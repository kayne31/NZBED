<?php

/**************************************************
 * NZBed
 * Copyright (c) 2006 Harry Bragg
 * tiberious.org
 * Module: gm (google music)
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

class gm
{

	/**
	 * Lists the definitions for tvrage website
	 *
	 * @var array of regular expressions
	 * @access public
	 */
	var $_def = array(
		'url' => array(
			'search' => 'http://www.google.com/musicsearch?q=%s&res=album',
			'album' => 'http://www.google.com/musicl?lid=%s',
		),
		'regex' => array(
			'search' => array( 
				'/<a href="\/musicl\?lid=(.+?)(?:&(?:a|s)id=.+?)?">(?:.+?)<\/a>/i',
				'/^\/musicl\?lid=(.+?)(?:&(?:a|s)id=.+?)?$/i'
			),
			'album' => array(
				'artist' => '/<b>Artist:<\/b>&nbsp;&nbsp;<font size=-1>(.+?)<\/font><br>/i',
				'album' => '/<br><b>Album:<\/b>&nbsp;&nbsp;<font size=-1>(.+?)(?:&nbsp;\-&nbsp;|<\/font>)/i',
				'year' => '/<br><b>Year:<\/b>&nbsp;&nbsp;<font size=-1>(\d+)<\/font>/i',
			),
		),
		'strip' => array(
			'search' => array( '-', '_', '+', '.' ),
			'result' => array( '<b>', '</b>' ),
		),
		'replace' => array(
			'from' => array(
				'Various Artists',
				'Sound Effects',
			),
			'to' => array(
				'VA',
				'OST',
			),
		),
	);

	var $debug = false;

	// store errors
	var $error;

	/**
	 * Get URL
	 *
	 * @param string $url - url to get
	 * @return contents of the page
	 * @access public
	 */
	function getUrl( $url, $redirect = true )
	{
		if ( $this->debug ) printf("gm->getUrl( url:%s, redirect:%d )\n", $url, $redirect );
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
				if ( $this->debug ) printf(" Returning redirection location: %s\n", $tmp['location'] );
				return $tmp['location'];
			}
			$body = $req->getResponseBody();
			unset( $req, $request );
			return $body;
		}
	}
	
	function findAlbum( $query, $ignoreCache = false )
	{
		if ( $this->debug ) printf("\ngm->findAlbum( query:%s, ignoreCache:%d )\n", $query, $ignoreCache );
	
		global $api;

		$query = str_replace( $this->def['strip']['search'], ' ', $query );
		$query = preg_replace( '/\s+/i', ' ', $query );
		
		$res = $api->db->select( '*', 'googlemusic_albumsearch', array( 'search' => $query ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );

		if ( $nRows > 0 )
		{
			$row = $api->db->fetch( $res );
			if ( !empty( $row->fgmalbumID ) )
			{
				if ( $this->debug )printf(" Using forced cache: %s\n", $row->fgmalbumID );
				return $row->fgmalbumID;
			}
		}
		
		// check the cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" Using cache: %s\n", $row->gmalbumID );
			return $row->gmalbumID;
		}

		$search = urlencode( stripslashes( $query ) );
	
		$url = sprintf( $this->_def['url']['search'], $search );

		if ( ( $page = $this->getUrl( $url, false ) ) !== false )
		{
			if ( isset( $this->_def['regex']['search'] ) )
			{
				if ( is_array( $this->_def['regex']['search'] ) )
				{
					foreach( $this->_def['regex']['search'] as $regex )
					{
						if ( $this->debug ) printf(" search regex: %s\n", $regex );
						if ( preg_match( $regex, $page, $match ) )
						{
							$found = $match[1];
							break;
						}
					}
				}
				else
				{
					if ( preg_match( $this->_def['regex']['search'], $page, $match ) )
					{
						$found = $match[1];
					}
				}
			}

			if ( isset( $found ) )
			{
				if ( $this->debug ) printf(" Found result: %s\n", $found );
		
				if ( $nRows >= 1 )
					$api->db->update( 'googlemusic_albumsearch', array( 'gmalbumID' => $found ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'googlemusic_albumsearch', array( 'gmalbumID' => $found, 'search' => $query ), __FILE__, __LINE__ );
				return $found;
			}
			else
			{
				if ( $this->debug ) printf(" Failed to find album\n" );
				$this->error = 'Failed to find album on google';
			}
		}
		else
		{
			if ( $this->debug ) printf(" Request to google failed\n");
			$this->error = 'google.com request failed, try again later';
		}
		return false;
	}

	function getSAlbum( $query, $ignoreCache = false )
	{
		if ( ( $albumID = $this->findAlbum( $query, $ignoreCache ) ) !== false )
		{
			return $this->getAlbum( $albumID, $ignoreCache );
		}
		else
			return false;
	}

	/**
	 * Get Album
	 *
	 * @param string $tvin - google music album ID
	 * @return array - Album Information information
	 * @access public
	 */
	function getAlbum( $albumID, $ignoreCache = false )
	{
		if ( $this->debug ) printf("\ngm->getAlbum( albumID:%s, ignoreCache:%d)\n", $albumID, $ignoreCache );

		global $api;
		
		$res = $api->db->select( '*', 'googlemusic_album', array( 'gmalbumID' => $albumID ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		if ( $nRows >= 1 )
			$row = $api->db->fetch( $res );
	
		// check cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" Using Cache: \n" );
			if ( $this->debug ) print_r( $row );
			return $row;
		}
		
		$url = sprintf( $this->_def['url']['album'], $albumID );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			preg_match( $this->_def['regex']['album']['artist'], $page, $artist );
			preg_match( $this->_def['regex']['album']['album'], $page, $title );
			preg_match( $this->_def['regex']['album']['year'], $page, $year );
			
			$artist[1] = str_replace( $this->def['strip']['result'], '', $artist[1] );
			$title[1] = str_replace( $this->def['strip']['result'], '', $title[1] );

			$album = array(
				'gmalbumID' => $albumID,
				'artist' => $api->stringDecode( $artist[1] ),
				'title' => $api->stringDecode( $title[1] ),
				'year' => $api->stringDecode( $year[1] ),
				'genre' => '',
				'url' => sprintf( $this->_def['url']['album'], $albumID ) );

			if ( $this->debug ) var_dump( $album );

			$album['artist'] = str_replace( $this->_def['replace']['from'],
				$this->_def['replace']['to'], $album['artist'] );
			
			if ( empty( $album['title'] ) )
			{
				if ( $nRows >= 1 )
					return $row;
				return false;
			}
			
			if ( $nRows >= 1 )
				$api->db->update( 'googlemusic_album', $album, array( 'gmalbumID' => $row->gmalbumID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'googlemusic_album', $album, __FILE__, __LINE__ );
						
			return (object)$album;
		}
		else
		{
			$this->error = 'google.com music request fauled';
			return false;
		}
	}
}

?>
