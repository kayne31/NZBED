<?php
/**
 * Replace function htmlspecialchars()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.htmlspecialchars
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 274499 $
 * @since       PHP 4.0.0
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_htmlspecialchars($string, $quote_style = ENT_COMPAT, $charset = 'ISO-8859-1', $double_encode = true)
{
    if (version_compare(PHP_VERSION, '5.2.3', 'ge')) {
        return htmlspecialchars($string, $quote_style, $charset, $double_encode);
    }

    // Sanity check
    if (!is_scalar($string)) {
        user_error('htmlspecialchars() expects parameter 1 to be string, ' .
            gettype($string) . ' given', E_USER_WARNING);
        return;
    }

    if (!is_int($quote_style) && $quote_style !== null) {
        user_error('htmlspecialchars() expects parameter 2 to be integer, ' .
            gettype($quote_style) . ' given', E_USER_WARNING);
        return;
    }
	
    if (!is_scalar($charset)) {
        user_error('htmlspecialchars() expects parameter 3 to be string, ' .
				   gettype($charset) . ' given', E_USER_WARNING);
        return;
    }
	
    if (!is_bool($double_encode)) {
        user_error('htmlspecialchars() expects parameter 4 to be bool, ' .
				   gettype($double_encode) . ' given', E_USER_WARNING);
        return;
    }
    
    // mb support
    if ($charset != 'ISO-8859-1') {
        if (!function_exists('mb_substr')) {
            user_error('php_compat_htmlspecialchars requires PHP >= 4.0.6 and '
                . 'the mbstring extension to support the $charset argument.',
                E_USER_WARNING);
            return;
        }
        $len = mb_strlen($string, $charset);
        $ret = '';
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($string, $i, 1, $charset);
            
            switch ($char) {
            case '&':
                if (!$double_encode && $i < $len - 2) {
                    // look ahead to see if we have an existing entity
                    $foundEntity = false;
                    $type = mb_substr($string, $i + 1, 1, $charset);
                    if ($type == '#') {
                        // numeric entities
                        $type2 = mb_substr($string, $i + 2, 1, $charset);
                        if ($type2 == 'x') {
                            $validator = 'ctype_xdigit';
                        } else if (ctype_digit($type2)) {
                            $validator = 'ctype_digit';
                            $foundEntity = true;
                        } else {
                            // invalid entity
                            $ret .= '&amp;';
                            break;
                        }
                    } else if (ctype_alnum($type)) {
                        $validator = 'ctype_alnum';
                        $foundEntity = true;
                    } else {
                        $ret .= '&amp;';
                        break;
                    }
                    for ($j = $i + ($type == '#' ? 3 : 2); $j < $len; $j++) {
                        $tempChar = mb_substr($string, $j, 1, $charset);
                        if ($foundEntity && $tempChar == ';') {
                            $ret .= mb_substr($string, $i, $j - $i + 1, $charset);
                            $i = $j;
                            break 2;
                        }
                        if ($validator($tempChar)) {
                            $foundEntity = true;
                        } else {
                            // invalid entity
                            $ret .= '&amp;';
                            break;
                        }
                    }
                }
                $ret .= '&amp;';
                break;
            case '"':
                $ret .= $quote_style & ENT_NOQUOTES ? '"' : '&quot;';
                break;
            case "'":
                $ret .= $quote_style & ENT_COMPAT || $quote_style & ENT_NOQUOTES ? "'" : '&#039;';
                break;
            case '<':
                $ret .= '&lt;';
                break;
            case '>':
                $ret .= '&gt;';
                break;
            default:
                $ret .= $char;
                break;
            }
        }
        return $ret;
    }
	if (!$double_encode) {
		return preg_replace('/[^&]|&(?!(?:#(?:x[a-f\d]+|\d+)|\w+);)/e', 'htmlspecialchars("$0", $quote_style)', $string);
	}
	
    return htmlspecialchars($string, $quote_style);
}

