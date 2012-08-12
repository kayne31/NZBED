<?php
require_once( INCLUDEPATH.'gamespot.php' );

class games{
	var $_debug = false;
	
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $ed_def;
	var $_def = array(
			'regex' => array(
				'filter' => array(
				'/xbox360/i',
				'/PS3/',
				'/xbox/i',
				'/NTSC/i',
				'/PAL/i',
				'/EUR/',
				'/JPN/i',
				'/readnfo/i',
				'/xgd3/i',
				'/repack/i',
				'/jtag/i'
				)
			)
	);
	
	function search( $search, $type, $ignoreCache = false ){
		$filteredsearch = $this->filter( $search ); // run filter to clean up the search string
		if( !$ignoreCache )
		{// Use from cache if it's available
			foreach( $this->_exts_array as $ext )
			{
				if( ( $gameurl = $ext->checkCache( $filteredsearch ) ) !== false )
				{// Search done before and we have a Game URL
					if( $this->_debug ) printf( "game URL: %s Found\n", $gameurl );
					if( ( $game = $ext->getGamefromdb( $gameurl ) ) !== false ){
						$report = $this->buildReport( $search );
						if($type == 4)
						{
							$game->platform = 'pc';
						}
						return $this->gameGetReport( $game, $report );
					}
				}
			}
		}
		//if We are here either ignoreCache is true or the Movie wasn't in the cache
		//Let's start searching through extensions
		foreach( $this->_exts_array as $ext )
		{
			if( ( $game = $ext->search( $filteredsearch ) ) != false){
				$report = $this->buildReport( $search );
				if($type == 4)
				{
					$game->platform = 'pc';
				}
				return $this->gameGetReport( $game, $report );
			}		
		}		
		return false;// Could not find any matches
	}
	
	function buildReport( $string ){
		global $ed;
		$report = array();
		$fTitle = $string;
		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['Consoles'] ) )
			{
				$rString = $this->ed_def['report']['category']['Consoles'].$this->ed_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$ed->addAttr( $report, 'Consoles', $attr, $id );
							if ( $attr == 'ConsolePlatform' )
								$tAppr = $id;
						}
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$ed->addAttr( $report, 'Consoles', $attr, $id );
							if ( $attr == 'ConsolePlatform' )
								$tAppr = $id;
						}
					}
				}
			}
		}
		return $report;
	}
	
	function ismyurl( $url ){
		foreach( $this->_urlregex_array as $reg )
		{
			if( preg_match($reg, $url ) != false )
			{
				return true;
			}
		}
		return false;// not our url
	}
	
	function getfromurl( $url, $ignoreCache = false ){
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->ismyurl( $url ) )
			{
				$game = $ext->getgamefromurl( $url, $ignoreCache );
				$report = $this->buildReport( $url );
				return $this->gameGetReport( $game, $report );
			}
		}
		return false;
	}
	
	function gameGetReport( $game, $tmp )
	{
		global $ed;
		$report = array();

		if ( $ed->ids )
		{
			if ( $game->year > 0 )
				$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s (%d)', $game->title, $game->year );
			else
				$report[$this->ed_def['report']['fields']['title']] = $game->title;
			$report[$this->ed_def['report']['fields']['url']] = $game->gsUrl;
			$report[$this->ed_def['report']['fields']['category']] = ( $game->platform == 'pc' )? $this->ed_def['report']['category']['Games']:$this->ed_def['report']['category']['Consoles'];
		}
		else
		{
			if ( $game->year > 0 )
				$report['title'] = sprintf( '%s (%d)', $game->title, $game->year );
			else
				$report['title'] = $game->title;

			$report['url'] = $game->gsUrl;
			$report['category'] = ( $game->platform == 'pc' )? 'Games':'Consoles';
		}

		// so things are in order ;]
		$report['attributes'] = $tmp['attributes'];

		if ( $game->platform == 'pc' )
		{
			if ( is_array( $report['attributes'] ) )
			{
				foreach( $report['attributes'] as $attr => $monkey )
				{
					if ( !in_array( $attr, $this->ed_def['report']['categoryGroups']['Games'] ) )
					{
						$ed->delAttr( $report, 'Games', $attr );
					}
				}
			}
		}
		else
		{
			if ( !$ed->isAttr( $report, $report['category'], 'ConsolePlatform' ) )
			{
				if ( isset( $this->ed_def['siteAttributes']['consoleplatform'][$game->platform] ) )
				{
					$ed->addAttr( $report, $report['category'], 'ConsolePlatform', $this->ed_def['siteAttributes']['consoleplatform'][$game->platform] );
				}
				else
				{
					$ed->addAttr( $report, $report['category'], 'ConsolePlatform', $game->platform );
				}
			}
		}
			
		$genre = $game->genre;

		if ( isset( $this->ed_def['siteAttributes']['gamegenre'][$genre] ) )
		{
			$ed->addAttr( $report, $report['category'], 'GameGenre', $this->ed_def['siteAttributes']['gamegenre'][$genre] );
		}
		else
		{
			$ed->addAttr( $report, $report['category'], 'GameGenre', $genre );
		}


		if ( ( !$this->ids ) &&
				( is_array( $report['attributes']['GameGenre'] ) ) )
			sort( $report['attributes']['GameGenre'] );
		return $report;
	}
	
	function filter( $search ){ // Try to remove any excess release attribs from search string
		foreach ($this->_def['regex']['filter'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		return trim( str_replace( $this->ed_def['strip'], ' ', $search ) );
	}
			
	function games(){
		$this->_exts_array[] = new gamespot();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->ed_def = $ed->get_def();
	}
	
}
?>