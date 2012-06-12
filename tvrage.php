<?php

/**************************************************
 * NZBed
 * Copyright (c) 2008 Harry Bragg
 * tiberious.org
 * Module: tvrage
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

require_once( 'XML/Unserializer.php' );

class tvrage
{
	/**
	 * Lists the definitions for tvrage website
	 *
	 * @var array of regular expressions
	 * @access public
	 */
	var $_def = array(
		'url' => array(
			'search' => 'http://www.tvrage.com/feeds/search.php?show=%s',
			'episodeList' => 'http://www.tvrage.com/feeds/episode_list.php?sid=%d',
			'show' => 'http://www.tvrage.com/feeds/showinfo.php?sid=%d',
			'base' => 'http://www.tvrage.com',
		),
		'regex' => array(
			'epID' => '/\/(\d+)$/i',
			'showID' => '/^shows\/id(?:-|\s)(\d+)$/i',
			'cast' => "/&bull;<i><a href='.+?\/id-(\d+)\/.+?' >(.+?)<\/a><\/i>(?:<\/span>)?<\/td><td.+? style='[^']+'><b>(As|Voiced)<\/b><\/td><td.+?>(?:<div.+?>)?<i>(.+?)<\/i>/i",
			'crew' => "/<tr ><td width='\d+' valign='top' class='b2'>&bull; <b>(.+?)<\/td><td class='b2'>(.+?)<\/td><\/tr>/i",
			'crewnames' => "/(?:<b><font size='\d+'>&nbsp;&nbsp|&nbsp;&nbsp;<\/font><\/b>)?<a href='.+?\/id-(\d+)\/.+?' >(.+?)<\/a>/i"
		),
		'command' => array(
			'showEp'			=> '/^(.+) s?(\d+)(?:x|e)(\d+)/i',
			'showEpName'		=> '/^(.+), (.+)$/i'
		),
		'error' => '/Unknown Error/i'
	);

	var $debug = false;

	var $error;

	var $_fromXML;
	
	var $_full = false;

	/*****************************************************
	 * Main functions
	 *****************************************************/

	function tvrage( $castAndCrew = false )
	{
        $options = array(
            XML_UNSERIALIZER_OPTION_RETURN_RESULT    => true,
            XML_UNSERIALIZER_OPTION_FORCE_ENUM       => array(
                'genre',
				'show',
				'episode',
				'Season'
            ),
			XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE => true,
        );

        $this->_fromXML = &new XML_Unserializer( $options );
				
		$this->_full = $castAndCrew;
	}
	
	function setCastAndCrew( $value )
	{
		$this->_full = $value;
	}
	
	/**
	 * GetXmlUrl
	 *
	 * @param string $url - url to get
	 * @return the contents in xml format
	 * @access private
	 */
	function getXmlUrl( $url )
	{
		if ( $this->debug ) printf( "  Get XML URL: %s\n", $url );
		
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			// parse the xml
	        $xmlData = $this->_fromXML->unserialize( $page );
	        if ( PEAR::isError( $xmlData ) )
	        {
				if ( $this->debug ) printf("   XML UnSerialization failed\n" );

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
	
	/**
	 * Get URL
	 *
	 * @param string $url - url to get
	 * @return contents of the page
	 * @access public
	 */
	function getUrl( $url )
	{
		if ( $this->debug ) printf( "  Get URL: %s\n", $url );
		$req =& new HTTP_Request( );
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setURL( $url, array( 'timeout' => '5', 'readTimeout' => 10, 'allowRedirects' => true ) );
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
	
	/**
	 * look for a show
	 *
	 * @param string $query - Show search query
	 * @return int - tvrage.com showid
	 * @access public
	 */
	function findShow( $query, $ignoreCache = false )
	{
		if ( $this->debug ) printf("findShow( query:%s, ignoreCache:%d )\n", $query, $ignoreCache );

		global $api;
		
		$res = $api->db->select( '*', 'tvrage_search', array('search' => $query ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		// check the cache
		if ( $nRows > 0 )
        {
            $row = $api->db->fetch( $res );
            if ( $row->ftvrageShowID > 0)
            {
				if ( $this->debug ) printf( " using forced ID: %d ", $row->ftvrageShowID );
                return $row->ftvrageShowID;   
            }
            else if ( ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			     ( $ignoreCache == false ) )
            {
			if ( $this->debug ) printf( " using cached ID: %d ", $row->tvrageShowID );
                return $row->tvrageShowID; 
            }
        }
                 
		// find tv show
		$url = sprintf( $this->_def['url']['search'], urlencode( $query ) );
		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{
			if ( $xpage == 0 )
				return false;

			if ( isset( $xpage['show'][0]['showid'] ) )
			{
				$showid = $xpage['show'][0]['showid'];
				if ( $this->debug ) printf(" found a show, id: %d\n", $showid );
				//if ( $this->debug ) var_dump( $xpage );
				if ( $nRows >= 1 )
					$api->db->update( 'tvrage_search', array( 'tvrageShowID' => $showid ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'tvrage_search', array( 'tvrageShowID' => $showid, 'search' => $query ), __FILE__, __LINE__ );
				return $showid;
			}
			else
			{
				return false;
			}
		}
		else
		{
			if ( $nRows > 0 )
				return $row->tvrageShowID;

			$this->error = "Tvrage timed out, try again later.";
			return false;
		}
	}
	
	function getFShow( $query, $ignoreCache = false )
	{
		if ( ( $showID = $this->findShow( $query, $ignoreCache ) ) !== false )
		{
			return $this->getShow( $showID, $ignoreCache );
		}
		else
			return false;
	}
	
	/**
	 * @param string $tvin - tvrage showID
	 * @return array - Show information
	 * @access public
	 */
	function getShow( $showID, $ignoreCache = false, $tvID = false )
	{
		if ( $this->debug ) printf("getShow( showID:%s, ignoreCache:%d )\n", $showID, $ignoreCache );

		global $api;

		if ( $tvID == true )
		{
			// check regex
			if ( preg_match( $this->_def['regex']['showID'], $showID, $match ) )
			{
				$showID = $match[1];
			}
			else
			{
				if ( ( $showID = $this->findShow( $showID, $ignoreCache ) ) === false )
					return false;				
			}			
		}

		$res = $api->db->select( '*', 'tvrage_show', array( 'tvrageShowID' => $showID ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		if ( $nRows >= 1 )
			$row = $api->db->fetch( $res );
	
		// check cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" using tv show cache\n");
			$this->getShowCastAndCrew( $row );
			return $row;
		}
			
		$url = sprintf( $this->_def['url']['show'], $showID );
		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{
			if ( $this->debug ) var_dump( $xpage );

			if ( count( $xpage ) == 0 )
			{
				if ( $this->debug ) printf(" error getting tv show\n");
				return false;
			}

			$genres = ( count( $xpage['genres']['genre'] ) > 0 )? implode( ' | ', $xpage['genres']['genre'] ):'';
					
			$show = array(
				'tvrageShowID' => $showID,
				'name' => $xpage['showname'],
				'genre' => $genres,
				'class' => $xpage['classification'],
				'url' => $xpage['showlink'] );

			if ( $this->debug ) var_dump( $show );

			if ( empty( $show['name'] ) )
			{
				if ( $nRows >= 1 )
				{
					$this->getShowCastAndCrew( $row );
					return $row;
				}
				return false;
			}
				
			if ( $nRows >= 1 )
			{
				if ( !empty( $row->nzbName ) )
					$show['nzbName'] = rtrim( $row->nzbName );
				if ( !empty( $row->nzbGenre ) )
					$show['nzbGenre'] = $row->nzbGenre;
				$show['usenetToTvrage'] = $row->usenetToTvrage;
				$show['tvrageToNewzbin'] = $row->tvrageToNewzbin;
			}
			
			if ( $nRows >= 1 )
			{
				$api->db->update( 'tvrage_show', $show, array( 'showID' => $row->showID ), __FILE__, __LINE__ );
			}
			else
				$api->db->insert( 'tvrage_show', $show, __FILE__, __LINE__ );
				
			$oshow = (object)$show;
			
			$this->getShowCastAndCrew( $oshow, false );
						
			return $oshow;
		} else {
			if ( $nRows > 0 )
			{
				$this->getShowCastAndCrew( $row );
				return $row;
			}

			$this->error = sprintf("Tvrage getShow timed out, try again later.", $url );
			return false;
		}

	}

	/**
	 * look for a episode
	 *
	 * @param string showID - tvrage.com showid
	 * @param int $series - Series Number
	 * @param int $episode - Episode in $series number
	 * @return int - episodeID
	 * @access public
	 */
	function findEpisode( $showID, $series, $episode )
	{	
		if ( $this->debug ) printf("findEpisode( showID:%s, series:%s, episode:%d )\n", $showID, $series, $episode );

		// download series page
		$url = sprintf( $this->_def['url']['episodeList'], $showID );

		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{

			if ( $series == 'S' )
			{
				if ( isset( $xpage['Episodelist']['Special']['episode'][$episode-1] ) )
				{
					$ep = $xpage['Episodelist']['Special']['episode'][$episode-1];
					$ep['seasonnum'] = $episode;
				}
			}		
			else
			{
				if ( isset( $xpage['Episodelist']['Season'] ) )
				{
					// loop through series
					foreach( $xpage['Episodelist']['Season'] as $s )
					{
						$sNum = $s['no'];
		
						if ( $this->debug ) printf(" current series: %d\n", $sNum );
			
						if ( $sNum == $series )
						{
		                    if ( ( $e = $this->findMatch( $s['episode'], 'seasonnum', "$episode" ) ) !== false )
		                    {
		                        if ( $this->debug ) printf(" found Episode for %d - %sx%02d\n", $showID, $series, $episode );
		
		                        return $e;
		                    }
						}
					}
				}
			}
		}
		else
		{
			$this->error = "Tvrage timed out, try again later.";
		}
		return false;
	}

    /**
     * @param string $showid - tvrage showid
     * @param int $date - date to start looking on 
     * @return array - array of Episode information
     * @access public
     */    
    function findEpisodeFromDate( $showid, &$series, $date, $ignoreCache = false )
    {
		if ( $this->debug ) printf("findEpisodeFromDate( showid:%s, date:%d, ignoreCache:%d )\n", $showid, $date, $ignoreCache );	

        // download series page
        $url = sprintf( $this->_def['url']['episodeList'], $showid );

        if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{

	        if ( isset( $xpage['Episodelist']['Special'] ) )
	        {
				$series = 'S';
	
				if ( ( $e = $this->findSpecialMatch( $xpage['Episodelist']['Special']['episode'], 'airdate', date('Y-m-d', $date ) ) ) !== false )
	            {
			    	if ( $this->debug ) printf(" found Episode for %d - %s\n", $showid, date('Y-m-d', $date ) );
	
	                return $e;
	            }
	        }
	        // loop through series
			if ( isset( $xpage['Episodelist']['Season'] ) )
			{
	            foreach( $xpage['Episodelist']['Season'] as $s )
	            {
	                $series = $s['no'];
		
					if ( ( $e = $this->findMatch( $s['episode'], 'airdate', date('Y-m-d', $date ) ) ) !== false )
					{
	                    if ( $this->debug ) printf(" found Episode for %d - %s\n", $showid, date('Y-m-d', $date ) );
		
	   	            	return $e;
		            }
				}
			}
			else
			{
				if ( $this->debug ) printf(" Failed to find any seasons\n");
				if ( $this->debug ) print_r( $xpage );
				$this->error = sprintf("Tvrage: Unable to find any seasons for showID: %s", $showid );
	        }
		}
		else
		{
			$this->error = "Tvrage timed out, try again later.";
		}
        return false;
    }

    /**
     * @param string $showid - tvrage showid
     * @param int $date - date to start looking on
     * @return array - array of Episode information
     * @access public
     */
    function findEpisodeFromID( $showID, &$series, $epid, $ignoreCache = false )
    {
        if ( $this->debug ) printf("findEpisodeFromID( showid:%s, epid:%d, ignoreCache:%d )\n", $showID, $epid, $ignoreCache );

        // download series page
        $url = sprintf( $this->_def['url']['episodeList'], $showID );

        if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{

	        if ( isset( $xpage['Episodelist']['Special'] ) )
	        {
				$series = 'S';
	
				if ( ( $e = $this->findSpecialMatch( $xpage['Episodelist']['Special']['episode'], 'link', sprintf("/\/%d$/i", $epid ), true ) ) !== false )
	            {
	                if ( $this->debug ) printf(" found Episode for %d - id:%d\n", $showID, $epid );
	
	                return $e;
	            }
	        }
			if ( isset( $xpage['Episodelist']['Season'] ) )
			{
	            // loop through series
	            foreach( $xpage['Episodelist']['Season'] as $s )
	            {
	                $series = $s['no'];
	
					if ( $this->debug ) printf(" Series: %s\n", $series );
	
					if ( ( $e = $this->findMatch( $s['episode'], 'link', sprintf('/\/%d$/i', $epid ), true ) ) !== false )
		            {
		                if ( $this->debug ) printf(" found Episode for %d - id:%d\n", $showID, $epid );
	
			            return $e;
		            }
	            }
			}
        }
		else
		{
			$this->error = "Tvrage timed out, try again later.";
		}
        return false;
    }

	/**
	 * findMatch
	 *
	 * @param array $eps - List of episodes from xml
	 * @param string $field - field name to match
	 * @param mixed $value - value to match against
	 * @return mixed - matching episode or false
	 */
	function findMatch( $eps, $field, $value, $regex = false )
	{
		foreach( $eps as $ep )
		{
			if ( $this->debug ) printf(" field:%s %s:%s [r:%d]?\n", $field, $ep[$field], $value, $regex );
			if ( $regex )
			{
				if ( preg_match( $value, $ep[$field] ) )
				{
					return $ep;
				}
			}
			else if ( $ep[$field] == $value )
				return $ep;
		}
		return false;
	}

	/*
	 * findSpecialMatch
     *
     * @param array $eps - List of episodes from xml
     * @param string $field - field name to match
     * @param mixed $value - value to match against
     * @return mixed - matching episode or false
     */
    function findSpecialMatch( $eps, $field, $value, $regex = false )
    {
        for( $i=0; $i < count( $eps ); $i++)
		{
			$ep = $eps[$i];
            if ( $this->debug ) printf(" field:%s %s:%s [r:%d]?\n", $field, $ep[$field], $value, $regex );
            if ( $regex )
            {
                if ( preg_match( $value, $ep[$field] ) )
                {
					$ep['seasonnum'] = $i+1;
                    return $ep;
                }
            }
            else if ( $ep[$field] == $value )
			{
				$ep['seasonnum'] = $i+1;
                return $ep;
			}
        }
        return false;
    }

    
	/**
	 * @param string $show - tvrage showID
	 * @param int $id - episodeID
	 * @return array - Episode information
	 * @access public
	 */	
	function getEpisode( $showID, $series, $episode, $ignoreCache = false )
	{
		// trim series
		$series = sprintf("%d", $series );
	
		if ( $this->debug ) printf("getEpisode( showID:%s, series:%s, episode:%d, ignoreCache:%d )\n", $showID, $series, $episode, $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvrage_episode', array( 'tvrageShowID' => $showID, 'series' => $series, 'episode' => $episode ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		if ( $nRows > 0 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{			
			if ( $this->debug ) printf(" using episode cache\n" );
			$this->getEpCastAndCrew( $row, true );
			return $row;
		}

		if ( ( $rawEp = $this->findEpisode( $showID, $series, $episode ) ) !== false )
		{

			$ep = $this->getEpisodeInfo( $showID, $series, $rawEp );
	
			if ( $this->debug ) var_dump( $ep );
	
			if ( empty( $ep['title'] ) )
			{
				if ( $nRows >= 1 )
				{
					$this->getEpCastAndCrew( $row );
					return $row;
				}
				else
				{
					return false;
				}
			}
	
			if ( $nRows >= 1 )
				$api->db->update( 'tvrage_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'tvrage_episode', $ep, __FILE__, __LINE__ );
				
			$oep = (object)$ep;
			
			$this->getEpCastAndCrew( $oep, false );
						
			return $oep;
		} else {
			if ( $nRows > 0 )
			{
				$this->getEpCastAndCrew( $row, true );
				return $row;
			}
			return false;
		}
	}

	/**
	 * getEpisodeInfo
	 *
	 * @param int $showID - tvrage showID
	 * @param int $season - season
	 * @param array $ep - array of information
	 * @return array - custom episode information
	 * @access public
	 */
	function getEpisodeInfo( $showID, $season, $ep )
	{
		if ( $this->debug ) printf("getEpisodeInfo( showID:%d, season:%s, ep:<> )\n", $showID, $season );
		if ( $this->debug ) print_r( $ep );

		preg_match( $this->_def['regex']['epID'], $ep['link'], $epID );

		// check if season is string for int, set to string remove any 0's
		if ( is_numeric( $season ) )
			$season = sprintf("%d", $season );

        $ep = array(
            'tvrageEpisodeID' => $epID[1],
            'tvrageShowID' => $showID,
            'title' => $ep['title'],
            'series' => $season,
            'episode' => $ep['seasonnum'],
            'date' => strtotime( $ep['airdate'] ),
//						'airdate' => $ep['airdate'],
//						'episodenum' => $ep['epnum'],
           // 'url' => sprintf( '%s/%sx%02d/', $ep['link'], $season, $ep['seasonnum'] ) );
            'url' => sprintf( '%s/', $ep['link'] ) ); 
		
		return $ep;
	}

	/**
	 * @param string $show - tvrage showID
	 * @param int $id - episodeID
	 * @return array - Episode information
	 * @access public
	 */	
	function getIDEpisode( $showID, $episodeID, $ignoreCache = false )
	{
		if ( $this->debug ) printf( "getIDEpisode( showID:%s, episodeID:%d, ignoreCache:%d )\n", $showID, $episodeID, $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvrage_episode', array( 'tvrageShowID' => $showID, 'tvrageEpisodeID' => $episodeID ), __FILE__, __LINE__ );
		
		$nRows = $api->db->rows( $res );
		
		$id = $episodeID;
	
		if ( $nRows == 1 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
		     ( mt_rand(1, 100) <= (100 * 0.9) ) &&
			 ( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" using episode cache\n" );			
			$this->getEpCastAndCrew( $row, true );
			return $row;
		}

		$series = '';

        if ( ( $rawEp = $this->findEpisodeFromID( $showID, $series, $episodeID, $ignoreCache ) ) !== false )
        {
            $ep = $this->getEpisodeInfo( $showID, $series, $rawEp );

            if ( $this->debug ) var_dump( $ep );

            if ( empty( $ep['title'] ) )
            {
                if ( $nRows >= 1 )
                {
									$this->getEpCastAndCrew( $row, true );
                  return $row;
                }
                else
                {
                    return false;
                }
            }

            if ( $nRows >= 1 )
                $api->db->update( 'tvrage_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
            else
                $api->db->insert( 'tvrage_episode', $ep, __FILE__, __LINE__ );

						$oep = (object)$ep;
						
						$this->getEpCastAndCrew( $oep, false );
						
            return $oep;
        } else {
					if ( $nRows > 0 )
					{
						$this->getEpCastAndCrew( $row, true );
						return $row;
					}
				
          return false;
        }
	}

    /**
     * @param string $show - tvrage showID
     * @param int $id - episodeID
     * @return array - Episode information
     * @access public
     */
    function getDateEpisode( $showID, $date, $ignoreCache = false )
    {
        if ( $this->debug ) printf( "getDateEpisode( showID:%s, date:%s, ignoreCache:%d )\n", $showID, date('Y-m-d', $date ), $ignoreCache );

        global $api;

        $res = $api->db->select( '*', 'tvrage_episode', array( 'tvrageShowID' => $showID, 'date' => $date ), __FILE__, __LINE__ );

        $nRows = $api->db->rows( $res );

		if ( $nRows > 0 )
			$row = $api->db->fetch( $res );

        // check cache
        if ( ( $nRows >= 1 ) &&
             ( mt_rand(1, 100) <= (100 * 0.9) ) &&
             ( $ignoreCache == false ) )
        {
            if ( $this->debug ) printf(" using episode cache\n" );
						$this->getEpCastAndCrew( $row );
            return $row;
        }

        $series = 0;

        if ( ( $rawEp = $this->findEpisodeFromDate( $showID, $series, $date ) ) !== false )
        {
            $ep = $this->getEpisodeInfo( $showID, $series, $rawEp );

            if ( $this->debug ) var_dump( $ep );

            if ( empty( $ep['title'] ) )
            {
                if ( $nRows >= 1 )
                {
									$this->getEpCastAndCrew( $row, true );
                    return $row;
                }
                else
                {
                    return false;
                }
            }
						
						// set the type
						$ep['type'] = 'date';

            if ( $nRows >= 1 )
                $api->db->update( 'tvrage_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
            else
                $api->db->insert( 'tvrage_episode', $ep, __FILE__, __LINE__ );
						
						$oep = (object)$ep;
						
						$this->getEpCastAndCrew( $oep, false );

            return $oep;
        } else {
					if ( $nRows > 0 )
					{
						$this->getEpCastAndCrew( $row, true );
						return $row;
					}

          return false;
        }
    }
		
		/**
		 * @param ref object - Episode information
		 * @access public
		 **/
		function getEpCastAndCrew( &$ep, $useCache = true)
		{
			if ( $this->_full )
			{
				if ( $this->debug ) printf("getEpCastAndCrew( ep, useCache:%d )\n", $useCache);
				
				$this->getCast( $ep, 'tvrage_episode_cast', 'episodeID', $ep->episodeID, $useCache );
				$this->getCrew( $ep, 'tvrage_episode_crew', 'episodeID', $ep->episodeID, $useCache );
			}
 		}
		
		/**
		 * @param ref object - Show information
		 * @access public
		 **/
		function getShowCastAndCrew( &$show, $useCache = true)
		{
			if ( $this->_full )
			{
				if ( $this->debug ) printf("getShowCastAndCrew( show, useCache:%d )\n", $useCache);
				
				$this->getCast( $show, 'tvrage_show_cast', 'showID', $show->showID, $useCache );
				$this->getCrew( $show, 'tvrage_show_crew', 'showID', $show->showID, $useCache );
			}
		}

		/**
		 * @param ref obj object - Episode/Show information
		 * @param table string - Name of the table to store information in
		 * @param idname string - Name of the idvalue to store against
		 * @param id integer - Value of the id to store against
		 * @param useCache boolean [default: true] - use cache by default
		 * @access private
		 **/		
		function getCast( &$obj, $table, $idname, $id, $useCache = true )
		{
			if ( $this->debug ) printf("getCast( obj, table:%s, idname:%s, id:%d, useCache:%d )\n", $table, $idname, $id, $useCache );
			
			global $api;
			
			// check cache
			$res = $api->db->select( '*', $table, array( $idname => $id ) , __FILE__, __LINE__ );
																												 
			$nRows = $api->db->rows( $res );
			
			$cast = array();
			
			if ( ( $useCache ) && ( $nRows > 0 ) )
			{
				// return cache select
				while ( $row = $api->db->fetch( $res ) )
				{
					$obj->cast[] = array(
						'name' => $row->name,
						'character' => $row->character,
						'type' => $row->type
					);
				}				
			}
			else
			{
				$page = $this->getUrl( $obj->url );
				
				// cast search
				if ( preg_match_all( $this->_def['regex']['cast'], $page, $matches ) > 0 )
				{
					if ( $this->debug ) print_r( $matches );
					
					// clear cache
					$api->db->delete( $table, array( $idname => $id ), __FILE__, __LINE__ );
					
					for( $i = 0; $i < count( $matches[0] ); $i++ )
					{
						$characters = explode( ',', $matches[4][$i] );
						for ( $j = 0; $j < count( $characters ); $j++ )
						{
							$a = array(
								'name' => $matches[2][$i],
								'character' => $characters[$j],
								'type' => $matches[3][$i]
							);
							$obj->cast[] = $a;
							
							$a[$idname] = $id;
							
							$api->db->insert( $table, $a, __FILE__, __LINE__ );
						}
					}
				}
			}
		}
		
		/**
		 * @param ref obj object - Episode/Show information
		 * @param table string - Name of the table to store information in
		 * @param idname string - Name of the idvalue to store against
		 * @param id integer - Value of the id to store against
		 * @param useCache boolean [default: true] - use cache by default
		 * @access private
		 **/		
		function getCrew( &$obj, $table, $idname, $id, $useCache = true )
		{
			if ( $this->debug ) printf("getCrew( obj, table:%s, idname:%s, id:%d, useCache:%d )\n", $table, $idname, $id, $useCache );
			
			global $api;
			
			// check cache
			$res = $api->db->select( '*', $table, array( $idname => $id ), __FILE__, __LINE__ );
																												 
			$nRows = $api->db->rows( $res );
			
			$cast = array();
			
			if ( ( $useCache ) && ( $nRows > 0 ) )
			{
				// return cache select
				while ( $row = $api->db->fetch( $res ) )
				{
					$obj->crew[] = array(
						'name' => $row->name,
						'role' => $row->role
					);
				}				
			}
			else
			{
				$page = $this->getUrl( $obj->url );
				
				// cast search
				if ( preg_match_all( $this->_def['regex']['crew'], $page, $matches ) > 0 )
				{
					if ( $this->debug ) print_r( $matches );
					
					// clear cache
					$api->db->delete( $table, array( $idname => $id ), __FILE__, __LINE__ );
					
					for( $i = 0; $i < count( $matches[0] ); $i++ )
					{
						if ( preg_match_all( $this->_def['regex']['crewnames'], $matches[2][$i], $crew ) > 0 )
						{
							if ( $this->debug ) print_r( $crew );
							for ( $j = 0; $j < count( $crew[0] ); $j++ )
							{
								$a = array(
									'role' => $matches[1][$i],
									'name' => $crew[2][$j]
								);
								
								$obj->crew[] = $a;
								
								$a[$idname] = $id;
								
								$api->db->insert( $table, $a, __FILE__, __LINE__ );
							}
						}
					}
				}
			}
		}
}

?>
