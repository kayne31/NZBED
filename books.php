<?php
require_once( INCLUDEPATH.'amazon.php' );
class books{
	var $debug = false;
	var $ignoreCache = false;
	var $_exts_array = array();// array to hold all instances of extensions
	var $_urlregex_array = array();// array to hold all the url regex from the extenstions
	var $_currentExt;
	var $_primary = 'amazon';
	var $_def;
	var $_regex = array(
					'/\[.*?\]/i'
					);
	
	function search( $search, $ignorecache = false ){
		$this->ignoreCache = true;//$ignorecache;
		$filteredsearch = $this->filter( $search );
		//print($filteredsearch).PHP_EOL;
		if( !$this->ignoreCache )
		{// Use from cache if it's available
			foreach( $this->_exts_array as $ext )
			{
				if( ( $isbn = $ext->checkCache( $filteredsearch ) ) !== false )
				{// Search done before and we have a Book ID
					if( ( $book = (object)$ext->getBookfromdb( $isbn ) ) !== false ){
						return $this->getReport( $book, $search );
					}
				}
			}
		}
		foreach( $this->_exts_array as $ext )
		{
			if($ext->getName() === $this->_primary)
			{
				if( ( $book = $ext->search( $filteredsearch ) ) != false){
					return $this->getReport( $book, $search );
				}
			}
		}
	}
	
	function filter( $search ){ // Try to remove any excess release attribs from search string
		foreach ($this->_regex as $regex){
			$search = preg_replace($regex,"",$search);
			//printf( "query during: %s - regex:%s \n", $search,$regex );
		}
		$search = trim( str_replace( $this->_def['strip'], ' ', $search ) );
		//printf( "Search: %s\nFiltered: %s\n", $old,$search );
		return $search;
	}
	
	function getReport( $book, $search ){
		global $ed;
		$report = array();
		if(empty($book->author)){
			$title = $book->title;
		}else if(is_array($book->author)){
			$title = $book->title;
		}else{
			$title = sprintf( '%s - %s', $book->author, $book->title);			
		}
		$report[$this->_def['report']['fields']['title']] = $title;
		$report[$this->_def['report']['fields']['url']] = $book->url;
		$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['Books'];
		return $report;
	}
	
	function books(){
		$this->_exts_array[] = new amazon();
		
		foreach( $this->_exts_array as $ext )
		{
			$this->_urlregex_array[] = $ext->geturlregex();
		}
		global $ed;
		$this->_def = $ed->get_def();
	}
	
	function setPrimary( $primary )
	{
		$this->_primary = $primary;
	}
}

?>