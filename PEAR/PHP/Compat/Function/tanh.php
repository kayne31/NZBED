<?php
/**
 * Replace tanh()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.tanh
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision: 269597 $
 * @since       PHP 5
 * @require     PHP 3.0.0
 */
function php_compat_tanh($n)
{
    return (exp(2 * $n) - 1) / (exp(2 * $n) + 1);
}

if (!function_exists('tanh')) {
    function tanh($n)
    {
	return php_compat_tanh($n);
    }
}
