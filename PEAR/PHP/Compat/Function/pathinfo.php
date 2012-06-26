<?php
/**
 * Replace function pathinfo() for PATHINFO_FILENAME support
 *
 * PHP versions 4 and 5
 *
 * @category  PHP
 * @package   PHP_Compat
 * @author    James Wade <hm2k@php.net>
 * @copyright 2009 James Wade
 * @license   LGPL - http://www.gnu.org/licenses/lgpl.html
 * @version   $CVS: 1.0 $
 * @link      http://php.net/function.pathinfo
 * @since     PHP 5.2.0
 * @require   PHP 4.0.0 (user_error)
 */
if (!defined('PATHINFO_FILENAME')) {
    define('PATHINFO_FILENAME', 8);
}
/**
 * Returns information about a file path
 *
 * @param string $path    The path being checked.
 * @param int    $options See @link
 *
 * @return array
 */
function php_compat_pathinfo($path = false, $options = false)
{
    // Sanity check
    if (!is_scalar($path)) {
        user_error('pathinfo() expects parameter 1 to be string, '
        . gettype($path) . ' given', E_USER_WARNING);
        return;
    }
    if (version_compare(PHP_VERSION, '5.2.0', 'ge')) {
        return pathinfo($path, $options);
    }
    if ($options & PATHINFO_FILENAME) {
        //bug #15688
        if (strpos($path, '.') !== false) {
            $filename = substr($path, 0, strrpos($path, '.'));
        }
        if ($options === PATHINFO_FILENAME) {
            return $filename;
        }
        $pathinfo             = pathinfo($path, $options);
        $pathinfo['filename'] = $filename;
        return $pathinfo;
    }
    return pathinfo($path, $options);
}
