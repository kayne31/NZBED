<?php
/**
 * Replace strstr()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2010 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>, James Wade <hm2k@php.net>
 * @link        http://php.net/function.strstr
 * @author      James Wade <hm2k@php.net>
 * @version     $Revision: 1.0 $
 * @since       PHP 5.3.0
 * @require     PHP 4.0.0 (strrev)
 */
function php_compat_strstr($haystack, $needle, $before_needle = false)
{
    if ($before_needle) {
      return strrev(array_pop(explode($n,strrev($h))));
    } else {
      return strstr($haystack, $needle);
    }
}
