<?php
require_once( INCLUDEPATH.'imdb.php' );
require_once( INCLUDEPATH.'tmdb.php' );

class movies{
	
	var $_debug = false;
	
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $_primary = 'tmdb';
	var $ed_def;
	var $_def = array(
			'regex' => array(
					'filter' => array(
					'/\b(ntsc|usa|jpn)\b/i',
					'/\b(pal|eur)\b/i',
					'/\bsecam\b/i',
					'/\bcam\b/i',
					'/(dvd[.-]?scr|screener)/i',
					'/\btc\b/i',
					'/\br5/i',
					'/\bts\b/i',
					'/vhs/i',
					'/(hdtv|\.ts(?!\.))/i',
					'/(tvrip|pdtv|dsr|dvb|sdtv|dtv|satrip)/i',
					'/hd[-.]?dvd|hd2dvd/i',
					'/(blu[-. ]?ray|b(d|r|rd)[-.]?(rom|rip))/i',
					'/(web[-. ]?dl|hditunes|ituneshd|ithd|webhd)/i',
					'/xvid/i',
					'/(dvdrip|dvd)/i',
					'/((h.?264|x264|avc))/i',
					'/avchd/i',
					'/\.ts(?!\.)/i',
					'/svcd/i',
					'/mvcd/i',
					'/divx/i',
					'/dvdr/i',
					'/w(mv|vc1)/i',
					'/ratDVD/i',
					'/((?:\.)?720p|\.?720)/i',
					'/1080i/i',
					'/1080p/i',
					'/psp/i',
					'/\b(ipod|iphone|itouch)\b/i',
					'/(ac3ld|ac3d|ac3|dd[25]\.?[01]|5\.1)/i',
					'/(dtshd|dts)/i',
					'/mp3/i',
					'/aac/i',
					'/\bogg\b/i',
					'/(flac|lossless)/i',
					'/(!<?clone)CD/i',
					'/Clone(\.|\-|_|)CD/i',
					'/Alcohol(\.|\-|_|)120%/i',
					'/((multi(5|3)))/i',
					'/(french)/i',
					'/((\.+DL\.+)|(german(?!.sub?.))|(deutsch))/i',
					'/((spanish)|(multi5))/i',
					'/((italian)|(multi(5|3)))/i',
					'/((dutch))/i',
					'/((\.+PL\.+))/i',
					'/((vostfr)|(vost))/i',
					'/(german.sub)/i',
					'/((nlsubs)|(nl.?subs)|(nl.?subbed))/i',
					'/swesub/i',
					'/proper.+/i',
					'/iNTERNAL.*/',
					'/WS/',
					'/HR/',
					'/3d/i',
					'/H-SBS/',
					'/hdrip/i',
					'/brrip/i',
					'/uncut/i',
					'/(readnfo)|(read\.nfo)/i',
					'/(line\.dubbed)|(dubbed)/i',
					'/unrated/i',
					'/(extended\.edition)|(extended)/i',
					'/remastered/i'
					)
			)
	);
	
	function movies(){
		// Instantiate new instances here
		$this->_exts_array[] = new tmdb();
		$this->_exts_array[] = new imdb();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->ed_def = $ed->get_def();
		
	}
	
	function filter( $search ){ // Try to remove any excess release attribs from search string
		$old = $search;
		foreach ($this->_def['regex']['filter'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		$fTitle = preg_replace( $this->ed_def['filmMatch'], '', $search );
		$fTitle = trim( str_replace( $this->ed_def['strip'], ' ', $fTitle ) );
		if($this->_debug) printf( "Search: %s\nFiltered: %s\n", $old,$fTitle );
		return $fTitle;
	}
	
	function getfromurl( $url, $ignoreCache = false ){
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->ismyurl( $url ) )
			{
				$movieinfo = $ext->getmoviefromurl( $url, $ignoreCache );
				$report = $this->buildReport( $url );
				return $this->filmGetReport( $movieinfo, $report );
			}
		}
		return false;
	}
	
	function buildReport( $string ){
		global $ed;
		$report = array();
		$fTitle = $string;
		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['Movies'] ) )
			{
				$rString = $this->ed_def['report']['category']['Movies'].$this->ed_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$ed->addAttr( $report, 'Movies', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$ed->addAttr( $report, 'Movies', $attr, $id );
						}
					}
				}
			}
		}
		return $report;
	}
	
	function insertImdb( $ID )
	{
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->getName() === 'imdb' )
			{
				$ext->getFilm( $ID );
			}
		}
	}
	
	function filmGetReport( $film, $tmp )
	{
		global $ed;
		$report = array();
		if($film->aka && (trim($film->title) != trim($film->aka))){
			$movieTitle=sprintf( '%s (%s) (%d)', $film->aka ,$film->title, $film->year );
		}
		else
			$movieTitle=sprintf( '%s (%d)', $film->title, $film->year );

		if ( $ed->ids )
		{

			$report[$this->ed_def['report']['fields']['title']] = $movieTitle;
			$report[$this->ed_def['report']['fields']['url']] = $film->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['Movies'];
		}
		else
		{

			$report['title'] = $movieTitle;
			$report['url'] = $film->url;
			$report['category'] = 'Movies';
		}

		// so things are in order ;]
		$report['attributes'] = $tmp['attributes'];
			
		$genres = explode( ', ', $film->genre );

		foreach( $genres as $id => $gen )
		{
			if ( isset( $this->ed_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$ed->addAttr( $report, 'Movies', 'VideoGenre', $this->ed_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$ed->addAttr( $report, 'Movies', 'VideoGenre', $gen );
			}
		}
			if ( ( !$ed->ids ) &&
			 ( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );
		//print_r($report);
		return $report;
	}
	
	function search( $search, $ignoreCache = false ){
		$filteredsearch = $this->filter( $search ); // run filter to clean up the search string
		if( !$ignoreCache )
		{// Use from cache if it's available
			foreach( $this->_exts_array as $ext )
			{
				if( ( $movieID = $ext->checkCache( $filteredsearch ) ) !== false )
				{// Search done before and we have a Movie ID
					if( $this->_debug ) printf( "movieID: %s Found\n", $movieID );
					if( ( $movie = (object)$ext->getMoviefromdb( $movieID ) ) !== false ){
						$report = $this->buildReport( $search );
						if($this->_debug) printf('done building report for: %s', $search);
						return $this->filmGetReport( $movie, $report );// We found the movie in the database and are returning it.
					}
				}
			}
		}
		//if We are here either ignoreCache is true or the Movie wasn't in the cache
		//Let's start searching through extensions
		foreach( $this->_exts_array as $ext )
		{
			if($ext->getName() === $this->_primary)
			{
				if( ( $movie = $ext->search( $filteredsearch ) ) != false){
					if( $this->_debug ) printf( "Found movie ID: %s\n", $movie->title );
					$report = $this->buildReport( $search );
					if($this->_debug) printf('done building report for: %s', $search);
					return $this->filmGetReport( $movie, $report );// We found the movie
				}	
			}
		}
		//if we are here the cache doesn't have it and the primary search doesn't either so now we will just search all providers
		foreach( $this->_exts_array as $ext )
		{
			if($ext->getName() != $this->_primary)
			{
				if( ( $movie = $ext->search( $filteredsearch ) ) != false){
					if( $this->_debug ) printf( "Found movie ID: %s\n", $movie->title );
					$report = $this->buildReport( $search );
					if($this->_debug) printf('done building report for: %s', $search);
					return $this->filmGetReport( $movie, $report );// We found the movie
				}	
			}
		}		
		return false;// Could not find any matches
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
	
	function setPrimary( $primary )
	{
		$this->_primary = $primary;
	}
}

?>