<?php
/**
 * This file contains the class XML_Query2XML_Driver_Caching.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Caching.php,v 1.3 2009/03/01 10:43:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Driver_Caching extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * Caching driver.
 *
 * usage:
 * <code>
 * $driver = new XML_Query2XML_Driver_Caching(
 *     XML_Query2XML_Driver::factory(...)
 * );
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2008 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 * @since     Release 1.7.1RC1
 */
class XML_Query2XML_Driver_Caching extends XML_Query2XML_Driver
{
    /**
     * The record cache.
     *
     * @var array An associative array.
     */
    private $_recordCache = array();
    
    /**
     * The driver who's results to be cached.
     *
     * @var XML_Query2XML_Driver
     */
    private $_driver = null;
    
    /**
     * An associative array of query strings returned by
     * $this->_driver->preprocessQuery(). The $configPath
     * will be used as the key.
     *
     * @var array An assocative array.
     */
    private $_queryStrings = array();
    
    /**
     * Constructor function.
     *
     * @param XML_Query2XML_Driver $driver The driver this driver
     *                                     will wrap around.
     */
    public function __construct(XML_Query2XML_Driver $driver)
    {
        $this->_driver = $driver;
    }
    
    /**
     * Pre-processes a query specification and returns a string representation
     * of the query.
     *
     * @param mixed  &$query     A string or an array containing the element 'query'.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return string The query statement as a string.
     */
    public function preprocessQuery(&$query, $configPath)
    {
        return $this->_queryStrings[$configPath] =
            $this->_driver->preprocessQuery($query, $configPath)
            . ' (USING CACHING)';
    }
    
    /**
     * Fetch all records from the result set.
     *
     * @param mixed  $sql        The SQL query.
     * @param string $configPath The config path.
     *
     * @return array An array of records.
     * @see XML_Query2XML_Driver::getAllRecords()
     */
    public function getAllRecords($sql, $configPath)
    {
        $queryString = $this->_queryStrings[$configPath];
        if (is_array($sql) && isset($sql['data']) && is_array($sql['data'])) {
            $queryString .= '; DATA:' . implode(',', $sql['data']);
        }
        if (!isset($this->_recordCache[$queryString])) {
            $this->_recordCache[$queryString] =&
                $this->_driver->getAllRecords($sql, $configPath);
        }
        return $this->_recordCache[$queryString];
    }
}
?>