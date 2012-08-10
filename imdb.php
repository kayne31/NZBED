<?php
require_once( 'HTTP/Request.php' );

class imdb
{
	var $_debug = false;
	var $_def = array(
		'myname' => 'imdb',
		'myurl' => '/^(?:http:\/\/)?(?:www\.)?imdb\.com\/title\/(tt\d+)/i',
		'url' => array(
			'search' => 'http://www.google.co.uk/search?hl=en&q=%s+site:imdb.com&btnI=1',
			'movie' => 'http://www.imdb.com/title/%s/',
			'credits' => 'http://www.imdb.com/title/%s/fullcredits',
			'nameid' => 'http://www.imdb.com/name/%s/',
			'aka' => 'http://www.imdb.com/title/%s/releaseinfo#akas'
		),
		'regex' => array(
			'id' => array(
				'/http:\/\/(?:www\.|.*)?imdb.com(?:.*?)\/title\/tt(\d+)\//i',
				'/http:\/\/(?:www\.|.*)?imdb.com(?:.*?)\/Title\?(\d+)/i'
				//'/<p><b>Popular Titles<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i',
				//'/<p><b>Titles \(Exact Matches\)<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i',
				//'/<p><b>Titles \(Partial Matches\)<\/b> \(Displaying \d+ Results?\)<ol><li>\s*<a href="\/title\/([^\/]+)\//i'
				),
			'film' => array(
				'title' => '<meta property=\"og:title\" content=\"(.*?)(\((?:TV\s)?(?:Video\s)?\d+\))\"\/>',
				'rating' => '/<span class="rating-rating">([0-9\.]+)<span>/i',
				//'genreContainer' => '/<div class="see-more inline canwrap">\s*<h4 class="inline">Genres:</h4>\s*(.+?)<\/div>/i',
				'genre' => '/href="\/genre\/(.*?)"/iS',
				'plot' => '/<h2>Storyline<\/h2>\s*<p>(.+?)<em class="nobr">/i',
				'crew' => '/(?:<h5><a class="glossary" name=".+?" href="\/glossary\/.#(\w+)">.+?<\/a><\/h5><\/td><\/tr>)?<tr>\s*<td valign="top"><a href="\/name\/(nm\d+)\/">(.+?)<\/a><\/td><td(?: valign="top"(?: nowrap="1")?)?>.+?<\/td><td valign="top">(?:<a href=".+?\/glossary\/.+?">)?([^<]+)/i',
				'country' => '/href="\/country\/.*?"/iS',
				'akaInt' => '/<td>(.*)<\/td>\s*<td>(.*)International/i',	//Priority goes to international title
				'akaUS' => '/<td>(.*)<\/td>\s*<td>(.*)USA \(imdb display title\)/i',				//then us
				'akaUK' => '/<td>(.*)<\/td>\s*<td>(.*)UK \(imdb display title\)/i',				//then uk
				'original' => '/<td>(.*)<\/td>\s*<td>(.*) \(original title\)<\/td>/i',
				'imdb_display' => '/<title>(.*) \(\d+\) - Release dates<\/title>/i'
			),
		),
		'command' => array(
			'id' => '/^\s*(tt\d+)\s*$/i'
		)
	);
	
	function search( $query )
	{
		if ( ( $id = $this->googlesearch( $query ) ) !== false )
		{
			return $this->getFilm( $id );
		}
		return false;
	}
	
	function geturlregex(){
		return $this->_def['myurl'];
	}
	
	function getName(){
		return $this->_def['myname'];
	}
	
	function checkCache( $search ){
		global $api;
		$res = $api->db->select( '*', 'imdb_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row->imdbID;
		}else{
			return false;
		}
	}
	
	function getmoviefromurl( $url, $ignoreCache = false ){
		if($this->_debug) printf( "url: %s \n", $url );
		preg_match( $this->_def['myurl'], $url, $urlinfo );
		if( !$ignoreCache )
		{
			if( ( $movie = $this->getMoviefromdb( $urlinfo['1'] ) ) != false )
			{
				return $movie;
			}
		}
		if( ( $movie = $this->getFilm( $urlinfo['1'] ) ) != false)
		{
			return $movie;
		}else{
			return false;
		}
	}
	
	function getMoviefromdb( $id )
	{
		global $api;
		$res = $api->db->select( '*', 'imdb_film', array( 'imdbID' => $id ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row;
		}else{
			return false;
		}
	}
	
	function ismyurl( $url )
	{
		if( preg_match($this->_def['myurl'], $url ) != false )
		{
			return true;
		}else{
			return false;
		}
	}
	
	function processgenres( $result ){
		global $api;
		for( $i=0; $i < count( $result[0] ); $i++ )
			{
				$genre[$result[1][$i]] = $api->stringDecode( $result[1][$i] );
			}
		$genStr = ( count( $genre ) > 0 )? implode( ', ', $genre ):'';
		return $genStr;
	}
	
	function getFilm( $imdbID )
	{
		global $api;
		$res = $api->db->select( '*', 'imdb_film', array( 'imdbID' => $imdbID ), __FILE__, __LINE__ );		
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
        {
            $row = $api->db->fetch( $res );
		}
		$url = sprintf( $this->_def['url']['movie'], urlencode( $imdbID ) );
        if ( $this->_debug ) printf( "url: %s \n", $url );  
		if ( ( $page = $this->getUrl( $url, true ) ) !== false )
		{
			preg_match( $this->_def['regex']['film']['title'], $page, $title );
			preg_match_all( $this->_def['regex']['film']['country'], $page, $cList );
			preg_match_all( $this->_def['regex']['film']['genre'], $page, $gList );
            if ( $this->_debug ) var_dump( $cList );
            if ( $this->_debug ) var_dump( $title );
            if ( $this->_debug ) var_dump( $gList );                                                   
			if(!in_array("us",$cList[0])){
				if(!in_array("gb",$cList[0])){
					$titles = $this->GetAka($imdbID);
					if($titles['original']){
						$title[1] = $titles['original'];
					}
					$aka = $titles['aka'];
					
				}
			}
			$genStr = $this->processgenres($gList);
			if( stristr($title[2], 'TV '))
			{
				$title[2]=str_replace("TV ", "", $title[2]);
			}elseif( stristr($title[2], 'Video '))
			{
				$title[2]=str_replace("Video ", "", $title[2]);
			}
	        if ($this->_debug)
			{
				 printf( 'title: %s - new title: %s \n', $title[0], $title[2] );  			
			}
			$film = array(
				'imdbID' => $imdbID,
				'title' => substr($api->stringDecode( $title[1]),0),
				'year' => substr($api->stringDecode( $title[2]),1,4),
				'genre' => $genStr,
				'aka' => $api->stringDecode( $aka ),
				'url' => sprintf( $this->_def['url']['movie'], $imdbID ) );
			
			if ( empty( $film['title'] ) )
			{
				return false;
			}
			$film['title'] = str_replace( '"','',$film['title'] );
			if ( $nRows >= 1 )
				$api->db->update( 'imdb_film', $film, array( 'filmID' => $row->filmID ), __FILE__, __LINE__ );
			else
				$api->db->insert( 'imdb_film', $film, __FILE__, __LINE__ );
						
			return (object)$film;
		}
		else
		{
			return false;
		}
	}
	
	function googlesearch( $query )
	{
		global $api;
		
		$res = $api->db->select( '*', 'imdb_search', array('search' => $query ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		$url = sprintf( $this->_def['url']['search'], urlencode(strtolower($query)) );
        if ( $this->_debug ) printf( "url: %s \n", $url );
		if ( ( $page = $this->getUrl( $url ) ) !== false )
		{
			foreach( $this->_def['regex']['id'] as $regex )
			{
				if ( preg_match( $regex, $page, $filmID) )
				{
					$filmID[1] = 'tt'.$filmID[1];
					if ( $nRows >= 1 )
						$api->db->update( 'imdb_search', array( 'imdbID' => $filmID[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
					else
						$api->db->insert( 'imdb_search', array( 'imdbID' => $filmID[1], 'search' => $query ), __FILE__, __LINE__ );
                        
                    if ( $this->_debug ) printf( 'found film id: %s', $filmID[1] );  
					return $filmID[1];
				}
			}
			return false;
		}
		else
		{
			return false;
		}
	}
	
	function getUrl( $url, $redir = false )
	{
		$req =& new HTTP_Request( );
		$req->addHeader("Accept-Language", "en-US,en;q=0.8");
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->setURL( $url, array( 'timeout' => '30', 'readTimeout' => 30, 'allowRedirects' => $redir ) );
		$request = $req->sendRequest();
		if (PEAR::isError($request)) {
			unset( $req, $request );
			return false;
		} else {
			$body = $req->getResponseBody();
			if ( empty( $body ) )
				$body = $req->getResponseHeader( 'location');
			unset( $req, $request );
			return $body;
		}
	}
	
	function GetAka( $imdbID){
		global $api;
		$titles = array();
		$url = sprintf( $this->_def['url']['aka'], urlencode( $imdbID ) );
        if ( $this->_debug ) printf( "url: %s \n", $url );  
		if ( ( $page = $this->getUrl( $url, true ) ) !== false ){
			//I do the preg_match in three steps to respect the priority level.
			preg_match_all( $this->_def['regex']['film']['akaInt'], $page, $aka ); 
			if(!$aka[1]){
				preg_match_all( $this->_def['regex']['film']['akaUS'], $page, $aka );
				if(!$aka[1]){
					preg_match_all( $this->_def['regex']['film']['akaUK'], $page, $aka );
				}
			}
			if ( $this->_debug ) var_dump($aka);	
			preg_match( $this->_def['regex']['film']['original'], $page, $original );
			
			if ( $this->_debug ) var_dump($original);
			
			$titles = array("original"=>$original[1],"aka"=>$aka[1][0]);
			return $titles;
		}
	}
}
?>