<?php
require_once( 'HTTP/Request.php' );
require_once( 'XML/Unserializer.php' );

class tvdb{
	var $debug = false;
	var $error;
	var $_fromXML;
	var $_def = array(
			'myname' => 'tvdb',
			'myurl' => '/^(?:http:\/\/)?(?:www\.)?thetvdb\.com\/(?:.+?)seriesid=(\d+)&seasonid=(\d+)&id=(\d+)/i',
			'url' => array(
					'search' => 'http://www.thetvdb.com/api/GetSeries.php?seriesname=%s&language=en',
					'episode' => 'http://www.thetvdb.com/api/A8791054920688B4/series/%d/default/%d/%d/en.xml',
					'show' => 'http://www.thetvdb.com/api/A8791054920688B4/series/%d/',
					'urlepisode' => 'http://www.thetvdb.com/api/A8791054920688B4/episodes/%d/en.xml',
					'dateshow' => 'http://www.thetvdb.com/api/GetEpisodeByAirDate.php?apikey=A8791054920688B4&seriesid=%d&airdate=%s&language=en',
					'linkfull' => 'http://www.thetvdb.com/?tab=episode&seriesid=%d&seasonid=%d&id=%d',
					'linkshow' => 'http://thetvdb.com/?tab=series&id=%d'
			),
			'regex' => array(
					'epID' => '/^(\d+)$/i',
					'showID' => '/^(\d+)$/i',
					'seasonID' => '/^(\d+)$/i'
			),
			'command' => array(
					'showEp'			=> '/^(.+) s?(\d+)(?:x|e)(\d+)/i',
					'showEpName'		=> '/^(.+), (.+)$/i'
			),
			'error' => '/Unknown Error/i'
	);

	function tvdb(){
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

	function findShow( $query, $ignoreCache = false )
	{
		if ( $this->debug ) printf("findShow TVDB( query:%s, ignoreCache:%d )\n", $query, $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvdb_search', array('search' => $query ), __FILE__, __LINE__ );

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
			//print_r($xpage);
			if ( isset( $xpage['Series'][0]['seriesid'] ) )
			{
				$showid = $xpage['Series'][0]['seriesid'];
				if ( $this->debug ) printf(" found a show, id: %d\n", $showid );
				//if ( $this->debug ) var_dump( $xpage );
				if ( $nRows >= 1 )
					$api->db->update( 'tvdb_search', array( 'tvShowID' => $showid ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'tvdb_search', array( 'tvShowID' => $showid, 'search' => $query ), __FILE__, __LINE__ );
				return $showid;
			}
			else if(isset($xpage['Series']['seriesid']))
			{
				$showid = $xpage['Series']['seriesid'];
				if ( $this->debug ) printf(" found a show, id: %d\n", $showid );
				//if ( $this->debug ) var_dump( $xpage );
				if ( $nRows >= 1 )
					$api->db->update( 'tvdb_search', array( 'tvShowID' => $showid ), array( 'search' => $query ), __FILE__, __LINE__ );
				else
					$api->db->insert( 'tvdb_search', array( 'tvShowID' => $showid, 'search' => $query ), __FILE__, __LINE__ );
				return $showid;
			}else
			{
				return false;
			}
		}
		else
		{
			if ( $nRows > 0 )
				return $row->tvShowID;

			$this->error = "Tvdb timed out, try again later.";
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

		$res = $api->db->select( '*', 'tvdb_show', array( 'tvShowID' => $showID ), __FILE__, __LINE__ );

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
			if ( $this->debug ) print_r( $xpage );

			if ( count( $xpage ) == 0 )
			{
				if ( $this->debug ) printf(" error getting tv show\n");
				return false;
			}

			$genres = trim($xpage['Series']['Genre'], '|');

			$show = array(
					'tvShowID' => $showID,
					'name' => $xpage['Series']['SeriesName'],
					'genre' => $genres,
					'class' => '',
					'url' => sprintf($this->_def['url']['linkshow'], $showID) );

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
				$api->db->update( 'tvdb_show', $show, array( 'showID' => $row->showID ), __FILE__, __LINE__ );
			}
			else
				$api->db->insert( 'tvdb_show', $show, __FILE__, __LINE__ );

			$oshow = (object)$show;
			return $oshow;
		} else {
			if ( $nRows > 0 )
			{
				return $row;
			}

			$this->error = sprintf("Tvdb getShow timed out, try again later.", $url );
			return false;
		}
	}

	function getEpisodeInfo( $showID, $season, $ep )
	{
		if ( is_numeric( $season ) )
			$season = sprintf("%d", $season );
		if ( $this->debug ) printf("getEpisodeInfo( showID:%d, season:%d, ep:%d )\n", $showID, $season, $ep );
		$url = sprintf($this->_def['url']['episode'], $showID, $season, $ep);
		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false ){
			$ep = array(
					'tvdbEpisodeID' => $xpage['Episode']['id'],
					'tvShowID' => $showID,
					'series' => $season,
					'episode' => $ep,
					'date' => '0',
					'title' => $xpage['Episode']['EpisodeName'],
					'url' => sprintf( $this->_def['url']['linkfull'], $showID, $xpage['Episode']['seasonid'], $xpage['Episode']['id']) );
		}
		return $ep;
	}

	function getEpisode( $showID, $series, $episode, $ignoreCache = false )
	{
		// trim series
		$series = sprintf("%d", $series );

		if ( $this->debug ) printf("getEpisode( showID:%s, series:%s, episode:%d, ignoreCache:%d )\n", $showID, $series, $episode, $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvdb_episode', array( 'tvShowID' => $showID, 'series' => $series, 'episode' => $episode ), __FILE__, __LINE__ );

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

		if(($ep = $this->getEpisodeInfo( $showID, $series, $episode )) !=false)
		{
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
				$api->db->update( 'tvdb_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'tvdb_episode', $ep, __FILE__, __LINE__ );

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

	function getDateEpisode( $showID, $date, $ignoreCache = false )
	{
		if ( $this->debug ) printf( "getDateEpisode( showID:%s, date:%s, ignoreCache:%d )\n", $showID, date('Y-m-d', $date ), $ignoreCache );

		global $api;

		$res = $api->db->select( '*', 'tvdb_episode', array( 'tvShowID' => $showID, 'date' => $date ), __FILE__, __LINE__ );

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
		$url = sprintf($this->_def['url']['dateshow'], $showID, $date);
		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false )
		{

			$ep = array(
					'tvdbEpisodeID' => $xpage['Episode']['id'],
					'tvShowID' => $showID,
					'title' => $xpage['Episode']['EpisodeName'],
					'series' => $xpage['Episode']['SeasonNumber'],
					'episode' => $xpage['Episode']['EpisodeNumber'],
					'date' => strtotime( $xpage['Episode']['FirstAired'] ),
					'url' => sprintf( $this->_def['url']['linkfull'], $showID, $xpage['Episode']['seasonid'], $xpage['Episode']['id'] ) );
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
				$api->db->update( 'tvdb_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'tvdb_episode', $ep, __FILE__, __LINE__ );

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
	
	function getIDEpisodeInfo( $showID, $ep )
	{
		if ( is_numeric( $season ) )
			$season = sprintf("%d", $season );
		if ( $this->debug ) printf("getEpisodeInfo(ep:%d )\n", $ep );
		$url = sprintf($this->_def['url']['urlepisode'], $ep);
		if ( ( $xpage = $this->getXmlUrl( $url ) ) !== false ){
			$ep = array(
					'tvdbEpisodeID' => $xpage['Episode']['id'],
					'tvShowID' => $showID,
					'series' => $xpage['Episode']['SeasonNumber'],
					'episode' => $xpage['Episode']['EpisodeNumber'],
					'date' => '0',
					'title' => $xpage['Episode']['EpisodeName'],
					'url' => sprintf( $this->_def['url']['linkfull'], $showID, $season, $ep) );
		}
		return $ep;
	}

	function getIDEpisode( $showID, $matches, $ignoreCache = false )
	{
		$episodeID = $matches[3];
		$series = $matches[2];
		if ( $this->debug ) printf( "getIDEpisode( showID:%s, seasonID:%d, episodeID:%d, ignoreCache:%d )\n", $showID, $series, $episodeID, $ignoreCache );
		global $api;
		$res = $api->db->select( '*', 'tvdb_episode', array( 'tvShowID' => $showID, 'tvdbEpisodeID' => $episodeID ), __FILE__, __LINE__ );

		$nRows = $api->db->rows( $res );

		$id = $episodeID;

		if ( $nRows == 1 )
			$row = $api->db->fetch( $res );

		// check cache
		if ( ( $nRows >= 1 ) &&
				( mt_rand(1, 100) <= (100 * 0.9) ) &&
				( $ignoreCache == false ) )
		{
			if ( $this->debug ) print("using episode cache\n" );
			return $row;
		}

		if ( ( $ep = $this->getIDEpisodeInfo( $showID, $episodeID ) ) !== false )
		{
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
				$api->db->update( 'tvdb_episode', $ep, array( 'episodeID' => $row->episodeID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'tvdb_episode', $ep, __FILE__, __LINE__ );

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