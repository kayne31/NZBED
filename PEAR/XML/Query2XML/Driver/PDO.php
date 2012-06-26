<?php
/**
 * This file contains the class XML_Query2XML_Driver_PDO.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: PDO.php,v 1.4 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * XML_Query2XML_Driver_PDO extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * Driver for the database abstraction layer PDO.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory(new PDO(...));
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @since     Release 1.5.0RC1
 */
class XML_Query2XML_Driver_PDO extends XML_Query2XML_Driver
{
    /**
     * In instance of PDO
     * @var PDO
     */
    private $_db = null;
    
    /**
     * Constructor
     *
     * @param PDO $db An instance of PDO.
     *
     * @throws XML_Query2XML_DBException If PDO::ATTR_ERRMODE cannot be set to
     *                               PDO::ERRMODE_EXCEPTION.
     */
    public function __construct(PDO $db)
    {
        $success = $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$success) {
            // no unit tests for this one
            throw new XML_Query2XML_DBException(
                'Could not set attribute PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION'
            );
        }
        $this->_db = $db;
    }
    
    /**
     * Execute a SQL SELECT stement and fetch all records from the result set.
     *
     * @param mixed  $sql        The SQL query as a string or an array.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return array An array of records.
     * @throws XML_Query2XML_DBException If a database related error occures.
     * @see XML_Query2XML_Driver::getAllRecords()
     */
    public function getAllRecords($sql, $configPath)
    {
        $result =& $this->_prepareAndExecute($sql, $configPath);
        try {
            $records = $result->fetchAll();
        } catch (PDOException $e) {
            /*
             * unit tests: PDO/getXML/
             *  throwDBException_nullResultSet_complexQuery_multipleRecords.phpt
             *  throwDBException_nullResultSet_complexQuery_singleRecord.phpt
             *  throwDBException_nullResultSet_simpleQuery_multipleRecords.phpt
             *  throwDBException_nullResultSet_simpleQuery_singleRecord.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not fetch records for the following SQL '
                . 'query: ' . $sql['query'] .  '; '
                . $e->getMessage()
            );
        }
        return $records;
    }
    
    /**
     * Private method that will use PDO::query() for simple and
     * PDO::prepare() & PDOStatement::execute() for complex query specifications.
     *
     * @param mixed  $sql        A string or an array.
     * @param string $configPath The config path used for exception messages.
     *
     * @return PDOStatement
     * @throws XML_Query2XML_DBException If a database related error occures.
     */
    private function _prepareAndExecute($sql, $configPath)
    {
        $query =& $sql['query'];
        if (isset($this->_preparedQueries[$query])) {
            $queryHandle = $this->_preparedQueries[$query];
        } else {
            // PREPARE
            $queryHandle = $this->_db->prepare($query);
            if ($queryHandle === false) {
                /*
                 * No unit test for this exception as neither the mysql, pgsql
                 * or sqlite driver ever returns false from PDO::prepare().
                 */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not prepare the following SQL '
                    . 'query - PDO::prepare() returned false: ' . $query
                );
            }
            $this->_preparedQueries[$query] =& $queryHandle;
        }
        
        // EXECUTE
        try {
            if (isset($sql['data'])) {
                $queryHandle->execute($sql['data']);
            } else {
                $queryHandle->execute();
            }
        } catch (PDOException $e) {
            /*
             * unit test: PDO/_prepareAndExecute
             *  throwDBException_complexQuery.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL query: '
                . $query .  '; ' . $e->getMessage()
            );
        }
        $success = $queryHandle->setFetchMode(PDO::FETCH_ASSOC);
        if (!$success) {
            // no unit tests for this one
            throw new XML_Query2XML_DBException(
                'Could not set fetch mode to PDO::FETCH_ASSOC'
            );
        }
        return $queryHandle;
    }
}
?>