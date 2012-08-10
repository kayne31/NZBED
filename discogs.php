<?php

require_once( 'HTTP/Request.php' );

class discogs{
	var $_debug = false;
	const _API_URL_ = "http://api.discogs.com/database/search";
	const _API_URL_ID_ = "http://api.discogs.com/";
	
	var $_def = array(
			'url' => '/discogs.com\/[a-z-]+\/(master|release)\/(\d+)/i',
			'urls' => array(
					'search' => 'http://www.google.com/search?hl=en&q=%s+site:discogs.com'
			),
			'regex' => array(
					'googleid' => array(
							'/discogs.com\/[a-z-]+\/(master|release)\/(\d+)/i'
					),
					'title' => '/(\[US\]|\[UK\]|\[EUR\]|\[JP\])/'
			)
	);
	
	function search( $search ){
		if( ( $id = $this->apisearch( $search ) ) != false){// Searching discogs Api
			if( ( $album = $this->getAlbum( $id ) ) != false){
				if($this->_debug) printf( "albumID: %s \n", $album['id'] );
				return $album;
			}
		}
		// discogs API found nothing let's use Google Search to see if we can get a Rovi ID
		if( ( $albumID = $this->searchGoogle($search) ) != false ) {
			//Found an AlbumID going to pull from API with ID
			if($this->_debug) printf( "albumID: %s \n", $albumID['id'] );
			if( ( $result = $this->getAlbum( $albumID ) ) != false ){
				return $result;// We found the album in the database and are returning it.
			}
		}
		//If we are here then rovi didn't have it
		return false;
	}
	
	function ismyurl( $url ){
		if( preg_match($this->_def['url'], $url ) != false )
		{
			return true;
		}else{
			return false;
		}
	}
	
	function geturlregex(){
		return $this->_def['url'];
	}
	
	function getAlbumfromdb($albumID){
		global $api;

		$res = $api->db->select( '*', 'discogs_album', array( 'albumID' => $albumID ), __FILE__, __LINE__ );
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
		$res = $api->db->select( '*', 'discogs_search', array('search' => $search ), __FILE__, __LINE__ );
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
		preg_match( $this->_def['url'], $url, $urlinfo );
		if($ignoreCache){
			$id['type'] = $urlinfo[1];
			$id['id'] = $urlinfo[2];
			if( ( $album = $this->getAlbum($id) ) != false){
				return $album;
			}
		}else{
			if( ( $result = $this->getAlbumfromdb($urlinfo['2']) ) != false)
			{
				return $result;
			}elseif( ( $album = $this->getAlbum($id) ) != false){
				return $album;
			}
		}
		return false;
	}
	
	function getAlbum( $albumID ){
		global $api;
		if($albumID['type'] == 'master')
		{
			if($this->_debug) printf( "type: %s \n", $albumID['type'] );
			$url = discogs::_API_URL_ID_.'masters/'.$albumID['id'];
		}elseif($albumID['type'] == 'release')
		{
			if($this->_debug) printf( "type: %s \n", $albumID['type'] );
			$url = discogs::_API_URL_ID_.'releases/'.$albumID['id'];
		}else{
			return false;
		}
		
		if($this->_debug) printf( "type: %s \n", $albumID['type'] );
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getUrl( $url ) ) !== false)
		{
			$result = json_decode($response,true);
			$genstr = $this->processgenres( $result['genres'] );
			$res = $api->db->select( '*', 'discogs_album', array( 'albumID' => $albumID['id'] ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			$album = array(
					'albumID' => $result['id'],
					'artist' => $result['artists'][0]['name'],
					'title' => preg_replace( $this->_def['regex']['title'],"",$result['title']),
					'year' => $result['year'],
					'genre' => $genstr,
					'type' => $albumID['type'],
					'url' => $result['uri']
			);
			if ( empty( $album['title'] ) )// API returned nothing
			{
				return false;
			}
			if ($album['artist'] == 'Various Artists')
			{
				$album['artist'] = 'VA';
			}
			if ( empty( $album['year'] ) )
			{
				$album['year'] = 0;
			}
			if( $nRows >= 1 )
			{
				$api->db->update( 'discogs_album', $album, array( 'albumID' => $album['albumID'] ), __FILE__, __LINE__  );
			}else{
				$api->db->insert( 'discogs_album', $album, __FILE__, __LINE__ );
			}
			return (object)$album;
		}
	}

	function searchGoogle($search){
		global $api;
		$query = urlencode($search);
		$url = sprintf($this->_def['urls']['search'], $query.'+master');
		if($this->_debug) printf( "google url: %s \n", $url );
		if( ($page = $this->getUrl($url) ) != false){
			foreach( $this->_def['regex']['googleid'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl) )
				{
					if ( $this->_debug ) echo 'gsUrl: '.$gsUrl[0]." \n";
					$res = $api->db->select( '*', 'discogs_search', array( 'albumID' => $gsUrl[2] ), __FILE__, __LINE__ );
					$nRows = $api->db->rows( $res );
					if( $nRows >= 1 )
					{
						$api->db->update( 'discogs_search', array( 'search' => $search ), array( 'albumID' => $gsUrl[2], 'type' => $gsUrl[1] ),  __FILE__, __LINE__ );
					}else{
						$api->db->insert( 'discogs_search', array( 'search' => $search, 'albumID' => $gsUrl[2], 'type' => $gsUrl[1] ), __FILE__, __LINE__ );
					}
					$id['id'] = $gsUrl[2];
					$id['type'] = $gsUrl[1];
					return $id;
				}
			}
			//if we are here then google did not list a master so lets grab a release instead
			$url = sprintf($this->_def['urls']['search'], $query);
			if($this->_debug) printf( "google url: %s \n", $url );
			if( ($page = $this->getUrl($url) ) != false){
				foreach( $this->_def['regex']['googleid'] as $regex )
				{
					//if ( $this->_debug ) echo 'page: '.$page." \n";
					if ( preg_match( $regex, $page, $gsUrl) )
					{
						if ( $this->_debug ) echo 'gsUrl: '.$gsUrl[0]." \n";
						$res = $api->db->select( '*', 'discogs_search', array( 'albumID' => $gsUrl[2] ), __FILE__, __LINE__ );
						$nRows = $api->db->rows( $res );
						if( $nRows >= 1 )
						{
							$api->db->update( 'discogs_search', array( 'search' => $search ), array( 'albumID' => $gsUrl[2], 'type' => $gsUrl[1] ),  __FILE__, __LINE__ )	;
						}else{
							$api->db->insert( 'discogs_search', array( 'search' => $search, 'albumID' => $gsUrl[2], 'type' => $gsUrl[1] ), __FILE__, __LINE__ );
						}
						$id['id'] = $gsUrl[2];
						$id['type'] = $gsUrl[1];
						return $id;
					}
				}
			}
		}
		if ( $this->_debug ) echo 'returning false';
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

	function apisearch( $search ){
		global $api;
		$query = urlencode($search);
		$id = array();
		if($this->_debug) printf( "query: %s \n", $query );
		$url = discogs::_API_URL_."?q=".$query."&type=master";
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getUrl( $url ) ) !== false )
		{
			$results = json_decode($response,true);
			//print_r($results['results'][0]);
			if(count($results['results']) === 0){
				return false;
			}
			$result = $results['results'][0];
			$id['id'] = $result['id'];
			$id['type'] = $result['type'];
			$res = $api->db->select( '*', 'discogs_search', array( 'albumID' => $result['id'] ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			if( $nRows >= 1 )
			{
				$api->db->update( 'discogs_search', array( 'search' => $search ), array( 'albumID' => $id['id'], 'type' => $id['type'] ),  __FILE__, __LINE__ );
			}else{
				$api->db->insert( 'discogs_search', array( 'search' => $search, 'albumID' => $id['id'], 'type' => $id['type'] ), __FILE__, __LINE__ );
			}
			return $id;
		}
	}
	
	function processgenres( $result ){
		if( is_array( $result ) ){
		foreach ($result as $gen)
			{
				$genstr [] = $gen;
			}
			$genstr = ( count( $genstr ) > 0 )? implode( ', ', $genstr ):'';
		}else{
			$genstr = $result;
		}
		return $genstr;
	}
}

?>