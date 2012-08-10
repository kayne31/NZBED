<?php
require_once( 'HTTP/Request.php' );

class tmdb{
	var $_debug = false;
	const _API_URL_ = "http://api.themoviedb.org/3/";
	private $_apikey = "51feaa74efec5fd6a1caf6b92c4229c6";	
	var $_def = array(
			'myname' => 'tmdb',
			'myurl' => '/^(?:http:\/\/)?(?:www\.)?themoviedb\.org\/movie\/(\d+)/i',
			'urls' => array(
					'movie' => 'http://www.themoviedb.org/movie/%s'
			),
			'regex' => array(
				'year' => array(
					'/(19\d{2})|(20\d{2})/'
					)
			)
	);
	
	function search( $query )
	{
		if ( ( $id = $this->apisearch( $query ) ) !== false )
		{
			return $this->getFilm( $id );
		}
		return false;
	}
	
	function checkCache( $search ){
		global $api;
		$res = $api->db->select( '*', 'tmdb_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row->tmdbID;
		}else{
			return false;
		}
	}
	
	function geturlregex(){
		return $this->_def['myurl'];
	}
	
	function getmoviefromurl($url, $ignoreCache = false){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['myurl'], $url, $urlinfo );
		if( !$ignoreCache )
		{
			if( ( $movie = $this->getMoviefromdb($urlinfo['1']) ) != false)
			{
				return $movie;
			}
		}
		if( ( $movie = $this->getFilm( $urlinfo['1'] ) ) != false)
		{
			return $movie;
		}else{
			return false;
		}
	}
	
	function getMoviefromdb( $id )
	{
		global $api;
		$res = $api->db->select( '*', 'tmdb_film', array( 'tmdbID' => $id ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row;
		}else{
			return false;
		}
	}
	
	function ismyurl( $url )
	{
		if( preg_match($this->_def['myurl'], $url ) != false )
		{
			return true;
		}else{
			return false;
		}
	}
	
	function getFilm( $tmdbID )
	{
		global $api;

		$res = $api->db->select( '*', 'tmdb_film', array( 'tmdbID' => $tmdbID ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		$query="movie/".$tmdbID;
		if ( $this->_debug ) printf( "query: %s \n", $query );
		$movie= $this->_call($query,"");
		$genstr = $this->processgenres( $movie['genres'] );
		$film = array(
				'tmdbID' => $tmdbID,
				'title' => $movie['title'],
				'year' => substr($movie['release_date'],0,4),
				'genre' => $genstr,
				'url' => sprintf( $this->_def['urls']['movie'], $tmdbID ),
				'aka' => ""
				);
		if ( empty( $film['title'] ) )
		{
			return false;
		}
		if ( empty( $film['year'] ) || $film['year'] === null )
		{
			$film['year'] = 0;
		}
		if($movie['imdb_id'] != '')
		{
			$film['url'] = sprintf('http://www.imdb.com/title/%s/', $movie['imdb_id']);
			if( $this->_debug ) echo "Inserting into IMDB";
			$api->movies->insertImdb($movie['imdb_id']);
		}
		
		if ( $nRows >= 1 )
			$api->db->update( 'tmdb_film', $film, array( 'filmID' => $row->filmID ), __FILE__, __LINE__ );
		else
			$api->db->insert( 'tmdb_film', $film, __FILE__, __LINE__ );

		return (object)$film; 
	}
	
	function processgenres( $result ){
		if( is_array( $result ) ){
		foreach ($result as $gen)
			{
				$genstr [] = $gen['name'];
			}
			$genstr = ( count( $genstr ) > 0 )? implode( ', ', $genstr ):'';
		}else{
			$genstr = $result;
		}
		return $genstr;
	}
	
	function apisearch( $search )
	{
		global $api;

		$query="query=".urlencode($search);
		if ( $this->_debug ) printf( "url: %s \n", $query );
		$results = $this->_call("search/movie",$query,"en");
		if($results['results'][0]['id'] != '')
		{
			$res = $api->db->select( '*', 'tmdb_search', array('search' => $search ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			if ( $nRows >= 1 ){
				$api->db->update( 'tmdb_search', array( 'tmdbID' => $results['results'][0]['id'] ), array( 'search' => $search ), __FILE__, __LINE__ );
			}else{
				$api->db->insert( 'tmdb_search', array( 'tmdbID' => $results['results'][0]['id'], 'search' => $search ), __FILE__, __LINE__ );
			}
			return $results['results'][0]['id'];
		}else{
			return false;
		}
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
	
	function getName(){
		return $this->_def['myname'];
	}

	private function getApikey() {
		return $this->_apikey;
	}	
}
?>