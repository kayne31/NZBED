<?php
require_once( 'HTTP/Request.php' );

class amazon{
	var $debug = false;
	var $error;
	var $apikey = 'AKIAJ67X5EEZCUVPE42A';
	var $secret = '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';
	var $_fromXML;
	var $_def = array(
			'myname' => 'amazon'
	);
	
	function search($search, $ignoreCache = false){
		if ( ( $book = $this->apisearch( $search ) ) !== false )
		{
			return (object)$book;
		}
		return false;
	}
	
	function apisearch( $search ){
		$search = urlencode($search);
		$method = "GET";
		$host = "ecs.amazonaws.com";
		$uri = "/onca/xml";
		// additional parameters
		$params["Service"] = "AWSECommerceService";
		$params["AWSAccessKeyId"] = $this->apikey;
		$params["Operation"] = "ItemSearch";
		$params["Keywords"] = $search;
		$params["SearchIndex"] = "Books";
		$params["AssociateTag"] = "6994-6033-8070";
		// GMT timestamp
		$params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
		// sort the parameters
		ksort($params);
		// create the canonicalized query
		$canonicalized_query = array();
		foreach ($params as $param=>$value)
		{
		    $param = str_replace("%7E", "~", rawurlencode($param));
		    $value = str_replace("%7E", "~", rawurlencode($value));
		    $canonicalized_query[] = $param."=".$value;
		}
		$canonicalized_query = implode("&", $canonicalized_query);

		// create the string to sign
		$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
		// calculate HMAC with SHA256 and base64-encoding
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->secret, True));
		// encode the signature for the request
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		// create request
		$request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
		//print($request).PHP_EOL;
		if( ( $xpage = $this->getXmlUrl($request) ) != false )
		{
			//print_r($xpage);
			if($xpage['Items']['TotalResults'] != 0)
			{
				if(isset($xpage['Items']['Item'][0]))
				{
					$result = $xpage['Items']['Item'][0];
					$book = array(
						'asin' => $result['ASIN'],
						'title' => $result['ItemAttributes']['Title'],
						'author' => $result['ItemAttributes']['Author'],
						'url' => $result['DetailPageURL']
					);
				}else if(isset($xpage['Items']['Item']))
				{
					$result = $xpage['Items']['Item'];
					$book = array(
						'asin' => $result['ASIN'],
						'title' => $result['ItemAttributes']['Title'],
						'author' => $result['ItemAttributes']['Author'],
						'url' => $result['DetailPageURL']
					);
				}
				return $book;
			}
		}
		return false;
	}
	
	function checkCache( $search ){
		global $api;
		$res = $api->db->select( '*', 'amazon_search', array('search' => $search ), __FILE__, __LINE__ );
		$nRows = $api->db->rows( $res );
		if ( $nRows >= 1 )
		{
			$row = $api->db->fetch( $res );
			return $row->isbn;
		}else{
			return false;
		}
	}
	
	function getUrl( $url ){
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
	
	function amazon(){
		$options = array(
				XML_UNSERIALIZER_OPTION_RETURN_RESULT    => true,
				XML_UNSERIALIZER_OPTION_FORCE_ENUM       => array(
						'item',
						'title',
						'author'
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
	
	function getName(){
		return $this->_def['myname'];
	}
	
	function geturlregex(){
		return false;
	}
}
?>