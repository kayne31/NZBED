<?php
/**
 * Replace json_decode()
 *
 * PHP versions 4 and 5
 *
 * @category  PHP
 * @package   PHP_Compat
 * @license   LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright 2004-2010 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>, James Wade <hm2k@php.net>
 * @link      http://php.net/function.json_decode
 * @author    James Wade <hm2k@php.net>
 * @version   $Revision: 1.0 $
 * @since     5.3.0
 */
//pear install Services_JSON-1.0.1
require_once 'Services/JSON.php';

if (!function_exists('json_decode')){
  function json_decode($content, $assoc=false) {
    $json = $assoc?new Services_JSON(SERVICES_JSON_LOOSE_TYPE):new Services_JSON;
    return $json->decode($content);
  }
}