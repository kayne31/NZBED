<?php
/**
 * Usage example for HTTP_Client: a simple link checker.
 *
 * @category    HTTP
 * @package     HTTP_Client
 * @author      Alexey Borzov <avb@php.net>
 * @version     CVS: $Id: link-checker.php,v 1.3 2007/05/19 14:57:31 avb Exp $
 * @ignore
 */

/**
 * A simple HTTP client class.
 */ 
require_once 'HTTP/Client.php';
/**
 * Listener for HTTP_Request and HTTP_Client objects
 */
require_once 'HTTP/Request/Listener.php';

/**
 * A Listener-based link checker class
 *
 * @category    HTTP
 * @package     HTTP_Client
 * @ignore
 */ 
class HTTP_Client_LinkChecker extends HTTP_Request_Listener
{
   /**
    * Results of link checking ('url' => 'result')
    * @var array
    */
    var $_urls;

   /**
    * An URL being checked 
    * @var string
    */
    var $_checkedUrl;

   /**
    * An URL we were redirected to
    * @var string
    */
    var $_redirUrl;


    function update(&$subject, $event, $data)
    {
        switch ($event) {
            case 'httpSuccess':
                if ('' == $this->_redirUrl) {
                    $this->_urls[$this->_checkedUrl] = 'OK';
                } else {
                    $this->_urls[$this->_checkedUrl] = 'Moved to ' . $this->_redirUrl;
                }
                break;

            case 'httpError':
                $response =& $subject->currentResponse();
                $this->_urls[$this->_checkedUrl] = 'HTTP Error ' . $response['code'];
                break;

            case 'httpRedirect':
                $this->_redirUrl = $data;
                break;

            case 'request':
                $this->_checkedUrl = $data;
                $this->_redirUrl   = '';
        }
    }


   /**
    * Returns the link checking results 
    *
    * @access public
    * @return array
    */
    function getResults()
    {
        return $this->_urls;
    }
}

$urlList = array(
    'http://www.php.net/',
    'http://www.php.net/fsockopen',
    'http://pear.php.net/foobar.php'
);

$client  =& new HTTP_Client();
$checker =& new HTTP_Client_LinkChecker();
$client->attach($checker);

foreach ($urlList as $url) {
    $client->head($url);
}

var_dump($checker->getResults());
?>
