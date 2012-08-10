<?php

require_once( 'HTTP/Request.php' );
require_once( 'XML/Unserializer.php' );

class mbrainz{
	var $_debug = false;
	const _API_URL_ = 'http://www.musicbrainz.org/ws/2/release';
	const _API_URL_ID = 'http://www.musicbrainz.org/ws/2/release/';
	var $_fromXML;
	var $_def = array(
			'url' => '/musicbrainz.org\/release\/([a-z0-9-]+)/i',
			'album' => 'http://www.musicbrainz.org/release/%s',
			'regex' => array(
					'title' => '/(\[US\]|\[UK\]|\[EUR\]|\[JP\])/',
					'uk' => '/(?<!\w)uk(?!\w)/i',
					'jp' => '/(?<!\w)(jp|japanese|japan)(?!\w)/i',
					'year' => '/(19\d{2}|20\d{2})/'
			)
	);

	function mbrainz(){
		$options = array(
				XML_UNSERIALIZER_OPTION_RETURN_RESULT    => true,
				XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => true,
		);
		$this->_fromXML = &new XML_Unserializer( $options );
	}

	function search( $search ){
		if( ( $album = $this->apisearch( $search ) ) != false){// Searching MusicBrainz Api
			return (object)$album;
		}
		//If we are here then MusicBrainz didn't have it
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
		$res = $api->db->select( '*', 'mbrainz_album', array( 'albumID' => $albumID ), __FILE__, __LINE__ );
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
		$res = $api->db->select( '*', 'mbrainz_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row;
		}else{
			return false;
		}
	}

	function getalbumfromurl($url, $ignoreCache = false){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['url'], $url, $urlinfo );
		if($ignoreCache){
			if( ( $album = $this->getAlbum($urlinfo[1]) ) != false){
				return (object)$album;
			}
		}else{
			if( ( $album = $this->getAlbumfromdb($urlinfo['1']) ) != false)
			{
				return $album;
			}elseif( ( $album = $this->getAlbum($urlinfo[1]) ) != false){
				return (object)$album;
			}
		}
		return false;
	}

	function getAlbum( $albumID ){
		global $api;
		$url = mbrainz::_API_URL_ID.$albumID.'?inc=artists';
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getXmlUrl( $url ) ) !== false)
		{
			$res = $api->db->select( '*', 'mbrainz_album', array( 'albumID' => $albumID ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			$result = $response['release'];
			print_r($result);
			preg_match($this->_def['regex']['year'], $result['date'], $date);
			$album = array(
					'albumID' => $result['id'],
					'artist' => $result['artist-credit']['name-credit']['artist']['name'],
					'title' => preg_replace( $this->_def['regex']['title'],"",$result['title']),
					'year' => $date[1],
					'genre' => '',
					'url' => sprintf($this->_def['album'], $result['id'])
			);
			if ( empty( $album['artist'] ) )
			{
				$album['artist'] = $result['artist-credit']['name-credit'][0]['artist']['name'];
			}
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
				$api->db->update( 'mbrainz_album', $album, array( 'albumID' => $album['albumID'] ), __FILE__, __LINE__  );
			}else{
				$api->db->insert( 'mbrainz_album', $album, __FILE__, __LINE__ );
			}
			return $album;
		}
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
		$query =  urlencode($search);
		if(preg_match($this->_def['regex']['uk'],$search) )
		{
			$query .= '+country:UK+status=Official&limit=1';
		}elseif(preg_match($this->_def['regex']['jp'],$search))
		{
			$query .= '+country:JP+status=Official&limit=1';
		}else{
			$query .= '+country:US+status=Official&limit=1';
		}
		if($this->_debug) printf( "query: %s \n", $query );
		$url = mbrainz::_API_URL_."?query=".$query;
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $response = $this->getXmlUrl( $url ) ) !== false )
		{
			
			if($response['release-list']['count'] != 0)
			{
			$result = $response['release-list']['release'];
			preg_match($this->_def['regex']['year'], $result['date'], $date);
			$album = array(
					'albumID' => $result['id'],
					'title' => preg_replace( $this->_def['regex']['title'],"",$result['title']),
					'artist' => '',
					'year' => $date[1],
					'genre' => '',
					'url' => sprintf($this->_def['album'], $result['id'])
			);
			if(isset($result['artist-credit']['name-credit']['artist']['name'])){
				$album['artist'] = $result['artist-credit']['name-credit']['artist']['name'];
			}else{
					$album['artist'] = $result['artist-credit']['name-credit'][0]['artist']['name'];
			}
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
			$res = $api->db->select( '*', 'mbrainz_search', array( 'albumID' => $result['id'] ), __FILE__, __LINE__ );
			$nRows = $api->db->rows( $res );
			if( $nRows >= 1 )
			{
				$api->db->update( 'mbrainz_search', array( 'search' => $search ), array( 'albumID' => $result['id'] ),  __FILE__, __LINE__ );
				$api->db->update( 'mbrainz_album', $album, array( 'albumID' => $album['albumID'] ), __FILE__, __LINE__  );
			}else{
				$api->db->insert( 'mbrainz_search', array( 'search' => $search, 'albumID' => $result['id'] ), __FILE__, __LINE__ );
				$api->db->insert( 'mbrainz_album', $album, __FILE__, __LINE__ );
			}
			return $album;
			}
		}
	}

	function getXmlUrl( $url )
	{
		if ( $this->_debug ) printf( "  Get XML URL: %s\n", $url );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			// parse the xml
			$xmlData = $this->_fromXML->unserialize( $page );
			if ( PEAR::isError( $xmlData ) )
			{
				if ( $this->_debug ) printf("   XML UnSerialization failed\n" );
				$page = preg_replace( '/\&\s/i', '&amp; ', $page );
				$xmlData = $this->_fromXML->unserialize( $page );
				if ( PEAR::isError( $xmlData ) )
					return false;
				else
					return $xmlData;
				return false;
			}
			return $xmlData;
		}
		return false;
	}
}

?>