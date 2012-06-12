<?php

/**************************************************
 * NZBirc v1
 * Copyright (c) 2006 Harry Bragg
 * tiberious.org
 * Module: imdb
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

class gamespot
{

	/**
	 * Lists the definitions for tvrage website
	 *
	 * @var array of regular expressions
	 * @access public
	 */
	var $_def = array(
		'url' => array(
			'search' => 'http://www.google.com/search?hl=en&q=%s+site:gamespot.com&btnI=I\'m+Feeling+Lucky',
			'game' => 'http://www.gamespot.com/%s',
			'details' => 'http://www.gamespot.com/%stech_info.html'
		),
		'regex' => array(
			'url' => array(
				'/<a href="http:\/\/.+?\.gamespot\.com\/(.+\/.+\/.+\/).*?">here<\/A>/i'
				//'/<p><b>Popular Titles<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i',
				//'/<p><b>Titles \(Exact Matches\)<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i',
				//'/<p><b>Titles \(Partial Matches\)<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i'
				),
			'game' => array(
				'title' => '/<div class="wrap">\s*<h2 class="module_title">(.+?)<\/h2>\s*<\/div>/i',
				'year' => '/<dt>Release Date:<\/dt>\s*<dd>\s*\w+\s+\d+,\s(\d{4})/i',
				'genre' => '/<div class="pt5">\s+Genre: <a href=".+?" class=".+?">(.+)<\/a>\s+<\/div>/i',
				'rating' =>	'/<dl class="main_score">\s+<dt><a href="[^"]+">([0-9.]+)<\/a><\/dd>/i',
				'description' => '/<p class="review deck">(.+)<\/p>\s+<p>/i',
				'platform' => '/^([^\/]+?)\//i',
				'class' => '/^[^\/]+\/([^\/]+)\//i'
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
	
	/**
	 * look for a game
	 *
	 * @param string $query - game search query
	 * @return string - gamespot Url
	 * @access public
	 */
	function findGame( $query, $ignoreCache = false )
	{
		global $api;
		
		$res = $api->db->select( '*', 'gamespot_search', array('search' => $query ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		// check the cache
		if ( $nRows >= 1 )
        {
            $row = $api->db->fetch( $res );
            if ( $row->fgsUrl != '')
            {
                return $row->fgsUrl;
            }
            else if ( ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			          ( $ignoreCache == false ) )
		    {
			    return $row->gsUrl;
		    }
        }
		
		if ( $this->debug ) echo 'Query: '.$query." \n";

		// find game
		$url = sprintf( $this->_def['url']['search'], urlencode(strtolower($query)) );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			foreach( $this->_def['regex']['url'] as $regex )
			{
				if ( preg_match( $regex, $page, $gsUrl) )
				{
					if ( $this->debug ) echo 'gsUrl: '.$gsUrl[1]." \n";
					if ( $nRows >= 1 )
						$api->db->update( 'gamespot_search', array( 'gsUrl' => $gsUrl[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
					else
						$api->db->insert( 'gamespot_search', array( 'gsUrl' => $gsUrl[1], 'search' => $query ), __FILE__, __LINE__ );
					return $gsUrl[1];
				}
			}
			return false;
		}
		else
		{
			return false;
		}
	}

	function getSGame( $query, $ignoreCache = false )
	{
		if ( ( $gsUrl = $this->findGame( $query, $ignoreCache ) ) !== false )
		{												
			return $this->getGame( $gsUrl, $ignoreCache );
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get Film
	 *
	 * @param string $tvin - tvrage showID
	 * @return array - Show information
	 * @access public
	 */
	function getGame( $gsUrl, $ignoreCache = false )
	{
		global $api;
		
		$res = $api->db->select( '*', 'gamespot_game', array( 'gsUrl' => $gsUrl ), __FILE__, __LINE__ );
		
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
			
		$url = sprintf( $this->_def['url']['game'], $gsUrl );
		$durl = sprintf( $this->_def['url']['details'], $gsUrl );
		if ( ( ( $page = $this->getUrl( $url, true ) ) !== false ) &&
		     ( ( $details = $this->getUrl( $durl, true ) ) !== false ) )
		{
			preg_match( $this->_def['regex']['game']['title'], $page, $title );
			preg_match( $this->_def['regex']['game']['year'], $details, $year );
			preg_match( $this->_def['regex']['game']['genre'], $details, $genre );
			preg_match( $this->_def['regex']['game']['platform'], $gsUrl, $platform );
			preg_match( $this->_def['regex']['game']['class'], $gsUrl, $class );
						
			$game = array(
				'gsUrl' => $gsUrl,
				'title' => $api->stringDecode( $title[1] ),
				'genre' => trim( $api->stringDecode( $genre[1] ).' '.$class[1] ),
				'year' => $api->stringDecode( $year[1] ),
				'platform' => $platform[1],
				'url' => $url );

			if ( $this->debug ) var_dump( $game );
			
			if ( empty( $game['title'] ) )
			{
				if ( $nRows >= 1 )
					return $row;
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
}

?>
