<?php
/**
 * Replace fputcsv()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2010 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.fprintf
 * @author      Twebb <twebb@boisecenter.com>
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 301763 $
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_fputcsv($handle, $fields, $delimiter = ',', $enclosure = '"')
{
    // Sanity Check
    if (!is_resource($handle)) {
        user_error('fputcsv() expects parameter 1 to be resource, ' .
            gettype($handle) . ' given', E_USER_WARNING);
        return false;
    }

    
    $str = '';
    foreach ($fields as $cell) {
        $cell = str_replace($enclosure, $enclosure . $enclosure, $cell);

        if (strchr($cell, $delimiter) !== false ||
            strchr($cell, $enclosure) !== false ||
            strchr($cell, "\n") !== false) {
            
            $str .= $enclosure . $cell . $enclosure . $delimiter;
        } else {
            $str .= $cell . $delimiter;
        }
    }

    fputs($handle, substr($str, 0, -1) . "\n");

    return strlen($str);
}


// Define
if (!function_exists('fputcsv')) {
    function fputcsv($handle, $fields, $delimiter = ',', $enclosure = '"')
    {
        return php_compat_fputcsv($handle, $fields, $delimiter, $enclosure);
    }
}
