<?php


$path = './PEAR/';

set_include_path(get_include_path() . PATH_SEPARATOR . $path);
ini_set("display_errors", 0);
define( 'INCLUDEPATH', './' );
//we include all the Category Extensions they will handle the individual extensions
require_once( INCLUDEPATH.'mysql.inc.php' );
require_once( INCLUDEPATH.'ed.php' );
require_once( INCLUDEPATH.'tv.php' );
require_once( INCLUDEPATH.'movies.php' );
require_once( INCLUDEPATH.'games.php' );
require_once( INCLUDEPATH.'anime.php' );
require_once( INCLUDEPATH.'music.php' );

require_once( 'XML/Serializer.php' );

class api
{
	var $xml;	
	var $db;
	var $ed;
	var $tv;
	var $games;
	var $anime;
	var $movies;
	var $music;
	var $ext_Array;// this will hold all the extensions as well makes it quicker to scan for a url match  also may use it later for rest

	function api( $ids, $cache )
	{
		global $db;
		global $ed;
		$this->db = $db;
		
		$this->tv = new tv();
		$this->movies = new movies();
		$this->games = new games();
		$this->anime = new anime();
		$this->music = new music();
		$this->ed = $ed;
		$this->ext_Array[]= $this->tv;
		$this->ext_Array[]= $this->movies;
		$this->ext_Array[]= $this->games;
		$this->ext_Array[]= $this->anime;
		$this->ext_Array[]= $this->music;
		
	 	$options = array(
			XML_SERIALIZER_OPTION_INDENT           => '    ',
			XML_SERIALIZER_OPTION_RETURN_RESULT    => true,
			XML_SERIALIZER_OPTION_ATTRIBUTES_KEY   => '_attributes',
			XML_SERIALIZER_OPTION_ROOT_NAME        => 'nzbed',
			XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
			XML_SERIALIZER_OPTION_XML_ENCODING     => 'UTF-8',
			XML_SERIALIZER_OPTION_MODE			   => XML_SERIALIZER_MODE_SIMPLEXML
		);
	 	
		$this->xml = new XML_Serializer( $options );
	}
	
	function getextArray(){
		return $this->ext_Array;
	}
	
	function getInfo( $string, $cat )
	{
		if ( ( $report = $this->ed->Query( $string, $cat ) ) === false )
		{
			$report = array(
				'error' => $this->ed->_error
			);

			// check query:
			$exist = $this->db->select( 'queryID', 'query_fail', array( 'query' => $string ), __FILE__, __LINE__ );

			if ( $this->db->rows( $exist ) == 0 )
			{
				$data = array(
					'type' => $cat,
					'query' => $string,
					'error' => $this->ed->_error,
					'IP' => $_SERVER['REMOTE_ADDR'],
					'date' => time(),
				);

				$this->db->insert( 'query_fail', $data, __FILE__, __LINE__ );
			}
		}
		return $report;		
	}
	
	function toXML( $array )
	{
		return $this->xml->serialize( $array );
	}
	
	function stringDecode( $string )
	{
		//$str = trim( html_entity_decode( $string, ENT_QUOTES, 'ISO-8859-15' ) );
		$str = $string;
		$str = preg_replace('~&#x([0-9a-f]+);~ei', '$this->code2utf(hexdec("\\1"))', $str);
		$str = preg_replace('~&#([0-9]+);~e', '$this->code2utf("\\1")', $str);
		return $str;
	}
	
    function code2utf($number)
    {
        if ($number < 0)
            return FALSE;
       
        if ($number < 128)
            return chr($number);
       
        // Removing / Replacing Windows Illegals Characters
        if ($number < 160)
        {
                if ($number==128) $number=8364;
            elseif ($number==129) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==130) $number=8218;
            elseif ($number==131) $number=402;
            elseif ($number==132) $number=8222;
            elseif ($number==133) $number=8230;
            elseif ($number==134) $number=8224;
            elseif ($number==135) $number=8225;
            elseif ($number==136) $number=710;
            elseif ($number==137) $number=8240;
            elseif ($number==138) $number=352;
            elseif ($number==139) $number=8249;
            elseif ($number==140) $number=338;
            elseif ($number==141) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==142) $number=381;
            elseif ($number==143) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==144) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==145) $number=8216;
            elseif ($number==146) $number=8217;
            elseif ($number==147) $number=8220;
            elseif ($number==148) $number=8221;
            elseif ($number==149) $number=8226;
            elseif ($number==150) $number=8211;
            elseif ($number==151) $number=8212;
            elseif ($number==152) $number=732;
            elseif ($number==153) $number=8482;
            elseif ($number==154) $number=353;
            elseif ($number==155) $number=8250;
            elseif ($number==156) $number=339;
            elseif ($number==157) $number=160; // (Rayo:) #129 using no relevant sign, thus, mapped to the saved-space #160
            elseif ($number==158) $number=382;
            elseif ($number==159) $number=376;
        } //if
       
        if ($number < 2048)
            return chr(($number >> 6) + 192) . chr(($number & 63) + 128);
        if ($number < 65536)
            return chr(($number >> 12) + 224) . chr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
        if ($number < 2097152)
            return chr(($number >> 18) + 240) . chr((($number >> 12) & 63) + 128) . chr((($number >> 6) & 63) + 128) . chr(($number & 63) + 128);
       
       
        return FALSE;
    } //code2utf()
}
//print "start";


if ( isset( $_REQUEST['q'] ) )
{
	//creating ed here so it can be a global for the extensions
	$ed= new ed( isset($_REQUEST['i']) ? $_REQUEST['i'] : false, isset($_REQUEST['c']) ? $_REQUEST['c'] : false );
	$api = new api( isset($_REQUEST['i']) ? $_REQUEST['i'] : false, isset($_REQUEST['c']) ? $_REQUEST['c'] : false);
	//setting the primary search provider for movies if set.  Default is TMDB.  For new movie extension just put in here and in nzbed extension
	if( isset( $_REQUEST['m'] ) )
	{
		switch( $_REQUEST['m'] ){
			case 0:
				$api->movies->setPrimary( 'tmdb' );
				break;
			case 1:
				$api->movies->setPrimary( 'imdb' );
				break;
			case 2:
				$api->tv->setPrimary( 'tvrage' );
				break;
			case 3:
				$api->tv->setPrimary( 'tvdb' );
				break;
		}
	}
	
	header( 'Content-type: text/xml' );

	$arr = $api->getInfo( $_REQUEST['q'], isset($_REQUEST['t']) ? $_REQUEST['t'] : false);

	echo $api->toXML( $arr );
}

?>
