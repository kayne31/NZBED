<?php
/**
 * Emulate enviroment register_long_arrays=off
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/manual/en/ini.core.php#ini.register-long-arrays
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 274851 $
 */
unset(
    $GLOBALS['HTTP_GET_VARS'],
    $GLOBALS['HTTP_POST_VARS'],
    $GLOBALS['HTTP_COOKIE_VARS'],
    $GLOBALS['HTTP_SERVER_VARS'],
    $GLOBALS['HTTP_ENV_VARS'],
    $GLOBALS['HTTP_FILES_VARS']
);

// Register the change
//ini_set('register_long_arrays', 'off'); // Cannot be set at runtime (bug 15532)
$GLOBALS['__PHP_Compat_ini']['register_long_arrays'] = false;
