<?php
/**
 * Replace getmxrr()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2009 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>, James Wade <hm2k@php.net>
 * @link        http://php.net/function.vsprintf
 * @author      James Wade <hm2k@php.net>
 * @version     $Revision: 1.0 $
 * @since       PHP 4.0
 * @require     PHP 4.0.3 (escapeshellarg)
 */

function php_compat_getmxrr($hostname, &$mxhosts, &$mxweight=false)
{
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    return win_getmxrr($hostname, $mxhosts, $mxweight);
  }
  else {
    user_error('getmxrr() is not supported on your operating system.',
      E_USER_WARNING);
    return;
  }
}

function getmxrr_win($hostname, &$mxhosts, &$mxweight=false)
{
	if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
    user_error('getmxrr() is not supported on your operating system',
      E_USER_WARNING);
    return;
  }
	if (empty($hostname)) {
    user_error('getmxrr() expects parameter 1 to be string, ' .
        gettype($hostname) . ' given', E_USER_WARNING);
    return false;
  }
  if (!is_array ($mxhosts)) {
    user_error('getmxrr() expects parameter 2 to be array, ' .
        gettype($mxhosts) . ' given', E_USER_WARNING);
    return false;
  }
	$exec='nslookup -type=MX '.escapeshellarg($hostname);
	@exec($exec, $output);
	if (empty($output)) return;
	$i=-1;
	foreach ($output as $line) {
		$i++;
		if (preg_match("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.+)$/i", $line, $parts)) {
		  $mxweight[$i] = trim($parts[1]);
		  $mxhosts[$i] = trim($parts[2]);
		}
		if (preg_match('/responsible mail addr = (.+)$/i', $line, $parts)) {
		  $mxweight[$i] = $i;
		  $mxhosts[$i] = trim($parts[1]);
		}
	}
	return ($i!=-1);
}

// Define
if (!function_exists('getmxrr')) {
    function getmxrr($hostname, &$mxhosts, &$mxweight=false) {
      return php_compat_getmxrr($hostname, $mxhosts, $mxweight);
    }
}
