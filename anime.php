<?php 
require_once( INCLUDEPATH.'anidb.php' );

class anime{
	var $_debug = false;
	
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $ed_def;
	var $_def = array(
			'regex' => array(
				'filter' => array(
				)
			)
	);
	
	function search( $search, $ignoreCache = false ){
		$filteredsearch = $this->filter( $search ); // run filter to clean up the search string
		foreach( $this->ed_def['info']['Anime'] as $reg )
		{
			if ( preg_match( $reg, $search, $matches ) )
			{
				$matched = true;
				break;
			}
		}
		if( !$ignoreCache )
		{// Use from cache if it's available
			foreach( $this->_exts_array as $ext )
			{
				if( ( $anime = $ext->checkCache( $filteredsearch ) ) !== false )
				{// Search done before and we have a Anime ID
					if( $this->_debug ) printf( "Anime ID: %s Found\n", $id );
						( preg_match( $reg, $string, $matches ) );
						return $this->animeGetReport( $anime, $matches[3] );
				}
			}
		}
		//if We are here either ignoreCache is true or the Anime wasn't in the cache
		//Let's start searching through extensions
		foreach( $this->_exts_array as $ext )
		{
			if( ( $anime = $ext->search( $filteredsearch ) ) != false){
				return $this->animeGetReport( $anime, $matches[3] );
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
	
	function getfromurl( $url, $ignoreCache = false ){
		foreach( $this->_exts_array as $ext )
		{
			if( $ext->ismyurl( $url ) )
			{
				$anime = $ext->getanimefromurl( $url, $ignoreCache );
				( preg_match( $reg, $string, $matches ) );
				return $this->animeGetReport( $anime, $matches[3] );
			}
		}
		return false;
	}
	
	function animeGetReport( $anime, $aStr = '' )
	{
		global $ed;
		$report = array();
		if ( $ed->ids )
		{
			$report[$this->ed_def['report']['fields']['title']] = $anime->name;
			$report[$this->ed_def['report']['fields']['url']] = $anime->url;
			$report[$this->ed_def['report']['fields']['category']] = $this->ed_def['report']['category']['Anime'];
		}
		else
		{
			$report['title'] = $anime->name;
			$report['url'] = $anime->url;
			$report['category'] = 'Anime';
		}
		$aStr .= $anime->type;
		foreach( $this->ed_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->ed_def['report']['categoryGroups']['Anime'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$ed->addAttr( $report, 'Anime', $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$ed->addAttr( $report, 'Anime', $attr, $id );
						}
					}
				}
			}
		}
		$ed->addAttr( $report, 'Anime', 'Language', 'Japanese' );
		$ed->addAttr( $report, 'Anime', 'Subtitle', 'English' );
		return $report;
	}
	
	function filter( $search ){ // Try to remove any excess release attribs from search string
		foreach ($this->_def['regex']['filter'] as $regex){
			$search = preg_replace($regex,"",$search);
			//if($this->_debug) printf( "query during: %s - regex:%s \n", $query,$catregex );
		}
		$search = str_replace( $this->ed_def['strip'], ' ', $search );
		return $search;
	}
			
	function anime(){
		$this->_exts_array[] = new anidb();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->ed_def = $ed->get_def();
	}
	
}
?>