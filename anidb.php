<?php

require_once( 'HTTP/Request.php' );

class anidb
{
	var $_debug = false;
	var $_def = array(
			'myname' => 'anidb',
			'myurl' => '/(?:http:\/\/)?(?:www\.)?anidb\.net\/(?:perl-bin\/animedb.pl\?show=(ep|anime)&\wid=(\d+))?([ae]\d+)?/i',
			'url' => array(
					'search' => 'http://anidb.net/perl-bin/animedb.pl?show=search&query=%s&do.fsearch=Search',
					'anime' => 'http://anidb.net/perl-bin/animedb.pl?show=anime&aid=%d',
					'episode' => 'http://anidb.net/perl-bin/animedb.pl?show=ep&eid=%d',
					'report' => 'http://anidb.net/%s'
			),
			'findAnime'             => '/<tr class="g_odd">\s+<td class="score">[0-9.]+<\/td>\s+<td class="type">.+<\/td>\s+<td class="id"><a href="http:\/\/anidb.net\/a\d+">(a\d+)<\/a><\/td>\s+<td class="title">(.+)<\/td>\s+<td class="excerpt">.*?<\/td>\s+<\/tr>/i',
			'anime'         => array(
					'mainTitle'         => '/<tr class=".+?">\s+<th class="field">Main Title<\/th>\s+<td class="value">(.+)\s+\(<a class="shortlink" href="http:\/\/anidb.net\/a\d+">(a\d+)<\/a>\)<\/td>\s+<\/tr>/i',
					'officialTitle'     => '/<tr class="(?:g_odd )?official verified (yes|no)">\s+<th class="field">Official Title<\/th>\s+<td class="value">\s+<span class="icons">\s+(?s:(.+?))<\/span>\s+<label>(.+)<\/label><\/td>\s+<\/tr>/i',
					'officialLang'      => '/<span>(..)<\/span>/i',
					'type'              => '/<tr class="(?:g_odd )?type">\s+<th class="field">Type<\/th>\s+<td class="value">(TV Series|OVA|Movie)(?:, (?:unknown number of|\d+?) episodes)?(?:, \d+ movies)?<\/td>\s+<\/tr>/i',
					'episode'           => '/<td class="id eid">\s+<a href="(.+?)">\s+<abbr title="(.+?)">%d\s+<abbr>\s+<\/a>\s+<\/td>\s+<td class="title">\s+<label title="[^"]+">(.+?)\s+<\/label>\s+<\/td>/i',
					'year'				=> '/<tr class="(?:g_odd )?year">\s+<th class="field">Year<\/th>\s+<td class="value">(?:\d+.\d+.)(\d{4})<\/td>\s+<\/tr>/i'
			),
			'error' => '/<h3>ERROR<\/h3>/i',
			'regex' => array(
					'parsesearch' => '/(.+?)\s*(?:[-_\.]|\s*)(?:e(?:ep(?:isode)?)?)?\s*[-_\.]?\s*((?:\d+\s?-\s?)?\d+)(.+?)?$/i',
					'epSplit' => '/(\d+)\s*-\s*(\d+)/',
					'year' => 	'/(19\d{2})|(20\d{2})/'
					)
	);

	function findAnime( $query )
	{
		global $api;
		$res = $api->db->select( '*', 'anidb_search', array('search' => $query ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		$url = sprintf( $this->_def['url']['search'], urlencode( strtolower( $query ) ) );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			if ( preg_match($this->_def['findAnime'], $page, $foundanime) )
			{
				if ($this->_debug)
				{
					echo 'Found anidbID: ';
					print_r( $foundanime);
				}
				if ( $nRows >= 1 )
					$api->db->update( 'anidb_search', array( 'anidbID' => $foundanime[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'anidb_search', array( 'anidbID' => $foundanime[1], 'search' => $query ), __FILE__, __LINE__ );
				return $foundanime[1];
			}
			else
			{
				return false;
			}
		}
		else
		{
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

	function search( $query )
	{
		preg_match( $this->_def['regex']['parsesearch'], $query, $matches );
		if ( ( $anidbID = $this->findAnime( $matches[1] ) ) !== false )
		{
			if( isset( $matches[2] ) ){
				return $this->getAnime( $anidbID, $matches[2] );// we have an episode also
			}else{
				return $this->getAnime( $anidbID );
			}
		}
		else
			return false;
	}
	
	function getanimefromurl( $url, $ignoreCache = false ){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['myurl'], $url, $urlinfo );
		if( $urlinfo['1'] === 'ep' )
		{
			$id = 'e'.$urlinfo[2];
		}elseif( $urlinfo[1] === 'anime' )
		{
			$id = 'a'.$urlinfo[2];
		}else{
			if( isset($urlinfo[3]) ){
				$id = $urlinfo[3];
			}else{
				return false;
			}
		}
		if( !$ignoreCache )
		{
			if( ( $game = $this->getAnimefromdb( $id ) ) != false )
			{
				return $game;
			}
		}
		if( ( $game = $this->getAnime( $id ) ) != false)
		{
			return $game;
		}else{
			return false;
		}
	}
	
	function getAnimefromdb( $id, $ep=false )
	{
		global $api;
		$res = $api->db->select( '*', 'anidb_anime', array( 'anidbID' => $id ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			if($ep)
			{
				if( ( $anime = $this->getAnime( $id, $ep ) ) != false)
				{
					return $anime;
				}else{
					return false;
				}
			}else{
				$anime = $api->db->fetch( $res );
				return $anime;
			}
		}else{
			if($ep)
			{
				if( ( $anime = $this->getAnime( $id, $ep ) ) != false)
				{
					return $anime;
				}else{
					return false;
				}
			}else{
				if( ( $anime = $this->getAnime( $id ) ) != false)
				{
					return $anime;
				}else{
					return false;
				}
			}
		}
	}
	
	function checkCache( $search ){
		global $api;
		preg_match( $this->_def['regex']['parsesearch'], $search, $matches );
		$res = $api->db->select( '*', 'anidb_search', array('search' => $matches[1] ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			if(isset($matches[2]))
			{
				return (object) $this->getAnimefromdb($row->anidbID, $matches[2]);
			}else{
				return (object) $this->getAnimefromdb($row->anidbID);
			}
		}else{
			return false;
		}
	}

	function getAnime( $anidbID, $episode = false )
	{
		global $api;
		$res = $api->db->select( '*', 'anidb_anime', array( 'anidbID' => $anidbID ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
            $row = $api->db->fetch( $res );
		if( substr( $anidbID,0,1 ) === 'a' ){
			$url = sprintf( $this->_def['url']['anime'], substr($anidbID,1) );
		}elseif (substr( $anidbID,0,1 ) === 'e' ){
			$url = sprintf( $this->_def['url']['episode'], substr($anidbID,1) );			
		}else{
			return false;
		}
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			if ( preg_match( $this->_def['error'], $page ) )
			{
				return false;
			}

			preg_match( $this->_def['anime']['mainTitle'], $page, $main );
			preg_match_all( $this->_def['anime']['officialTitle'], $page, $offTitle );
			preg_match( $this->_def['anime']['type'], $page, $type );

			if ($this->_debug)
			{
				echo 'Main: '.$main[1];
				echo 'ID: '.$main[2];
				echo 'Type: '.$type[1];
				print_r( $offTitle );
			}

			for ($i=0; $i < count( $offTitle[0] ); $i++)
			{
				preg_match_all( $this->_def['anime']['officialLang'], $offTitle[2][$i], $langs );
				for ($j=0; $j < count($langs[0]); $j++)
				{
					if ($langs[1][$j] == 'en')
					{
						$enTitle = $api->stringDecode( $offTitle[3][$i] );
					}
				}
			}

			if (!isset($enTitle))
			{
				$enTitle = $api->stringDecode( $main[1] );
			}

			$anime = array(
					'anidbID' => $main[2],
					'name' => $enTitle,
					'type' => trim($api->stringDecode( $type[1] ) ),
					'url' => sprintf( $this->_def['url']['report'], $anidbID) );
			if($this->_debug) printf( "Type: %s \n", $anime['type'] );		
			if ($anime['type'] === 'Movie'){
				if (preg_match($this->_def['anime']['year'], $page, $year))
					{
						if($this->_debug) print_r($year);
						$anime['name'] = sprintf( "%s (%d)", $anime['name'], $year[1]);
					}
			}
			if ($this->_debug)
			{
				print_r( $anime );
			}

			if ( empty( $anime['name'] ) )
			{
				return false;
			}
			
			if( $episode != false){
				if ($this->_debug) printf("episode - %s\n", $episode);
				$episode = trim( $episode );
				if( substr( $episode,0,1 ) === 0)
					$episode = substr( $episode,1 );
				if (preg_match( $this->_def['regex']['epSplit'], $episode ))
				{
					$title = sprintf( '%s - %02d-%02d', $anime->name, $split[1], $split[2] );
				}else{
					$r = sprintf( $this->_def['anime']['episode'], $episode );
					if ( $this->_debug ) printf( "%s\n", $r );
					if ( preg_match( $r, $page, $match ) )
					{
						$anime['name'] = sprintf( '%s - %02d - %s', $anime['name'], $episode, $api->stringDecode( $match[3] ) );
						if ( $this->_debug ) print_r($anime);
					}else{
						$anime['name'] = sprintf( '%s - %02d', $anime['name'], $episode );
					}
				}
			}

			if ( $nRows >= 1 )
			{
				$api->db->update( 'anidb_anime', $anime, array( 'animeID' => $row->animeID ), __FILE__, __LINE__ );
			}
			else
				$api->db->insert( 'anidb_anime', $anime, __FILE__, __LINE__ );

			return (object)$anime;
		}
		else
		{
			return false;
		}

	}
	
	function geturlregex(){
		return $this->_def['myurl'];
	}
	
	function getUrl( $url )
	{
		if ( $this->_debug ) printf("getUrl( url:%s );\n", $url );
		$req = new HTTP_Request( );
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setURL( $url, array( 'timeout' => '30', 'readTimeout' => 30, 'allowRedirects' => true ) );
		$request = $req->sendRequest();
		if (PEAR::isError($request)) {
			unset( $req, $request );
			return false;
		} else {
			$body = $req->getResponseBody();
			unset( $req, $request );
			return $body;
		}
	}
}

?>
