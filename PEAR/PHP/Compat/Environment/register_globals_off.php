<?php
/**
 * Emulate enviroment register_globals=off
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/register_globals
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 274851 $
 */

if (ini_get('register_globals')) {
    $ignore = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER',
                '_ENV', '_FILES');

    $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES,
                isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
  
    foreach ($input as $k => $v) {
        if (!in_array($k, $ignore) && isset($GLOBALS[$k])) {
            unset($GLOBALS[$k]);
        }
    }

    // Register the change
    //ini_set('register_globals', 'off'); // Cannot be set at runtime (bug 15532)
	$GLOBALS['__PHP_Compat_ini']['register_globals'] = false;
}
