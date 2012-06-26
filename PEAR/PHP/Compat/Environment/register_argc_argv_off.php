<?php
/**
 * Emulate enviroment register_argc_argv=off
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/manual/en/ini.core.php#ini.register-argc-argv
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 274851 $
 */

if (isset($_GLOBALS['argc']) || isset($_SERVER['argc'])) {
    unset($GLOBALS['argc'], $GLOBALS['argv'], $_SERVER['argc'], $_SERVER['argv']);

    // Register the change
    //ini_set('register_argc_argv', 'off'); // Cannot be set at runtime (bug 15532)
	$GLOBALS['__PHP_Compat_ini']['register_argc_argv'] = false;
}
