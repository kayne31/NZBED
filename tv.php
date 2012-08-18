<?php
require_once( INCLUDEPATH.'tvrage.php' );
require_once( INCLUDEPATH.'tvdb.php' );

class tv{
	var $debug = false;
	var $ignoreCache = false;
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $ed_def;
	var $_error;
	var $_currentExt;
	var $_primary = 'tvrage';

	function search( $search, $ignorecache = false ){
		$this->ignoreCache = $ignorecache;
		$filteredsearch = $this->filter( $search ); // run filter to clean up the search string
		$stripedString = str_replace( $this->ed_def['strip'], ' ', $filteredsearch );
		$matches = $this->checkformatches( $stripedString );
		if(!$matches){
			return false;
		}
		$showquery = $matches[1];
		foreach( $this->_exts_array as $ext )
		{
			if($ext->getName() == $this->_primary)
			{
				$this->_currentExt = $ext->getName();
				if( ( $show = $ext->getFShow( $showquery ) ) != false){
					if ( $this->debug ) printf("%s--%s\n",$matches['mUsed'],$show->name);
					switch( $matches['mUsed'] ){
						case ('Date'):
							return $this->matchDate( $ext, $show, $matches );
							break;
						case ('TV'):
							return $this->matchTv( $ext, $show, $matches );
							break;
						case ('DVD'):
							return $this->matchDvd( $ext, $show, $matches );
							break;
						case ('Multi'):
							return $this->matchMulti( $ext, $show, $matches );
							break;
						case ('Part'):
							return $this->matchPart( $ext, $show, $matches );
							break;
						case ('Series'):
							return $this->matchSeries( $ext, $show, $matches );
							break;
					}
				}
			}
		}
		foreach( $this->_exts_array as $ext )
		{
			if($ext->getName() != $this->_primary)
			{
				$this->_currentExt = $ext->getName();
				if( ( $show = $ext->getFShow( $showquery ) ) != false){
					if ( $this->debug ) printf("%s\n",$matches['mUsed']);
					switch( $matches['mUsed'] ){
						case ('Date'):
							return $this->matchDate( $ext, $show, $matches );
							break;
						case ('TV'):
							return $this->matchTv( $ext, $show, $matches );
							break;
						case ('DVD'):
							return $this->matchDvd( $ext, $show, $matches );
							break;
						case ('Multi'):
							return $this->matchMulti( $ext, $show, $matches );
							break;
						case ('Part'):
							return $this->matchPart( $ext, $show, $matches );
							break;
						case ('Series'):
							return $this->matchSeries( $ext, $show, $matches );
							break;
					}
				}
			}
		}
		if ( !isset( $ext->error ) )
			$this->_error = 'Invalid show name: '.$showquery;
		else
			$this->_error = $ext->error;
		return false;
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

	function getfromurl( $url, $ignorecache = false ){
		$this->ignoreCache = $ignorecache;
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->ismyurl( $url ) )
			{
				if ( preg_match( $ext->geturlregex(), $url, $matches ) )
				{
					$showquery = str_replace( $this->ed_def['strip'], ' ', $matches[1] );
					if ( ( $show = $ext->getShow( $showquery, $this->ignoreCache, true ) ) !== false )
					{
						if ( ( $ep = $ext->getIDEpisode( $show->tvShowID, $matches, $this->ignoreCache ) ) !== false )
						{
							// search for episode properties
							return $this->tvGetReport( $show, $ep );
						}
						else
						{
							$this->_error = sprintf( 'Could not find episodeID: %d for show: %s', $matches[2], $matches[1] );
							return false;
						}
					}
					else
					{
						$this->_error = sprintf( 'Invalid tvrage show ID: %s', $matches[1] );
						return false;
					}
				}
			}
		}
		return false;
	}

	function tvGetReport( $show, $ep, $aStr = '' )
	{
		global $ed;
		$report = array();
		$ep->title = preg_replace($this->ed_def['addPart']['from'], $this->ed_def['addPart']['to'], $ep->title );
		$pNum_n = $pNum = substr($ep->title,strpos($ep->title, "(Part ")+6,-1);
		if ( preg_match( $this->ed_def['info']['isRoman'], $pNum ) )
			$pNum_n = Numbers_Roman::toNumber($pNum); // convert to number
		if($pNum!=$pNum_n)
			$ep->title=str_replace("Part ".$pNum, "Part ".$pNum_n, $ep->title);

		list( $rSeries, $rEpisode ) = $this->tvNewSEp( $show, $ep->series, $ep->episode, false );

		if ( $ed->ids )
		{

			$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - %sx%02d - %s', $show->name, $rSeries, $rEpisode, $ep->title );

			$report[$this->ed_def['report']['fields']['url']] = $ep->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['TV'];
		}
		else
		{
			$report['title'] = sprintf( '%s - %sx%02d - %s', $show->name, $rSeries, $rEpisode, $ep->title );
			$report['url'] = $ep->url;
			$report['category'] = 'TV';
		}

		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}

		if ( $ed->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
		{
			$ed->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}
			
		$class = explode( ' | ', $show->class );
		$genres = explode( '|', $show->genre );
		foreach( $genres as $gen )
		{
			$gen = trim($gen);
			if ( isset( $this->ed_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}

		foreach( $class as $id => $cl )
		{
			if ( isset( $this->ed_def['siteAttributes']['class'][$cl] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$ed->ids ) &&
				( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );

		return $report;
	}

	function tvGetReportMulti( $show, $listep, $min, $max, $aStr = '' )
	{
		global $ed;
		$report = array();
		if ( $this->debug ) print_r( $listep );

		for ( $i = 0; $i < count( $listep ); $i++ )
		{
			$listep[$i]->title = preg_replace( $this->ed_def['addPart']['from'], $this->ed_def['addPart']['to'], $listep[$i]->title );
			$pNum_n = $pNum = substr($listep[$i]->title,strpos($listep[$i]->title, "(Part ")+6,-1);
			if ( preg_match( $this->ed_def['info']['isRoman'], $pNum ) )
				$pNum_n = Numbers_Roman::toNumber($pNum); // convert to number
			if($pNum!=$pNum_n)
				$listep[$i]->title=str_replace("Part ".$pNum, "Part ".$pNum_n, $listep[$i]->title);
			list( $listep[$i]->rSeries, $listep[$i]->rEpisode ) = $this->tvNewSEp( $show, $listep[$i]->series, $listep[$i]->episode, false );

			$notes .= sprintf( "%dx%02d - %s: %s\n", $listep[$i]->rSeries, $listep[$i]->rEpisode, $listep[$i]->title, $listep[$i]->url );
		}

		if ( $this->debug ) print_r( $listep );

		if ( count( $listep ) == 2 )
		{
			// do a check for Part's
			if ( ( preg_match( $this->ed_def['getPart'], $listep[0]->title, $e0part ) ) &&
					( preg_match( $this->ed_def['getPart'], $listep[1]->title, $e1part ) ) &&
					( $e0part[1] == $e1part[1] ) )
			{
				$title = sprintf( '%s - %sx%02d-%sx%02d - %s (Part %d & %d)', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode, $listep[1]->rSeries, $listep[1]->rEpisode, $e0part[1], $e0part[2], $e1part[2] ) ;
			}
			else
			{
				$title = sprintf( '%s - %dx%02d-%dx%02d - %s / %s', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode, $listep[1]->rSeries, $listep[1]->rEpisode, $listep[0]->title, $listep[1]->title );
			}
		}
		else
		{
			$title = sprintf( '%s - %sx%02d-%sx%02d', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode,
					$listep[count($listep)-1]->rSeries, $listep[count($listep)-1]->rEpisode );
		}
		if($this->_currentExt === 'tvrage')
		{
			$url = sprintf( '%s/episode_list/%d', $show->url, $listep[0]->series );
		}else{
			$url = $listep[0]->url;
		}

		if ( $ed->ids )
		{
			$report[$this->ed_def['report']['fields']['title']] = $title;
			$report[$this->ed_def['report']['fields']['url']] = $url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['TV'];
			$report[$this->ed_def['report']['fields']['notes']] = $notes;
		}
		else
		{
			$report['title'] = $title;
			$report['url'] = $url;
			$report['category'] = 'TV';
			$report['notes'] = $notes;
		}

		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}

		if ( $ed->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
		{
			$ed->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}
			
		$class = explode( ' | ', $show->class );

		$genres = explode( ' | ', $show->genre );

		foreach( $genres as $gen )
		{
			if ( isset( $this->ed_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}

		foreach( $class as $id => $cl )
		{
			if ( isset( $this->ed_def['siteAttributes']['class'][$cl] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$ed->ids ) &&
				( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );

		if ( $this->debug ) print_r( $report );

		return $report;
	}


	function tvGetReportDVD( $show, $ep, $aStr = '' )
	{
		$report = array();
		global $ed;
		$ep->title = preg_replace( $this->ed_def['addPart']['from'], $this->ed_def['addPart']['to'], $ep->title );
		if ( $ed->ids )
		{
			$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - Season %d [DVD %d]', $show->name, $ep->series, $ep->episode );
			$report[$this->ed_def['report']['fields']['url']] = $ep->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['TV'];
		}
		else
		{
			$report['title'] = sprintf( '%s - Season %d [DVD %d]', $show->name, $ep->series, $ep->episode );
			$report['url'] = $ep->url;
			$report['category'] = 'TV';
		}

		$ed->addAttr( $report, 'TV', 'Source', 'DVD' );
		$ed->addAttr( $report, 'TV', 'Format', 'DVD' );

		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}
			
		$class = explode( ' | ', $show->class );

		$genres = explode( ' | ', $show->genre );

		foreach( $genres as $gen )
		{
			if ( isset( $this->ed_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}

		foreach( $class as $id => $cl )
		{
			if ( isset( $this->ed_def['siteAttributes']['class'][$cl] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$ed->ids ) &&
				( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );

		return $report;
	}

	function tvGetReportDate( $show, $ep, $aStr = '' )
	{
		$report = array();
		global $ed;
		$ep->title = preg_replace( $this->ed_def['addPart']['from'], $this->ed_def['addPart']['to'], $ep->title );
		if ( $ed->ids )
		{
			if($ep->title)
				$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - %04d-%02d-%02d - %s', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ), $ep->title );
			else
				$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - %04d-%02d-%02d', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ));
			$report[$this->ed_def['report']['fields']['url']] = $ep->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['TV'];
		}
		else
		{
			$report['title'] = sprintf( '%s - %04d-%02d-%02d - %s', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ), $ep->title );
			$report['url'] = $ep->url;
			$report['category'] = 'TV';
		}

		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$ed->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}

		if ( $ed->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
		{
			$ed->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}

		$class = explode( ' | ', $show->class );

		$genres = explode( ' | ', $show->genre );

		foreach( $genres as $gen )
		{
			if ( isset( $this->ed_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $this->ed_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}

		foreach( $class as $id => $cl )
		{
			if ( isset( $this->ed_def['siteAttributes']['class'][$cl] ) )
			{
				$ed->addAttr( $report, 'TV', 'VideoGenre', $ed->_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$ed->ids ) &&
				( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );

		return $report;
	}

	function tvNewSEp( $show, $series, $episode, $u2t = true )
	{
		return array( $series, $episode );
	}

	function matchSeries( $ext, $show, $matches ){
		// determine size of series
		$min = 1;
		$max = 50;
		for ( $i = $min; $i <= $max; $i++ )
		{
			if ( ( $tep = $ext->getEpisode( $show->tvShowID, $matches[2], $i, $this->ignoreCache ) ) !== false )
			{
				if ( $this->debug ) var_dump( $tep );
				$ep[] = $tep;
			}
			else
			{
				break;
			}
		}
		return $this->tvGetReportMulti( $show, $ep, $min, $max, $matches[3] );
	}

	function matchPart( $ext, $show, $matches ){
		// assume 1xPart num
		if ( $this->debug ) printf('Doing Part');
		// check roman numeral
		if ( preg_match( $this->ed_def['info']['isRoman'], $matches[2] ) )
			$num = Numbers_Roman::toNumber($matches[2]); // convert to number
		else
			$num = $matches[2];
			
		if ( ( $ep = $ext->getEpisode( $show->tvShowID, 1, $num,
				$this->ignoreCache ) ) !== false )
		{
			if ( $this->debug ) var_dump( $ep );
			return $this->tvGetReport( $show, $ep, $matches[3] );
		}
		else
		{
			if($this->_currentExt === 'tvrage')
			{
				$ep = (object)array(
						'series' => 1,
						'episode' => $num,
						'title' => sprintf( 'Part %d', $num ),
						'url' => sprintf( '%s/episode_list/%d', $show->url, 1 ) );
				if ( $this->debug ) var_dump( $ep );
				return $this->tvGetReport( $show, $ep, $matches[4] );
			}else{
				$ep = (object)array(
						'series' => 1,
						'episode' => $num,
						'title' => sprintf( 'Part %d', $num ),
						'url' => $show->url );
				if ( $this->debug ) var_dump( $ep );
				return $this->tvGetReport( $show, $ep, $matches[4] );
			}
		}
	}

	function matchMulti( $ext, $show, $matches ){
		$min = 9999;
		$max = -1;
		// split up the episodes
		preg_match_all( $this->ed_def['info']['TVMsplit'], $matches[3], $epList );
		if ( $this->debug ) print_r( $epList );
		$min = Min($epList[1]);
		$max = Max($epList[1]);
		for ( $i = $min; $i <= $max; $i++ )
		{
			if ( ( $tep = $ext->getEpisode( $show->tvShowID, $matches[2], $i, $this->ignoreCache ) ) !== false )
			{
				if ( $this->debug ) var_dump( $tep );
				$ep[] = $tep;
			}
			else
			{
				if($this->_currentExt === 'tvrage')
				{
					$tep = (object)array(
							'series' => $matches[2],
							'episode' => $i,
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $i ),
							'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
					if ( $this->debug ) var_dump( $tep );
					$ep[] = $tep;
				}else{
					$tep = (object)array(
							'series' => $matches[2],
							'episode' => $i,
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $i ),
							'url' => $show->url );
					if ( $this->debug ) var_dump( $tep );
					$ep[] = $tep;
				}
			}
		}
		if($min!=$max)
			return $this->tvGetReportMulti( $show, $ep, $min, $max, $matches[4] );
		else{
			if ( ( $ep = $ext->getEpisode( $show->tvShowID, $matches[2], $min, $this->ignoreCache ) ) !== false ){
				if ( $this->debug ) var_dump( $ep );
				return $this->tvGetReport( $show, $ep, $matches[4] );
			}
			else{
				if($this->_currentExt === 'tvrage')
				{
					$ep = (object)array(
							'series' => sprintf("%d", $matches[2] ),
							'episode' => $matches[3],
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
							'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
					if ( $this->debug ) var_dump( $ep );
					return $this->tvGetReport( $show, $ep, $matches[4] );
				}else{
					$ep = (object)array(
							'series' => sprintf("%d", $matches[2] ),
							'episode' => $matches[3],
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
							'url' => $show->url );
					if ( $this->debug ) var_dump( $ep );
					return $this->tvGetReport( $show, $ep, $matches[4] );
				}
			}
		}
	}

	function matchDvd( $ext, $show, $matches ){
		if($this->_currentExt === 'tvrage')
		{
			$ep = (object)array(
					'series' => $matches[2],
					'episode' => $matches[3],
					'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
			return $this->tvGetReportDVD( $show, $ep, $matches[4] );
		}else{
			$ep = (object)array(
					'series' => $matches[2],
					'episode' => $matches[3],
					'url' => $show->url );
			return $this->tvGetReportDVD( $show, $ep, $matches[4] );
		}
	}

	function matchTv( $ext, $show, $matches ){
		if ( ( $ep = $ext->getEpisode( $show->tvShowID, $matches[2], $matches[3],
				$this->ignoreCache ) ) !== false )
		{
			if ( $this->debug ) var_dump( $ep );
			// search for episode properties
			return $this->tvGetReport( $show, $ep, $matches[4] );
		}
		else
		{
			if($this->_currentExt === 'tvrage')
			{
				$ep = (object)array(
						'series' => sprintf("%d", $matches[2] ),
						'episode' => $matches[3],
						'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
						'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
				if ( $this->debug ) var_dump( $ep );
				return $this->tvGetReport( $show, $ep, $matches[4] );
			}else{
				$ep = (object)array(
						'series' => sprintf("%d", $matches[2] ),
						'episode' => $matches[3],
						'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
						'url' => $show->url );
				if ( $this->debug ) var_dump( $ep );
				return $this->tvGetReport( $show, $ep, $matches[4] );
			}
		}
	}

	function matchDate( $ext, $show, $matches ){
		if ( $matches[2] >= 100 )
		{
			$date = mktime( 0,0,0, $matches[3], $matches[4], $matches[2] );
		}
		else
		{
			// dumb ass american date standard
			$date = mktime( 0,0,0, $matches[2], $matches[3], $matches[4] );
		}
		if ( $this->debug ) printf(" Found Date: %s\n", date('d/m/Y', $date ) );
		// get ID
		if ( ( $ep = $ext->getDateEpisode( $show->tvShowID, $date, $this->ignoreCache ) ) !== false )
		{
			if ( $this->debug ) var_dump( $ep );
			if ( $ep->date == 0 )
			{
				$ep->date = $date;
			}
			return $this->tvGetReportDate( $show, $ep, $matches[5] );
		}
		else
		{
			if ( $this->debug ) printf(" Unable to find episode from date %d/%d/%d", $matches[2], $matches[3], $matches[4] );
			$ep = (object)array(
					'date' => $date,
					'title' => '',
					'url' => sprintf( '%s', $show->url ) );
			return $this->tvGetReportDate( $show, $ep, $matches[5] );
		}
	}

	function checkformatches( $string ){
		$stripedString = str_replace( $this->ed_def['strip'], ' ', $string );
		if ( $this->debug ) printf("stripedString = %s\n", $stripedString );
		$typematches = array();
		$typematched = array();
		if ( isset( $this->ed_def['info']['TV'] ) )
		{
			foreach( $this->ed_def['info']['TV'] as $name => $arr )
			{
				foreach ( $arr as $reg )
				{
					if ( $this->debug ) printf( "regex: %s\n", $reg );
					if ( preg_match( $reg, $stripedString, $matches ) )
					{
						$typematches[$name] = $matches;
						$typematched[$name] = true;
						$hasmatched = true;
						if ( $this->debug )
						{
							printf( "regex search: %s Found %s: \n", $reg, $name );
							var_dump( $matches );
						}
						break;
					}
				}
			}
		}
		if ( !$hasmatched )
		{
			return false;
		}
		if ( $typematched['Multi'] )
		{
			// do possibiliy checks
			$min = 9999;
			$max = -1;
			$thresh = 10;
			// split up the episodes
			preg_match_all( $this->ed_def['info']['TVMsplit'], $typematches['Multi'][3], $epList );
			if ( $this->debug ) print_r( $epList );
			if ( count( $epList[1] ) == 0 )
			{
				if ( $this->debug ) printf(" no episode range found, not Multi\n");
				$typematched['Multi'] = false;
			}
			else
			{
				$min = Min($epList[1]);
				$max = Max($epList[1]);
				if ( $max-$min > $thresh )
				{
					if ( $this->debug ) printf(" range plausability check failed, %d-%d\n", $min, $max );
					$typematched['Multi'] = false;
				}
			}
		}
		if ( $typematched['Date'] )
		{
			if ( ( ( $typematches['Date'][2] > 100 ) &&
					( ( $typematches['Date'][3] > 12 ) ||
							( $typematches['Date'][4] > 31 ) ||
							( $typematches['Date'][2] > date('Y')+1 ) ||
							( $typematches['Date'][2] < 1900 ) ) ) ||
					( ( $typematches['Date'][2] < 100 ) &&
							( ( $typematches['Date'][2] > 12 ) ||
									( $typematches['Date'][3] > 31 ) ||
									( $typematches['Date'][4] > date('y')+1 ) ) ) )
			{
				if ( $this->debug ) printf(" date plausability check failed, %d/%d/%d\n", $matches[2], $matches[3], $matches[4] );
				$typematched['Date'] = false;
			}
		}
		foreach( $typematched as $name => $isMatch )
		{
			if ( $isMatch )
			{
				$matches = $typematches[$name];
				$matches['mUsed'] = $name;
				break;
			}
		}
		return $matches;
	}

	function filter( $search ){ // Try to remove any excess release attribs from search string
		foreach ($this->_def['regex']['filter'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		return $search;
	}

	function tv(){
		$this->_exts_array[] = new tvrage();
		$this->_exts_array[] = new tvdb();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->ed_def = $ed->get_def();
	}
	
	function setPrimary( $primary )
	{
		$this->_primary = $primary;
	}
	
	var $_def = array(
			'regex' => array(
					'strip' => array( '.', '-', '(', ')', '_', '#', '[', ']','"' ),
					'filter' => array(
					)
			)
	);
}
?>