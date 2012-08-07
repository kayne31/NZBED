<?php

require_once( 'HTTP/Request.php' );

class rovi{
	
	var $_debug = false;
	const _API_URL_ = "http://api.rovicorp.com/search/v2.1/music/search";
	const _API_URL_ID_ = "http://api.rovicorp.com/data/v1/album/info";
	private $_apikey = "ehf9t84pqfpnzb36watchwms";
	private $_sskey = "TDk8VrVXX7";
	var $_def = array(
			'myname' => 'rovi',
			'myurl' => '/^(?:http:\/\/)?(?:www\.)?allmusic.com\/album\/.*(mw\d+)/i',
			'urls' => array(
					'album' => 'http://allmusic.com/album/%s',
					'search' => 'http://www.google.com/search?hl=en&q=%s+site:allmusic.com'
			),
			'regex' => array(
					'googleid' => array(
							'/allmusic.com\/album\/([^\/]+(mw\d+))/i'
					),
					'title' => '/(\[US\]|\[UK\]|\[EUR\]|\[JP\])/'
			)
	);
	
	function search( $search ){
		if( ( $album = $this->apisearch( $search ) ) != false){// Searching Rovi Api
			return $album;
		}
		// Rovi API found nothing let's use Google Search to see if we can get a Rovi ID
		if( ( $albumID = $this->searchGoogle($search) ) != false ) {
			//Found an AlbumID going to pull from API with ID
			if( ( $result = $this->getAlbum( $albumID ) ) !== false ){
				return $result;// We found the album in the database and are returning it.
			}
		}
		//If we are here then rovi didn't have it
		return false;
	}
	
	function ismyurl( $url ){
		if( preg_match($this->_def['myurl'], $url ) != false )
		{
			return true;
		}else{
			return false;
		}
	}
	
	function geturlregex(){
		return $this->_def['myurl'];
	}
	
	function getAlbumfromdb($albumID){
		global $api;
		$res = $api->db->select( '*', 'music_album', array( 'albumID' => $albumID ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row;
		}else{
			return false;
		}
	}

	function checkCache( $search ){
		global $api;

		$res = $api->db->select( '*', 'music_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row->albumID;
		}else{
			return false;
		}
	}

	function getalbumfromurl($url, $ignoreCache = false){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['myurl'], $url, $urlinfo );
		if( !$ignoreCache )
		{
			if( ( $result = $this->getAlbumfromdb($urlinfo[1]) ) != false)
			{
				return $result;
			}
		}
		if( ( $album = $this->getAlbum($urlinfo[1],true) ) != false)
		{
			return $album;
		}else{
			return false;
		}
	}
	
	function getAlbum( $albumID ){
		global $api;
		if($this->_debug) printf( "query: %s \n", $albumID );
		$url = rovi::_API_URL_ID_."?apikey=".$this->getApikey()."&sig=".$this->buildSig()."&albumid=".$albumID;
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getUrl( $url ) ) !== false)
		{
			$results = json_decode($response,true);
			$result = $results['album'];
			$res = $api->db->select( '*', 'music_album', array( 'albumID' => $albumID ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			$genstr = $this->processgenres( $result['genres'] );
			$album = array(
					'albumID' => $result['ids']['albumId'],
					'artist' => $result['primaryArtists'][0]['name'],
					'artistID' => $result['primaryArtists'][0]['id'],
					'title' => preg_replace( $this->_def['regex']['title'],"",$result['title']),
					'year' => substr($result['originalReleaseDate'],0,4),
					'genre' => $genstr,
					'url' => sprintf( $this->_def['urls']['album'], $result['ids']['albumId'] )
			);
			if ( empty( $album['title'] ) )// API returned nothing
			{
				return false;
			}
			if ($album['artist'] === 'Various Artists')
			{
				$album['artist'] = 'VA';
			}
			if ( empty( $album['year'] ) )
			{
				$album['year'] = 0;
			}
			if( $nRows >= 1 )
			{
				$api->db->update( 'music_album', $album, array( 'albumID' => $album['albumID'] ), __FILE__, __LINE__  );
				$api->db->update( 'music_search', array( 'search' => $albumID ), array( 'albumID' => $album['albumID'] ),  __FILE__, __LINE__  );
			}else{
				$api->db->insert( 'music_album', $album, __FILE__, __LINE__ );
				$api->db->insert( 'music_search', array( 'search' => $albumID, 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
			}
			return (object)$album;
		}
	}

	function searchGoogle($query){
		$url = sprintf($this->_def['urls']['search'], $query);
		if($this->_debug) printf( "google url: %s \n", $url );
		if( ($page = $this->getUrl($url) ) != false){
			foreach( $this->_def['regex']['googleid'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl) )
				{
					if ( $this->debug ) echo 'gsUrl: '.$gsUrl[0]." \n";
					return $gsUrl[2];
				}
			}
		}
		return false;
	}

	function getUrl( $url, $redirect = true )
	{
		$req =& new HTTP_Request( );
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setURL( $url, array( 'timeout' => 30, 'readTimeout' => 30, 'allowRedirects' => $redirect ) );
		$request = $req->sendRequest();
		if (PEAR::isError($request)) {
			unset( $req, $request );
			return false;
		} else {
			$tmp = $req->getResponseHeader();
			if ( isset( $tmp['location'] ) )
			{
				return $this->getUrl( $tmp['location'] );
			}
			$body = $req->getResponseBody();
			unset( $req, $request );
			return $body;
		}
	}

	function buildSig(){
		return md5($this->getApikey().$this->getSskey().time());
	}

	function getSskey(){
		return $this->_sskey;
	}

	function getApikey(){
		return $this->_apikey;
	}
	
	function apisearch( $search ){
		global $api;
		$query = urlencode($search);
		if($this->_debug) printf( "query: %s \n", $query );
		$url = rovi::_API_URL_."?apikey=".$this->getApikey()."&sig=".$this->buildSig()."&query=".$query."&entitytype=album&endpoint=music";
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getUrl( $url ) ) !== false )
		{
			$results = json_decode($response,true);
			$result = $results['searchResponse']['results'][0]['album'];
			$res = $api->db->select( '*', 'music_album', array( 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			$genstr = $this->processgenres( $result['genres'] );
			$album = array(
					'albumID' => $result['ids']['albumId'],
					'artist' => $result['primaryArtists'][0]['name'],
					'artistID' => $result['primaryArtists'][0]['id'],
					'title' => preg_replace( $this->_def['regex']['title'],"",$result['title']),
					'year' => substr($result['originalReleaseDate'],0,4),
					'genre' => $genstr,
					'url' => sprintf( $this->_def['urls']['album'], $result['ids']['albumId'] )
			);
			if ( empty( $album['title'] ) )// API returned nothing
			{
				return false;
			}
			if ($album['artist'] === 'Various Artists')
			{
				$album['artist'] = 'VA';
			}
			if ( empty( $album['year'] ) )
			{
				$album['year'] = 0;
			}
			if( $nRows >= 1 )
			{
				$api->db->update( 'music_album', $album, array( 'albumID' => $album['albumID'] ), __FILE__, __LINE__  );
				$api->db->update( 'music_search', array( 'search' => $search ), array( 'albumID' => $album['albumID'] ),  __FILE__, __LINE__  );
			}else{
				$api->db->insert( 'music_album', $album, __FILE__, __LINE__ );
				$api->db->insert( 'music_search', array( 'search' => $search, 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
			}
			return (object)$album;
		}
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
}

?>