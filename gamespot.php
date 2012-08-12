<?php
require_once( 'HTTP/Request.php' );

class gamespot{
	var $_debug = false;
	var $_def = array(
			'myname' => 'gamespot',
			'myurl' => '/(?:http:\/\/)?(?:www\.)?gamespot\.com\/[a-zA-Z\-0-9]+\//i',
			'url' => array(
					'search' => 'http://www.google.com/search?hl=en&q=%s+site:gamespot.com&btnI=1',
					'search2' => 'http://www.gamespot.com/search/?qs=%s',
					'game' => '%s',
			),
			'regex' => array(
					'url' => array(
							'/(?:http:\/\/)?(?:www\.)?gamespot\.com\/[a-zA-Z\-0-9]+\//i'
					),
					'direct' => array(
							'/<div class=\"result_title\"><a href=\"[a-z]+:\/\/[a-z\.]+\/[a-z_\-\.0-9]+\/">.+?<\/a>/i'
					),
					'game' => array(
							'title' => '/<meta\sproperty="og:title"\scontent="([a-z\s\.\-#&;:,0-9!\?]+)"\s\/>/i',
							'year' => '/<dt>Release Date:<\/dt>\s*<dd>\s*\w+\s+\d+,\s(\d{4})/i',
							'year1' => '/<li class="date"><div class="statWrap"><span class="label">Release Date: <\/span><span class="data">\s*\w+\s+\d+,\s(\d{4})/i',
							'year2' => '/<li class=\"date\"><div class=\"statWrap\"><span class=\"label\">Release:\s<\/span><span class=\"data\"><a href=\".+?">\s*\w+\s+\d+,\s(\d{4})/i',
							'year3' => '/<li class=\"date\"><div class=\"statWrap\"><span class=\"label\">Release:\s<\/span><span class=\"data\"><span>\s*\w+\s+\d+,\s(\d{4})/i',
							'year4' => '/<li class=\"date\"><div class=\"statWrap\"><span class=\"label\">Release:\s<\/span><span class=\"data\"><span>\s*\w+\s+(\d{4})/i',
							'year5' => '/<li class=\"date\"><div class=\"statWrap\"><span class=\"label\">Release:\s<\/span><span class=\"data\"><a href=\".+?">\s*\w+\s+(\d{4})/i',
							'genre' => '/Genre:.+?<a href=".+?title=".+?">(.+?)</i',
							'platform' => '/<ul\sclass=\"platformFilter.+?}\">All\sPlatforms.+?(xbox360\/|pc\/|ps3\/|wii\/)">(\w+)<\/a>/i',
							'platform1' => '/<ul\sclass=\"platformFilter.+?>All\sPlatforms.+?{.+?}">(\w+)</i',
					),
			),
	);

	function search( $query )
	{
		if ( $this->debug ) sprintf( "Query: %s\n",$query );
		if ( ( $url = $this->gamesearch( $query ) ) !== false )
		{
			if ( $this->debug ) sprintf( "Url: %s\n",$url );
			return $this->getGame( $url );
		}
		return false;
	}
	
	function checkCache( $search ){
		global $api;
		$res = $api->db->select( '*', 'gamespot_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row->gsUrl;
		}else{
			return false;
		}
	}
	
	function getgamefromurl( $url, $ignoreCache = false ){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['myurl'], $url, $urlinfo );
		if( !$ignoreCache )
		{
			if( ( $game = $this->getGamefromdb( $urlinfo['0'] ) ) != false )
			{
				return $game;
			}
		}
		if( ( $game = $this->getGame( $urlinfo['0'] ) ) != false)
		{
			return $game;
		}else{
			return false;
		}
	}
	
	function geturlregex(){
		return $this->_def['myurl'];
	}
	
	function getGamefromdb( $url )
	{
		global $api;
		$res = $api->db->select( '*', 'gamespot_game', array( 'gsUrl' => $url ), __FILE__, __LINE__ );
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

	function getGame( $gsUrl, $ignoreCache = false )
	{
		global $api;

		$res = $api->db->select( '*', 'gamespot_game', array( 'gsUrl' => $gsUrl ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		$url = sprintf( $this->_def['url']['game'], $gsUrl );
		//can get all information from the main gamespot page
		if ( ( ( $page = $this->getUrl( $url, true ) ) !== false ) )
		{
			preg_match( $this->_def['regex']['game']['title'], $page, $title );
			//Checks each year for a match Gamespot has many
			if(!preg_match( $this->_def['regex']['game']['year'], $page, $year ))
			{
				if(!preg_match( $this->_def['regex']['game']['year1'], $page, $year )){
					if(!preg_match( $this->_def['regex']['game']['year2'], $page, $year )){
						if(!preg_match( $this->_def['regex']['game']['year3'], $page, $year )){
							if(!preg_match( $this->_def['regex']['game']['year4'], $page, $year )){
								preg_match( $this->_def['regex']['game']['year5'], $page, $year );
							}
						}
					}
				}
			}
			preg_match( $this->_def['regex']['game']['genre'], $page, $genre );
			if(!preg_match( $this->_def['regex']['game']['platform'], $page, $platform )){
				preg_match( $this->_def['regex']['game']['platform1'], $page, $platform );
				$platform[2] = $platform[1];
			}
			$game = array(
					'gsUrl' => $gsUrl,
					'title' => $title[1] ,
					'genre' => trim( $genre[1] ),
					'year' => $api->stringDecode( $year[1] ),
					'platform' => $platform[2],
					 );

			if ( $this->_debug ) var_dump( $game );
			if ( empty( $game['title'] ) )
			{
				return false;
			}
			if ( $nRows >= 1 )
				$api->db->update( 'gamespot_game', $game, array( 'gsID' => $row->gsID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'gamespot_game', $game, __FILE__, __LINE__ );

			return (object)$game;
		}
		else
		{
			return false;
		}
	}

	function gamesearch( $query )
	{
		global $api;

		$res = $api->db->select( '*', 'gamespot_search', array('search' => $query ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $this->_debug ) echo 'Query: '.$query." \n";
		// find game google search first
		$url = sprintf( $this->_def['url']['search'], urlencode( strtolower( $query ) ) );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			foreach( $this->_def['regex']['url'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl ) )
				{
					if ( $this->_debug ) echo 'gsUrl: '.$gsUrl[0]." \n";
					if ( $nRows >= 1 )
						$api->db->update( 'gamespot_search', array( 'gsUrl' => $gsUrl[0] ), array( 'search' => $query ), __FILE__, __LINE__ );
					else
						$api->db->insert( 'gamespot_search', array( 'gsUrl' => $gsUrl[0], 'search' => $query ), __FILE__, __LINE__ );
					return $gsUrl[0];
				}
			}
		}
		//Couldn't find link via google so searching Gamespot direct
		$url = sprintf( $this->_def['url']['search2'], urlencode(strtolower($query)) );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			foreach( $this->_def['regex']['direct'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl) )
				{
					if ( $nRows >= 1 )
						$api->db->update( 'gamespot_search', array( 'gsUrl' => $gsUrl[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
					else
						$api->db->insert( 'gamespot_search', array( 'gsUrl' => $gsUrl[1], 'search' => $query ), __FILE__, __LINE__ );
					return $gsUrl[1];
				}
			}
		}
		return false;// could not find any match
	}

	function getUrl( $url, $redirect = false )
	{
		$req =& new HTTP_Request( );
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setURL( $url, array( 'timeout' => '30', 'readTimeout' => 30, 'allowRedirects' => $redirect ) );
		$request = $req->sendRequest();
		if (PEAR::isError($request)) {
			unset( $req, $request );
			return false;
		} else {
			$body = $req->getResponseBody();
			if ( empty( $body ) )
			{
				$nURL = $req->getResponseHeader( 'location' );
				if ( isset( $nURL ) )
				{
					unset( $req, $request );
					return $this->getUrl( $nURL, true );
				}
			}
			unset( $req, $request );
			return $body;
		}
	}

}
?>