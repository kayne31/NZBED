<?php

class tmdb{
	/**
	 * url of TMDB api
	 */
	const _API_URL_ = "http://api.themoviedb.org/3/";
	private $_apikey = "51feaa74efec5fd6a1caf6b92c4229c6";
	var $_def = array(
			'url' => array(
					'id' => 'http://www.themoviedb.org/movie/%s'
			),
			'year' => array(
					'/(19\d{2})|(20\d{2})/'
					),
			'regex' => array(
					'/\b(ntsc|usa|jpn)\b/i',
					'/\b(pal|eur)\b/i',
					'/\bsecam\b/i',
					'/\bcam\b/i',
					'/(dvd[.-]?scr|screener)/i',
					'/\btc\b/i',
					'/\br5/i',
					'/\bts\b/i',
					'/vhs/i',
					'/(hdtv|\.ts(?!\.))/i',
					'/dvd/i',
					'/(tvrip|pdtv|dsr|dvb|sdtv|dtv|satrip)/i',
					'/hd[-.]?dvd/i',
					'/(blu[-. ]?ray|b(d|r|rd)[-.]?(rom|rip))/i',
					'/(web[-. ]?dl|hditunes|ituneshd|ithd|webhd)/i',
					'/xvid/i',
					'/dvd(?!rip?.)/i',
					'/((h.?264|x264|avc))/i',
					'/avchd/i',
					'/\.ts(?!\.)/i',
					'/svcd/i',
					'/mvcd/i',
					'/divx/i',
					'/w(mv|vc1)/i',
					'/ratDVD/i',
					'/(720p|\.?720)/i',
					'/1080i/i',
					'/1080p/i',
					'/psp/i',
					'/\b(ipod|iphone|itouch)\b/i',
					'/(ac3|dd[25]\.?[01]|5\.1)/i',
					'/dts/i',
					'/mp3/i',
					'/aac/i',
					'/\bogg\b/i',
					'/(flac|lossless)/i',
					'/DVD/i',
					'/(!<?clone)CD/i',
					'/Clone(\.|\-|_|)CD/i',
					'/Alcohol(\.|\-|_|)120%/i',
					'/((DL)|(multi(5|3)))/i',
					'/(french)/i',
					'/((\.+DL\.+)|(german(?!.sub?.))|(deutsch))/i',
					'/((spanish)|(multi5))/i',
					'/((italian)|(multi(5|3)))/i',
					'/((dutch))/i',
					'/((\.+PL\.+))/i',
					'/((vostfr)|(vost))/i',
					'/(german.sub)/i',
					'/((nlsubs)|(nl.?subbed))/i',
					'/dvd.+/i',
					'/proper.+/i',
					'/iNTERNAL.*/',
					'/WS/',
					'/HR/'
			)
	);

	var $_debug = false;

	function getSFilm( $query, $ignoreCache = false )
	{
		if($this->_debug) printf( "query before: %s \n", $query );
		foreach ($this->_def['regex'] as $catregex){
			$query = preg_replace($catregex,"",$query);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		if($this->_debug) printf( "query after: %s \n", $query );
		if ( ( $tmdbID = $this->findFilm( $query, $ignoreCache ) ) !== false )
		{
			return $this->getFilm( $tmdbID, $ignoreCache );
		}
		else
		{
			return false;
		}
	}

	function findFilm( $query, $ignoreCache = false )
	{
		global $api;

		$res = $api->db->select( '*', 'tmdb_search', array('search' => $query ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );
		// check the cache
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			if ( $ignoreCache == false )
			{
				if ( $this->_debug ) printf( "using cache: %d\n", $row->tmdbID );
				return $row->tmdbID;
			}
		}
		// find film
		$squery="query=".urlencode($query);
		if ( $this->_debug ) printf( "url: %s \n", $squery );
		$results = $this->_call("search/movie",$squery,"en");
		if($results['results'][0]['id'] != ''){
			if ( $nRows >= 1 ){
				$api->db->update( 'tmdb_search', array( 'tmdbID' => $results['results'][0]['id'] ), array( 'search' => $query ), __FILE__, __LINE__ );
			}else{
				$api->db->insert( 'tmdb_search', array( 'tmdbID' => $results['results'][0]['id'], 'search' => $query ), __FILE__, __LINE__ );
			}
			return $results['results'][0]['id'];
		}else{
			return false;
		}
	}

	function getFilm( $tmdbID, $ignoreCache = false)
	{
		global $api;

		$res = $api->db->select( '*', 'tmdb_film', array( 'tmdbID' => $tmdbID ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );

		if ( $nRows >= 1 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ($nRows >= 1) && ($ignoreCache == false ) )
		{
			if ( $this->_debug ) printf( 'getFilm: usingCache' );
			return $row;
		}
		 	
		$query="movie/".urlencode($tmdbID);
		
		if ( $this->_debug ) printf( "url: %s \n", $query );
		$movie= $this->_call($query,"");
		foreach ($movie['genres'] as $gen)
		{
			$genstr [] = $gen['name'];
		}
		$genstr = ( count( $genstr ) > 0 )? implode( ', ', $genstr ):'';
		$film = array(
				'tmdbID' => $tmdbID,
				'title' => $movie['title'],
				'year' => substr($movie['release_date'],0,4),
				'genre' => $genstr,
				'url' => sprintf( $this->_def['url']['id'], $tmdbID ),
				'aka' => ""
				);
		if ( empty( $film['title'] ) )
		{
			if ( $nRows >= 1 )
				return $row;
			return false;
		}

		$film['title'] = str_replace( '"','',$film['title'] );
		if ( $nRows >= 1 )
			$api->db->update( 'tmdb_film', $film, array( 'filmID' => $row->filmID ), __FILE__, __LINE__ );
		else
			$api->db->insert( 'tmdb_film', $film, __FILE__, __LINE__ );

		return (object)$film; 
	}


	/**
	 * Makes the call to the API
	 *
	 * @param string $action		API specific function name for in the URL
	 * @param string $text		Unencoded paramter for in the URL
	 * @return string
	 */
	private function _call($action,$text,$lang="en"){
		// # http://api.themoviedb.org/3/movie/11?api_key=XXX
		$url= tmdb::_API_URL_.$action."?api_key=".$this->getApikey()."&language=".$lang."&".$text;
		if ( $this->_debug ) printf( "url: %s \n", $url );
		// echo "<pre>$url</pre>";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);

		$results = curl_exec($ch);
		$headers = curl_getinfo($ch);

		$error_number = curl_errno($ch);
		$error_message = curl_error($ch);

		curl_close($ch);
		// header('Content-Type: text/html; charset=iso-8859-1');
		// echo"<pre>";print_r(($results));echo"</pre>";
		$results = json_decode(($results),true);
		return (array) $results;
	}


	private function getApikey() {
		return $this->_apikey;
	}
}
?>