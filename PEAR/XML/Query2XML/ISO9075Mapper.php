<?php
/**
 * This file contains the class XML_Query2XML_ISO9075Mapper.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: ISO9075Mapper.php,v 1.3 2007/11/19 01:36:56 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * PEAR_Exception is used as the parent for XML_Query2XML_ISO9075Mapper_Exception.
 */
require_once 'PEAR/Exception.php';

/**
 * I18N_UnicodeString is used for converting UTF-8 to Unicode and vice versa.
 */
require_once 'I18N/UnicodeString.php';

/**
 * Maps SQL identifiers to XML names according to Final Committee Draft for
 * ISO/IEC 9075-14:2005, section "9.1 Mapping SQL <identifier>s to XML Names".
 *
 * ISO/IEC 9075-14:2005 is available online at
 * http://www.sqlx.org/SQL-XML-documents/5FCD-14-XML-2004-07.pdf
 *
 * A lot of characters are legal in SQL identifiers but cannot be used within
 * XML names. To begin with, SQL identifiers can contain any Unicode character
 * while XML names are limited to a certain set of characters. E.g the
 * SQL identifier "<21yrs in age" obviously is not a valid XML name.
 * '#', '{', and '}' are also not allowed. Fully escaped SQL identifiers
 * also must not contain a column (':') or start with "xml" (in any case
 * combination). Illegal characters are mapped to a string of the form
 * _xUUUU_ where UUUU is the Unicode value of the character.
 *
 * The following is a table of example mappings:
 * <pre>
 * +----------------+------------------------+------------------------------------+
 * | SQL-Identifier | Fully escaped XML name | Comment                            |
 * +----------------+------------------------+------------------------------------+
 * | dept:id        | dept_x003A_id          | ":" is illegal                     |
 * | xml_name       | _x0078_ml_name         | must not start with [Xx][Mm][Ll]   |
 * | XML_name       | _x0058_ML_name         | must not start with [Xx][Mm][Ll]   |
 * | hire date      | hire_x0020_date        | space is illegal too               |
 * | Works@home     | Works_x0040_home       | "@" is illegal                     |
 * | file_xls       | file_x005F_xls         | "_" gets mapped if followed by "x" |
 * | FIRST_NAME     | FIRST_NAME             | no problem here                    |
 * +----------------+------------------------+------------------------------------+
 * </pre>
 * 
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_ISO9075Mapper
{
    /**
     * This method maps an SQL identifier to an XML name according to
     * FCD ISO/IEC 9075-14:2005.
     *
     * @param string $sqlIdentifier The SQL identifier as a UTF-8 string.
     *
     * @return string The fully escaped XML name.
     * @throws XML_Query2XML_ISO9075Mapper_Exception If $sqlIdentifier was a
     *                                               malformed UTF-8 string.
     */
    public static function map($sqlIdentifier)
    {
        /*
         * S as defined in section 9.1, paragraph 1 with the difference that
         * if N is the number of characters in SQLI the characters of SQLI,
         * in order from left to right are S[0], S[1], ..., S[N-1].
         */
        $S = self::_utf8ToUnicode($sqlIdentifier);
        
        /*
         * X as defined in section 9.1, paragraph 4 with the differnce that
         * for each i between 0 (zero) and N-1, X[i] will be the Unicode
         * character string.
         */
        $X = array();
        
        /*
         * section 9.1, paragraph 4 lit a
         * a) If S[i] has no mapping to Unicode (i.e., TM(S[i]) is undefined),
         * then X[i] is implementation-defined.
         */
        for ($i = 0; $i < count($S); $i++) {
            if (self::_unicodeToUtf8($S[$i]) == ':') {
                // section 9.1, paragraph 4 lit b: If Si is <colon>, then
                
                if ($i == 0) {
                    // i) If i = 0 (zero), then let Xi be _x003A_.
                    $X[$i] = '_x003A_';
                } else {
                    // ii) If EV is fully escaped, then let Xi be _x003A_.
                    $X[$i] = '_x003A_';
                }
                /*
                 * iii) Otherwise, let X[i] be T[i]
                 * we always do a full escape - therefore we do
                 * not have to implement iii)
                 */
                
            } elseif (
                $i < count($S) - 1 &&
                self::_unicodeToUtf8($S[$i]) == '_' &&
                self::_unicodeToUtf8($S[$i+1]) == 'x'
            ) {
                /*
                 * section 9.1, paragraph 4 lit c: if i < N–1, S[i] is <underscore>,
                 * and S[i+1] is the lowercase letter x, then let X[i] be _x005F_.
                 */
                $X[$i] = '_x005F_';
                
            } elseif (
                !self::_isValidNameChar($S[$i]) ||
                $i == 0 &&
                !self::_isValidNameStartChar($S[$i])
            ) {
                /*
                 * section 9.1, paragraph 4 lit e: the SQL-implementation supports
                 * Feature X211, "XML 1.1 support", and either T[i] is not a valid
                 * XML 1.1 NameChar, or i = 0 (zerno) and T[0] is not a valid
                 * XML 1.1 NameStartChar
                 */
    
                $X[$i] = dechex($S[$i]);
                if (strlen($X[$i]) < 4) {
                    /*
                     * ii) 1) If U1 = 0 (zero), U2 = 0 (zero), U3 = 0 (zero), and
                     * U4 = 0 (zero), then let X[i} be _xU5U6U7U8_.
                     */
                    $X[$i] = str_pad($X[$i], 4, '0', STR_PAD_LEFT);
                } elseif (strlen($X[$i]) > 4) {
                    // ii) 2) Otherwise, let X[i] be _xU3U4U5U6U7U8_.
                    $X[$i] = str_pad($X[$i], 8, '0', STR_PAD_LEFT);
                }
                $X[$i] = '_x' . $X[$i] . '_';
            } else {
                /*
                 * section 9.1, paragraph 4 lit f: Otherwise, let X[i] be T[i].
                 * NOTE 21 — That is, any character in SQLI that does not occasion
                 * a problem as a character in an XML 1.0 NCName or XML 1.1 NCName
                 * is simply copied into the result.
                 */
                $X[$i] = self::_unicodeToUtf8($S[$i]);
            }
        }
        
        if (
            count($S) >=3 &&
            strpos(
                strtolower(
                    self::_unicodeToUtf8($S[0])
                    . self::_unicodeToUtf8($S[1])
                    . self::_unicodeToUtf8($S[2])
                ),
                'xml'
            ) === 0
        ) {
            /*
             * section 9.1, paragraph 4 lit d: if EV is fully escaped,
             * i = 0 (zero), N >= 3, S[0] is either the uppercase letter
             * X or the lowercase letter x, S[1] is either the uppercase
             * letter M or the lowercase letter m, and S[2] is either the
             * uppercase letter L or the lowercase letter l, then
             */
            
            if (self::_unicodeToUtf8($S[0]) == 'x') {
                // i) If S[0] is the lowercase letter x, then let X[0] be _x0078_.
                $X[0] = '_x0078_';
            } elseif (self::_unicodeToUtf8($S[0]) == 'X') {
                // ii) If S[0] is the uppercase letter X, then let X[0] be _x0058_.
                $X[0] = '_x0058_';
            }
        }
        
        /*
         * section 9.1, paragraph 5: let XMLN be the character string concatenation
         * of X[0], X[1], ..., and X[N-1] in order from left to right.
         */
        $XMLN = '';
        for ($i = 0; $i < count($X); $i++) {
            $XMLN .= $X[$i];
        }
        return $XMLN;
    }
    
    /**
     * Returns whether $char is a valid XML 1.1. NameStartChar.
     * NameStartChar is defined as:
     * NameStartChar ::= ":" | [A-Z] | "_" | [a-z] | [#xC0-#xD6] | [#xD8-#xF6] |
     *                   [#xF8-#x2FF] | [#x370-#x37D] | [#x37F-#x1FFF] |
     *                   [#x200C-#x200D] | [#x2070-#x218F] | [#x2C00-#x2FEF] |
     *                   [#x3001-#xD7FF] | [#xF900-#xFDCF] | [#xFDF0-#xFFFD] |
     *                   [#x10000-#xEFFFF]
     *
     * @param int $c A unicode character as an integer.
     *
     * @return boolean Wheather $c is a valid NameStartChar.
     * @link http://www.w3.org/TR/xml11/
     */
    private static function _isValidNameStartChar($c)
    {
        return preg_match('/^[:A-Z_a-z]$/', self::_unicodeToUtf8($c)) !== 0 ||
               $c >= hexdec('C0') && $c <= hexdec('D6') ||
               $c >= hexdec('D8') && $c <= hexdec('F6') ||
               $c >= hexdec('F8') && $c <= hexdec('2FF') ||
               $c >= hexdec('370') && $c <= hexdec('37D') ||
               $c >= hexdec('37F') && $c <= hexdec('1FFF') ||
               $c >= hexdec('200C') && $c <= hexdec('200D') ||
               $c >= hexdec('2070') && $c <= hexdec('218F') ||
               $c >= hexdec('2C00') && $c <= hexdec('2FEF') ||
               $c >= hexdec('3001') && $c <= hexdec('D7FF') ||
               $c >= hexdec('F900') && $c <= hexdec('FDCF') ||
               $c >= hexdec('FDF0') && $c <= hexdec('FFFD') ||
               $c >= hexdec('10000') && $c <= hexdec('EFFFF');
    }
    
    
    /**
     * Returns whether $char is a valid XML 1.1. NameChar.
     * NameChar is defined as:
     * NameChar ::= NameStartChar | "-" | "." | [0-9] | #xB7 | [#x0300-#x036F] |
     *              [#x203F-#x2040]
     *
     * @param int $c A unicode character as an integer.
     *
     * @return boolean Wheather $char is a valid NameChar.
     * @link http://www.w3.org/TR/xml11/
     */
    private static function _isValidNameChar($c)
    {
        return self::_isValidNameStartChar($c) ||
               preg_match('/^[-\.0-9]$/', self::_unicodeToUtf8($c)) !== 0 ||
               $c == hexdec('B7') ||
               $c >= hexdec('0300') && $c <= hexdec('036F') ||
               $c >= hexdec('203F') && $c <= hexdec('2040');
    }
    
    /**
     * Converts a single unicode character represended by an integer
     * to an UTF-8 chracter
     *
     * @param int $char The unicode character as an integer
     *
     * @return string The UTF-8 character.
     */
    private static function _unicodeToUtf8($char)
    {
        return I18N_UnicodeString::unicodeCharToUtf8($char);
    }
    
    /**
     * Converts a UTF-8 string into unicode integers.
     *
     * @param string $string A string containing Unicode values encoded in UTF-8
     *
     * @return array The array of Unicode values.
     * @throws XML_Query2XML_ISO9075Mapper_Exception If a malformed UTF-8 string
     *                                               was passed as argument.
     */
    private static function _utf8ToUnicode($string)
    {
        $string = I18N_UnicodeString::utf8ToUnicode($string);
        if (strtolower(get_class($string)) == 'pear_error') {
            /*
             * unit tests:
             *  testMapException1()
             *  testMapException2()
             *  testMapException3()
             */
            throw new XML_Query2XML_ISO9075Mapper_Exception(
                $string->getMessage()
            );
        }
        return $string;
    }
}

/**
 * Only XML_Query2XML_ISO9075Mapper will throw this exception.
 * It does not extend XML_Query2XML_Exception because the
 * class XML_Query2XML_ISO9075Mapper should be usable without
 * XML_Query2XML. XML_Query2XML itself will never throw this
 * exception.
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_ISO9075Mapper_Exception extends PEAR_Exception
{
    
    /**
     * Constructor method
     *
     * @param string $message The error message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
?>