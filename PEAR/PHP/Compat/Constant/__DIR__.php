<?php
/**
 * Replace directory of the file constant
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2009 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/language.constants.predefined
 * @author      James Wade <hm2k@php.net>
 * @version     $Revision: 1.0 $
 * @since       PHP 5
 */
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}
