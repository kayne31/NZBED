<?php 
require_once( INCLUDEPATH.'rovi.php' );
require_once( INCLUDEPATH.'discogs.php' );
require_once( INCLUDEPATH.'mbrainz.php' );

class music
{
	var $_debug = false;
	
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $ed_def;
	var $_def = array(
			'regex' => array(
					'filter' => array(
							'/(ac3|dd[25]\.?[01]|5\.1)/i',
							'/dts/i',
							'/mp3/i',
							'/aac/i',
							'/\bogg\b/i',
							'/(flac|lossless)/i',
							'/(CDS|CD)/',
							'/(19\d{2})|(20\d{2})/',
							'/HQ/',
							'/\d{1,3}(\.?|\s?)kbps/i',
							'/kbps/i',
							'/repack/i',
							'/PROPER/',
							'/CDM/'
					)
			)
	);
	
	function music(){
		// Instantiate new instances here
		$this->_exts_array[] = new rovi();
		$this->_exts_array[] = new discogs();
		$this->_exts_array[] = new mbrainz();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->ed_def = $ed->get_def();
	}

	function filter( $search ){ // Try to remove any excess release attribs from search string
		foreach ($this->_def['regex']['filter'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		$search = trim(str_replace( $this->ed_def['strip'], ' ', $search ));
		return $search;
	}
	
	function getfromurl( $url, $ignoreCache = false ){
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->ismyurl( $url ) )
			{
				$album = $ext->getalbumfromurl( $url, $ignoreCache );
				$report = $this->buildReport( $url );
				return $this->musicGetReport( $album, $report );

			}
		}
		return false;
	}

	function search( $search, $ignoreCache = false ){
		$filteredsearch = $this->filter( $search ); // run filter to clean up the search string
		if( !$ignoreCache )
		{// Use from cache if it's available
			foreach( $this->_exts_array as $ext )
			{	if( $this->_debug ) printf( "Checking Cache: %s search\n", $search );
				if( ( $albumID = $ext->checkCache( $filteredsearch ) ) !== false )
				{// Search done before and we have an album ID
					if( $this->_debug ) printf( "albumID: %s Found\n", $albumID );
					if( ( $album = $ext->getAlbumfromdb( $albumID ) ) !== false ){
						$report = $this->buildReport( $search );
						return $this->musicGetReport( $album, $report );
					}
				}
			}
		}
		//if We are here either ignoreCache is true or the album wasn't in the cache
		//Let's start searching through extensions
		foreach( $this->_exts_array as $ext )
		{
			if( ( $album = $ext->search( $filteredsearch ) ) != false){
				$report = $this->buildReport( $search );
				return $this->musicGetReport( $album, $report );
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
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['Music'] ) )
			{
				foreach( $array as $id => $reg )
				{

					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$ed->addAttr( $report, 'Music', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$ed->addAttr( $report, 'Music', $attr, $id );
						}
					}
				}
			}
		}
		return $report;
	}
	
	function musicGetReport( $album, $tmp )
	{
		global $ed;
		$report = array();
		if ( $this->_debug ) var_dump( $album );
		if ( $ed->ids )
		{
			if ( $album->year > 0 )
				$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - %s (%d)', $album->artist, $album->title, $album->year );
			else
				$report[$this->ed_def['report']['fields']['title']] = sprintf( '%s - %s', $album->artist, $album->title );

			$report[$this->ed_def['report']['fields']['url']] = $album->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['Music'];
		}
		else
		{
			if ( $album->year > 0 )
				$report['title'] = sprintf( '%s - %s (%d)', $album->artist, $album->title, $album->year );
			else
				$report['title'] = sprintf( '%s - %s', $album->artist, $album->title );

			$report['url'] = $album->url;
			$report['category'] = 'Music';
		}
		$report['attributes'] = $tmp['attributes'];
		if(strlen($album->genre) != 0){
			$genres = explode( ', ', $album->genre );
		}else{
			$genres = $album->genre;
		}
		
		if ( $this->_debug ) var_dump( $genres );
		if(is_array($genres))
		{
			foreach( $genres as $gen )
			{
		 		$gen = trim( $gen );
				if ( isset( $this->ed_def['siteAttributes']['audiogenre'][$gen] ) )
				{
					$ed->addAttr( $report, 'Music', 'AudioGenre', $this->ed_def['siteAttributes']['audiogenre'][$gen] );
				}
				else
				{
					$ed->addAttr( $report, 'Music', 'AudioGenre', $gen );
				}
			}
		}
		if ( ( !$ed->ids ) &&
				( is_array( $report['attributes']['AudioGenre'] ) ) )
			sort( $report['attributes']['AudioGenre'] );

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
}
?>