<?php

/**************************************************
 * NZBed
 * Copyright (c) 2008 Harry Bragg
 * tiberious.org
 * Module: anidb
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

class anidb
{
    /**
     * Lists the definitions for tvrage website
     *
     * @var array of regular expressions
     * @access public
     */
    var $_def          = array(
        'url' => array(
            'search' => 'http://anidb.net/perl-bin/animedb.pl?show=search&query=%s&do.fsearch=Search',
            'anime' => 'http://anidb.net/perl-bin/animedb.pl?show=anime&aid=%d',
            'report' => 'http://anidb.net/%s'
        ),
        'findAnime'             => '/<tr class="g_odd">\s+<td class="score">[0-9.]+<\/td>\s+<td class="type">.+<\/td>\s+<td class="id"><a href="http:\/\/anidb.net\/a\d+">(a\d+)<\/a><\/td>\s+<td class="title">(.+)<\/td>\s+<td class="excerpt">.*?<\/td>\s+<\/tr>/i',
        'anime'         => array(
            'mainTitle'         => '/<tr class=".+?">\s+<th class="field">Main Title<\/th>\s+<td class="value">(.+)\s+\(<a class="shortlink" href="http:\/\/anidb.net\/a\d+">(a\d+)<\/a>\)<\/td>\s+<\/tr>/i',
            'officialTitle'     => '/<tr class="(?:g_odd )?official verified (yes|no)">\s+<th class="field">Official Title<\/th>\s+<td class="value">\s+<span class="icons">\s+(?s:(.+?))<\/span>\s+<label>(.+)<\/label><\/td>\s+<\/tr>/i',
            'officialLang'      => '/<span>(..)<\/span>/i',
            'type'              => '/<tr class="(?:g_odd )?type">\s+<th class="field">Type<\/th>\s+<td class="value">(TV Series|OVA), .+? episodes?<\/td>\s+<\/tr>/i',
						'episode'           => '/<td class="id eid">\s+<a href="(.+?)">%d<\/a>\s+<\/td>\s+<td class="title">\s+<label title="[^"]+">(.+?)\s+<\/label>\s+<\/td>/i'
        ),
        'error' => '/<h3>ERROR<\/h3>/i'
    );
    
    var $_debug = false;

    /*****************************************************
     * Main functions
     *****************************************************/
    
    /**
     * Get URL
     *
     * @param string $url - url to get
     * @return contents of the page
     * @access public
     */
    function getUrl( $url )
    {
		if ( $this->_debug ) printf("getUrl( url:%s );\n", $url );

        $req = new HTTP_Request( );
        $req->setMethod(HTTP_REQUEST_METHOD_GET);
        $req->setURL( $url, array( 'timeout' => '30', 'readTimeout' => 30, 'allowRedirects' => true ) );
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
     * look for a anime show
     *
     * @param string $query - Anime search query
     * @return string - anidb.net id
     * @access public
     */
    function findAnime( $query, $ignoreCache = false )
    {
				if ( $this->_debug ) printf( "findAnime( query:%s, ignoreCache:%d );\n", $query, $ignoreCache );
        global $api;
        
        $res = $api->db->select( '*', 'anidb_search', array('search' => $query ), __FILE__, __LINE__ );
        
        $nRows = $api->db->rows( $res );
        
        // check the cache
        if ( $nRows >= 1 )
        {
            $row = $api->db->fetch( $res );
            if ( $row->fanidbID != '')
            {
                return $row->fanidbID;   
            }
            else if ( ( mt_rand(1, 100) <= (100 * 0.9) ) &&
                 ( $ignoreCache == false ) )
            {
                return $row->anidbID; 
            }
        }
                 
        // find anime
        $url = sprintf( $this->_def['url']['search'], urlencode( strtolower( $query ) ) );
        if ( ( $page = $this->getUrl( $url ) ) !== false )
        {
            if ( preg_match($this->_def['findAnime'], $page, $tvurl) )
            {
                if ($this->_debug)
                {
                    echo 'Found anidbID: ';
                    print_r( $tvurl);
                }
                if ( $nRows >= 1 )
                    $api->db->update( 'anidb_search', array( 'anidbID' => $tvurl[1] ), array( 'search' => $query ), __FILE__, __LINE__ );
                else
                    $api->db->insert( 'anidb_search', array( 'anidbID' => $tvurl[1], 'search' => $query ), __FILE__, __LINE__ );
                return $tvurl[1];
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    
    function getFAnime( $query, $ignoreCache = false )
    {
        if ( ( $anidbID = $this->findAnime( $query, $ignoreCache ) ) !== false )
        {                
            return $this->getAnime( $anidbID, $ignoreCache );
        }
        else
            return false;
    }
    
    /**
     * Retrieve anime information from anidb
     *  
     * @param string $anidbID - anidb ID
     * @return array - Anime information
     * @access public
     */
    function getAnime( $anidbID, $ignoreCache = false )
    {
        global $api;

        $res = $api->db->select( '*', 'anidb_anime', array( 'anidbID' => $anidbID ), __FILE__, __LINE__ );
        
        $nRows = $api->db->rows( $res );
        
        if ( $nRows >= 1 )
            $row = $api->db->fetch( $res );
    
        // check cache
        if ( ( $nRows >= 1 ) &&
            // ( mt_rand(1, 100) <= (100 * 0.9) ) &&
             ( $ignoreCache == false ) )
        {
            return $row;
        }
            
        $url = sprintf( $this->_def['url']['anime'], substr($anidbID,1) );
        if ( ( $page = $this->getUrl( $url ) ) !== false )
        {
            if ( preg_match( $this->_def['error'], $page ) )
            {
                return false;
            }
                    
            preg_match( $this->_def['anime']['mainTitle'], $page, $main );
            preg_match_all( $this->_def['anime']['officialTitle'], $page, $offTitle );
            preg_match( $this->_def['anime']['type'], $page, $type );

            if ($this->_debug)
            {
                echo 'Main: '.$main[1];
                echo 'ID: '.$main[2];
                echo 'Type: '.$type[1];   
                print_r( $offTitle );
            }
            
            for ($i=0; $i < count( $offTitle[0] ); $i++)
            {
                preg_match_all( $this->_def['anime']['officialLang'], $offTitle[2][$i], $langs );
                for ($j=0; $j < count($langs[0]); $j++)
                {
                    if ($langs[1][$j] == 'en')
                    {
                        $enTitle = $api->stringDecode( $offTitle[3][$i] );
                    }   
                }
            }
            
            if (!isset($enTitle))
            {
                 $enTitle = $api->stringDecode( $main[1] );  
            }
            
            $anime = array(
                'anidbID' => $main[2],
                'name' => $enTitle,
                'type' => $api->stringDecode( $type[1] ),
                'url' => sprintf( $this->_def['url']['report'], $anidbID) );
                
            if ($this->_debug)
            {
                print_r( $anime );   
            }

            if ( empty( $anime['name'] ) )
            {
                if ( $nRows >= 1 )
                {
                    return $row;
                }
                return false;
            }
                
            if ( $nRows >= 1 )
            {
                if ( !empty( $row->fname ) )
                    $anime['fname'] = $row->fname;
            }
            
            if ( $nRows >= 1 )
            {
                $api->db->update( 'anidb_anime', $anime, array( 'animeID' => $row->animeID ), __FILE__, __LINE__ );
            }
            else
                $api->db->insert( 'anidb_anime', $anime, __FILE__, __LINE__ );

            return (object)$anime;
        }
        else 
        {
            return false;
        }

    }
		
		function getEpisode( &$anime, $epNum )
		{
            
          			if ( $this->_debug ) printf("anidb::getEpisode( anime, epNum:%d )\n", $epNum );
			
			global $api;

      $url = sprintf( $this->_def['url']['anime'], substr($anime->anidbID,1) );
      if ( ( $page = $this->getUrl( $url ) ) !== false )
      {
        if ( preg_match( $this->_def['error'], $page ) )
        {
			//		print "error\n";
          return false;
        }
				
				$r = sprintf( $this->_def['anime']['episode'], $epNum );
				if ( $this->_debug ) printf( "%s\n", $r );
				if ( preg_match( $r, $page, $match ) )
				{
//					$anime->url = sprintf( $this->_def['url']['report'], $match[1] );
					$anime->ep = $api->stringDecode( $match[2] );
					if ( $this->_debug )
						print_r($anime);
					return true;
				}
			}
			return false;
		}
}

?>
