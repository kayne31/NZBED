<?php
/**
 * Replace sinh()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.sinh
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision: 269597 $
 * @since       PHP 5
 * @require     PHP 3.0.0
 */
function php_compat_sinh($n)
{
    return 0.5 * (exp($n) - exp(-$n));
}

if (!function_exists('sinh')) {
    function sinh($n)
    {
	return php_compat_sinh($n);
    }
}
