<?php

require_once( 'HTTP/Request.php' );
require_once( 'XML/Unserializer.php' );

class tvrage
{
	/**
	 * Lists the definitions for tvrage website
	 *
	 * @var array of regular expressions
	 * @access public    (?<![a-z0-9])(2cds|2cd|2 cds|2 cd)(?![a-z0-9])
	 */
	var $_def = array(
			'myname' => 'tvrage',
			'myurl' => '/^(?:http:\/\/)?(?:www\.)?tvrage\.com\/(.+?)\/episodes\/(\d+)/i',
			'url' => array(
					'search' => 'http://www.tvrage.com/feeds/search.php?show=%s',
					'episodeList' => 'http://www.tvrage.com/feeds/episode_list.php?sid=%d',
					'show' => 'http://www.tvrage.com/feeds/showinfo.php?sid=%d',
					'base' => 'http://www.tvrage.com',
			),
			'regex' => array(
					'epID' => '/\/(\d+)$/i',
					'showID' => '/^shows\/id(?:-|\s)(\d+)$/i',
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

	/*****************************************************
	 * Main functions
	*****************************************************/

	function tvrage()
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
			if ( $row->ftvShowID > 0)
			{
				if ( $this->debug ) printf( " using forced ID: %d ", $row->ftvShowID );
				return $row->ftvShowID;
			}
			else if ( ( mt_rand(1, 100) <= (100 * 0.9) ) &&
					( $ignoreCache == false ) )
			{
				if ( $this->debug ) printf( " using cached ID: %d ", $row->tvShowID );
				return $row->tvShowID;
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
					$api->db->update( 'tvrage_search', array( 'tvShowID' => $showid ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'tvrage_search', array( 'tvShowID' => $showid, 'search' => $query ), __FILE__, __LINE__ );
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
				return $row->tvShowID;

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

		$res = $api->db->select( '*', 'tvrage_show', array( 'tvShowID' => $showID ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );

		if ( $nRows >= 1 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
				( mt_rand(1, 100) <= (100 * 0.9) ) &&
				( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" using tv show cache\n");
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
					'tvShowID' => $showID,
					'name' => $xpage['showname'],
					'genre' => $genres,
					'class' => $xpage['classification'],
					'url' => $xpage['showlink'] );

			if ( $this->debug ) var_dump( $show );

			if ( empty( $show['name'] ) )
			{
				if ( $nRows >= 1 )
				{
					return $row;
				}
				return false;
			}

			if ( $nRows >= 1 )
			{
				$api->db->update( 'tvrage_show', $show, array( 'showID' => $row->showID ), __FILE__, __LINE__ );
			}
			else
				$api->db->insert( 'tvrage_show', $show, __FILE__, __LINE__ );

			$oshow = (object)$show;
			return $oshow;
		} else {
			if ( $nRows > 0 )
			{
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

		$res = $api->db->select( '*', 'tvrage_episode', array( 'tvShowID' => $showID, 'series' => $series, 'episode' => $episode ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );

		if ( $nRows > 0 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
				( mt_rand(1, 100) <= (100 * 0.9) ) &&
				( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" using episode cache\n" );
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
			return $oep;
		} else {
			if ( $nRows > 0 )
			{
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
				'tvShowID' => $showID,
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
	function getIDEpisode( $showID, $matches, $ignoreCache = false )
	{
		$episodeID = $matches[2];
		if ( $this->debug ) printf( "getIDEpisode( showID:%s, episodeID:%d, ignoreCache:%d )\n", $showID, $episodeID, $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvrage_episode', array( 'tvShowID' => $showID, 'tvrageEpisodeID' => $episodeID ), __FILE__, __LINE__ );

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
			return $oep;
		} else {
			if ( $nRows > 0 )
			{
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

		$res = $api->db->select( '*', 'tvrage_episode', array( 'tvShowID' => $showID, 'date' => $date ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );

		if ( $nRows > 0 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
				( mt_rand(1, 100) <= (100 * 0.9) ) &&
				( $ignoreCache == false ) )
		{
			if ( $this->debug ) printf(" using episode cache\n" );
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
			return $oep;
		} else {
			if ( $nRows > 0 )
			{
				return $row;
			}

			return false;
		}
	}

	function geturlregex(){
		return $this->_def['myurl'];
	}

	function ismyurl( $url ){
		if( preg_match($this->_def['myurl'], $url ) != false )
		{
			return true;
		}else{
			return false;
		}
	}
	
	function getName(){
		return $this->_def['myname'];
	}
}

?>
