<?php

/**************************************************
 * NZBed
 * Copyright (c) 2006 Harry Bragg
 * tiberious.org
 * Module: amg
 **************************************************
 *
 * Full GPL License: <http://www.gnu.org/licenses/gpl.txt>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
 */

require_once( 'HTTP/Request.php' );

class amg
{

	/**
	 * Lists the definitions for tvrage website
	 *
	 * @var array of regular expressions
	 * @access public
	 */
	var $_def = array(
		'url' => array(
//			'search' => 'http://www.allmusic.com/cg/amg.dll?p=amg&sql=1:%s~C',
//			'albumsearch' => 'http://www.allmusic.com/cg/amg.dll?p=amg&sql=%s~T2%d', // 1-3 main, compilation, single
//			'album' => 'http://www.allmusic.com/cg/amg.dll?p=amg&sql=%s'
			'search' => 'http://allmusic.com/search/artist/%s/filter:all/exact:0',
			'albumsearch' => 'http://allmusic.com/artist/%s/discography/%s', // main,compilations,singles-eps
			'album' => 'http://allmusic.com/album/%s'
		),
		'albumtype' => array(
			'main',
			'compilations',
			'singles-eps'
		),
		'regex' => array(
			'search' => array( '/<td><a href="http:\/\/allmusic.com\/artist\/(.+?)">([^<]+)<\/a><\/td>/i' ),
			'albumsearch' => array( '/<td class="cell"><a href="http:\/\/allmusic.com\/album\/(.+?)">(.+?)<\/a><\/td>/i' ),
			'album' => array(
				'title' => '/<h1 class="title">(.+?)<\/h1>\s*<div class="subtitle"><a href="http:\/\/allmusic.com\/artist\/(.+?)">(.+?)<\/a><\/div>/i',
				'genre' => '/<li><a href="[^"]+">(.+?)<\/a><\/li>/i',
				'year' => '/<h3>Release Date<\/h3>\s*<p>.*?(\d{4})<\/p>/i'
			),
		),
	);

	var $debug = false;

	/**
	 * Get URL
	 *
	 * @param string $url - url to get
	 * @return contents of the page
	 * @access public
	 */
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
	
	/**
	 * look for a game
	 *
	 * @param string $query - game search query
	 * @return string - gamespot Url
	 * @access public
	 */
	function findArtist( $query, $ignoreCache = false )
	{
		global $api;
		
		$res = $api->db->select( '*', 'allmusic_artistsearch', array('search' => $query ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		// check the cache
		if ( $nRows >= 1 )
        {
            $row = $api->db->fetch( $res );
            if ( $row->famgartistID != '')
            {
                return $row->famgartistID;
            }
            else if ( ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			          ( $ignoreCache == false ) )
             {
                 return $row->amgartistID;
             }
		}
		
		// find artist
        
        if ($this->debug) echo $query."\n";
        
        $query = str_replace( array( '&' ), array( '' ), stripslashes( $query ) );
		
        $search = urlencode( strtolower( $query ) );

        if ($this->debug) echo $search."\n";
			
		$url = sprintf( $this->_def['url']['search'], $search );

		if ($this->debug) echo $url."\n";
		
		if ( ( $page = $this->getUrl( $url, true ) ) !== false )
		{
			foreach( $this->_def['regex']['search'] as $regex )
			{
				if ( $this->debug ) printf( "regex: %s\n", $regex );
				if ( preg_match( $regex, $page, $amgID) )
				{
					if ($this->debug) print_r( $amgID );
					if ( $nRows >= 1 )
						$api->db->update( 'allmusic_artistsearch', array( 'amgartistID' => $amgID[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
					else
						$api->db->insert( 'allmusic_artistsearch', array( 'amgartistID' => $amgID[1], 'search' => $query ), __FILE__, __LINE__ );
					return $amgID[1];
				}
			}
			return false;
		}
		else
		{
			return false;
		}
	}
	
	function findAlbum( $artistID, $query, $ignoreCache = false )
	{
		global $api;
		
		$res = $api->db->select( '*', 'allmusic_albumsearch', array('amgartistID' => $artistID, 'search' => $query ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );

		// check the cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			$row = $api->db->fetch( $res );
			return $row->amgalbumID;
		}

		$query = stripslashes( $query );
		
		$query = strtolower($query);
		
		// find album
		
		foreach ( $this->_def['albumtype'] as $albumType )
		{
			$url = sprintf( $this->_def['url']['albumsearch'], $artistID, $albumType );
			if ($this->debug) echo $url."\n";
			if ( ( $page = $this->getUrl( $url ) ) !== false )
			{
				foreach( $this->_def['regex']['albumsearch'] as $regex )
				{
					if ( preg_match_all( $regex, $page, $alb) )
					{
						$max = 0;
						
						for ($j=0; $j < count( $alb[0] ); $j++ )
						{
							similar_text( $query, $alb[2][$j], $perc );
                            if ($this->debug) echo $alb[1][$j].' '.$alb[2][$j].' '.$perc."\n";  
                             
							if ( $perc < $max )
							{
								$max = $perc;
								$found = $alb[1][$j];
								//break(3);
							}
							$list[$alb[1][$j]] = $perc;
						}

					}
				}
			}
		}

		if ( !isset( $found ) )
		{
			if ( !isset( $list ) )
				return false;
			array_multisort( $list, SORT_DESC, SORT_NUMERIC );
			
			foreach( $list as $amgID => $perc )
			{
				if ( $perc > 70 )
				{
					$found = $amgID;
					break;
				}
				return false;
			}
		}

		if ( isset( $found ) )
		{
		/*
			if ( $nRows >= 1 )
				$api->db->update( 'allmusic_albumsearch', array( 'amgalbumID' => $found ), array( 'amgartistID' => $artistID, 'search' => $query ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'allmusic_albumsearch', array( 'amgalbumID' => $found, 'amgartistID' => $artistID, 'search' => $query ), __FILE__, __LINE__ );
		*/
			return $found;
		}
		
		return false;
	}

	function getSAlbum( $artist, $album, $ignoreCache = false )
	{
		if ( ( $artID = $this->findArtist( $artist, $ignoreCache ) ) !== false )
		{
			if ( ( $albumID = $this->findAlbum( $artID, $album, $ignoreCache ) ) !== false )
			{
				return $this->getAlbum( $albumID, $ignoreCache );
			}
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * Get Film
	 *
	 * @param string $tvin - tvrage showID
	 * @return array - Show information
	 * @access public
	 */
	function getAlbum( $albumID, $ignoreCache = false )
	{
		global $api;
		
		$res = $api->db->select( '*', 'allmusic_album', array( 'amgalbumID' => $albumID ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		if ( $nRows >= 1 )
			$row = $api->db->fetch( $res );
	
		// check cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			return $row;
		}
		
		$url = sprintf( $this->_def['url']['album'], $albumID );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			preg_match( $this->_def['regex']['album']['title'], $page, $title );
			preg_match( $this->_def['regex']['album']['year'], $page, $year );
			preg_match_all( $this->_def['regex']['album']['genre'], $page, $genreList );
			
			$album = array(
				'amgalbumID' => $albumID,
				'amgartistID' => $api->stringDecode( $title[2] ),
				'artist' => $api->stringDecode( $title[3] ),
//				'artist' => $title[3],
				'title' => $api->stringDecode( $title[1] ),
//				'title' => $title[1],
				'genre' => implode( ', ', $genreList[1] ),
				'year' => $api->stringDecode( $year[1] ),
//				'year' => $year[1],
				'url' => sprintf( $this->_def['url']['album'], $albumID ) );

			if ( $this->debug ) var_dump( $album );
			
			if ( empty( $album['title'] ) )
			{
				if ( $nRows >= 1 )
					return $row;
				return false;
			}
			
			if ( $nRows >= 1 )
				$api->db->update( 'allmusic_album', $album, array( 'amgalbumID' => $row->amgalbumID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'allmusic_album', $album, __FILE__, __LINE__ );
						
			return (object)$album;
		}
		else
		{
			return false;
		}
	}
}

?>
