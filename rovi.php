<?php 

require_once( 'HTTP/Request.php' );

class rovi
{

	const _API_URL_ = "http://api.rovicorp.com/search/v2.1/music/search";
	const _API_URL_ID_ = "http://api.rovicorp.com/data/v1/album/info";
	private $_apikey = "ehf9t84pqfpnzb36watchwms";
	private $_sskey = "TDk8VrVXX7";

	var $_def = array(
			'url' => array(
					'album' => 'http://allmusic.com/album/%s',
					'search' => 'http://www.google.com/search?hl=en&q=%s+site:allmusic.com'
			),
			'regex' => array(
					'remove' => array(
							'/(ac3|dd[25]\.?[01]|5\.1)/i',
							'/dts/i',
							'/mp3/i',
							'/aac/i',
							'/\bogg\b/i',
							'/(flac|lossless)/i',
							'/(CDS|CD)/',
							'/(19\d{2})|(20\d{2})/',
							'/HQ/',
							'/\d{1,3}(\.?|\s?)kbps/i',
							'/kbps/i',
							'/repack/i',
							'/PROPER/',
							'/CDM/'
					),
					'googleid' => array(
							'/allmusic.com\/album\/([^\/]+(mw\d+))/i'
					)
			)
	);

	var $_debug = false;

	function checkCache( $search, $ignoreCache = false ){
		global $api;

		$res = $api->db->select( '*', 'music_search', array('search' => $search ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );
		// check the cache
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			//if ( $ignoreCache == false )    Refreshing system not built in yet.
			//{
			return $row->albumID;
			//}
		}else{
			return false;
		}
	}

	function getAlbumfromurl($url, $ignoreCache = false){
		if($this->_debug) printf( "url: %s \n", $url );
		if( ( $result = $this->getAlbumfromdb($url) ) != false)
		{
			return $result;
		}else{
			if( ( $album = $this->getAlbum($url,true) ) != false){
				return $album;
			}else{
				return false;
			}
		}
	}

	function getAlbum($query, $fromurl = false){
		global $api;

		if($fromurl){
			if($this->_debug) printf( "query: %s \n", $query );
			$url = rovi::_API_URL_ID_."?apikey=".$this->getApikey()."&sig=".$this->buildSig()."&albumid=".$query;
			if($this->_debug) printf( "url: %s \n", $url );
			if( ( $response = $this->getUrl( $url ) ) !== false)
			{
				$results = json_decode($response,true);
				$result = $results['album'];
				foreach ($result['genres'] as $gen)
				{
					$genstr [] = $gen['name'];
				}
				$genstr = ( count( $genstr ) > 0 )? implode( ', ', $genstr ):'';
				$album = array(
						'albumID' => $result['ids']['albumId'],
						'artist' => $result['primaryArtists'][0]['name'],
						'artistID' => $result['primaryArtists'][0]['id'],
						'title' => str_replace("[US]","",$result['title']),
						'year' => substr($result['originalReleaseDate'],0,4),
						'genre' => $genstr,
						'url' => sprintf( $this->_def['url']['album'], $result['ids']['albumId'] )
				);
				if ( empty( $album['title'] ) )
				{
					return false;
				}
				if ( empty( $album['year'] ) )
				{
					$album['year'] = 0;
				}
				$api->db->insert( 'music_album', $album, __FILE__, __LINE__ );
				$api->db->insert( 'music_search', array( 'search' => $query, 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
				return (object)$album;
			}else{
				return false;
			}
		}else{
			$query = urlencode($query);
			if($this->_debug) printf( "query: %s \n", $query );
			$url = rovi::_API_URL_."?apikey=".$this->getApikey()."&sig=".$this->buildSig()."&query=".$query."&entitytype=album&endpoint=music";
			if($this->_debug) printf( "url: %s \n", $url );
			if( ( $response = $this->getUrl( $url ) ) !== false)
			{
				$results = json_decode($response,true);
				$result = $results['searchResponse']['results'][0]['album'];
				$res = $api->db->select( '*', 'music_album', array( 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
				$nRows = $api->db->rows( $res );

				if ( $nRows >= 1 )
				{
					$row = $api->db->fetch( $res );
					return $row;
				}
				foreach ($result['genres'] as $gen)
				{
					$genstr [] = $gen['name'];
				}
				$genstr = ( count( $genstr ) > 0 )? implode( ', ', $genstr ):'';
				$album = array(
						'albumID' => $result['ids']['albumId'],
						'artist' => $result['primaryArtists'][0]['name'],
						'artistID' => $result['primaryArtists'][0]['id'],
						'title' => str_replace("[US]","",$result['title']),
						'year' => substr($result['originalReleaseDate'],0,4),
						'genre' => $genstr,
						'url' => sprintf( $this->_def['url']['album'], $result['ids']['albumId'] )
				);
				if ( empty( $album['title'] ) )
				{
					return false;
				}
				if ( empty( $album['year'] ) )
				{
					$album['year'] = 0;
				}
				$api->db->insert( 'music_album', $album, __FILE__, __LINE__ );
				$api->db->insert( 'music_search', array( 'search' => $query, 'albumID' => $result['ids']['albumId'] ), __FILE__, __LINE__ );
				return (object)$album;
			}else{
				//search google for an allmusic id before giving up
				if( ( $album = $this->searchGoogle($query) ) != false ) {
						return $album;
				}
				return false;
			}
		}
	}

	function searchGoogle($query){
		$url = sprintf($this->_def['url']['search'], $query);
		if($this->_debug) printf( "google url: %s \n", $url );
		if( ($page = $this->getUrl($url) ) != false){
			foreach( $this->_def['regex']['url'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl) )
				{
					if ( $this->debug ) echo 'gsUrl: '.$gsUrl[0]." \n";
					if( ( $result = $this->getAlbumfromurl($gsUrl[2]) ) !== false)
					{
						return $result;
					}else{
						return false;
					}
				}
			}
		}
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

	function getSAlbum( $search, $ignoreCache = false )
	{
		foreach ($this->_def['regex']['remove'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		global $api;
		if(($albumID = $this->checkCache($search, $ignoreCache)) !== false)
		{
			if($this->_debug) printf( "albumID: %s Found\n", $albumID );
			if(($result = $this->getAlbumfromdb($albumID))!== false){
				return $result;
			}
		}
		if( ( $album = $this->getAlbum($search) ) != false){
			return $album;
		}else{
			if( ( $album = $this->searchGoogle($search) ) != false ) {
					return $album;
			}
			return false;
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

	function buildSig(){
		return md5($this->getApikey().$this->getSskey().time());
	}

	function getSskey(){
		return $this->_sskey;
	}

	function getApikey(){
		return $this->_apikey;
	}

}
?>