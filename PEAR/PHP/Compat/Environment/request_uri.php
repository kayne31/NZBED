<?php
/**
 * Emulate server enviroment variable $_SERVER['REQUEST_URI']
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>, James Wade <hm2k@php.net>
 * @link        http://php.net/reserved.variables.server
 * @author      James Wade <hm2k@php.net>
 * @version     $Revision: 1.0 $
 */

// wrap everything in a function to keep global scope clean
function php_compat_request_uri(&$server)
{
  if (!isset($server['REQUEST_URI']) && isset($server)) {
    //ISAPI_Rewrite 3.x
    if (isset($server['HTTP_X_REWRITE_URL'])) {
      $server['REQUEST_URI'] = $server['HTTP_X_REWRITE_URL'];
    }
    //ISAPI_Rewrite 2.x w/ HTTPD.INI configuration
    elseif (isset($server['HTTP_REQUEST_URI'])){
      $server['REQUEST_URI'] = $server['HTTP_REQUEST_URI'];
    }
    //ISAPI_Rewrite isn't installed or not configured
    else {
      $server['HTTP_REQUEST_URI'] = isset($server['SCRIPT_NAME'])?$server['SCRIPT_NAME']:$server['PHP_SELF'];
      if (isset($server['QUERY_STRING'])) { $server['HTTP_REQUEST_URI'] .= '?'.$server['QUERY_STRING']; }
      $server['REQUEST_URI'] = $server['HTTP_REQUEST_URI'];
    }
  }
}

php_compat_request_uri($HTTP_SERVER_VARS);
php_compat_request_uri($_SERVER);
