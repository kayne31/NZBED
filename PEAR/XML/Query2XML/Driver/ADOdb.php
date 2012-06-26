<?php
/**
 * This file contains the class XML_Query2XML_Driver_ADOdb.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: ADOdb.php,v 1.4 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * XML_Query2XML_Driver_ADOdb extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * Driver for the database abstraction layer ADOdb.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory(NewADOConnection(...));
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
class XML_Query2XML_Driver_ADOdb extends XML_Query2XML_Driver
{
    /**
     * In instance of a class that extends ADOConnection.
     * @var ADOConnection
     */
    private $_db = null;
    
    /**
     * Constructor
     *
     * @param ADOConnection $db An instance of ADOConnection.
     *
     * @throws XML_Query2XML_DBException If the ADOConnection instance passed as
     *                          argument was not connected to the database server.
     */
    public function __construct(ADOConnection $db)
    {
        if (!$db->IsConnected()) {
            throw new XML_Query2XML_DBException(
                'ADOConnection instance was not connected'
            );
        }
        $db->SetFetchMode(ADODB_FETCH_ASSOC);
        $this->_db = $db;
    }
    
    /**
     * Execute a SQL SELECT statement and fetch all records from the result set.
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
        $result  =& $this->_prepareAndExecute($sql, $configPath);
        $records = array();
        while ($record = $result->fetchRow()) {
            if (class_exists('PEAR_Error') && $record instanceof PEAR_Error) {
                // no unit test for this exception as it cannot be produced easily
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not fetch rows for the following '
                    . 'SQL query: ' . $sql['query'] . '; '
                    . $record->toString()
                );
            }
            $records[] = $record;
        }
        if ($result instanceof ADORecordSet) {
            $result->free();
        }
        return $records;
    }
    
    /**
     * Private method that will ADOConnection::prepare() & ADOConnection::execute()
     * to retrieve records.
     *
     * @param mixed  $sql        An array with an element at the index 'query'.
     * @param string $configPath The config path used for exception messages.
     *
     * @return DB_result
     * @throws XML_Query2XML_DBException If a database related error occures.
     */
    private function _prepareAndExecute($sql, $configPath)
    {
        $query =& $sql['query'];
        if (isset($this->_preparedQueries[$query])) {
            $queryHandle = $this->_preparedQueries[$query];
        } else {
            // ADOdb centralizes all error-handling in execute()
            $queryHandle                    = $this->_db->prepare($query);
            $this->_preparedQueries[$query] =& $queryHandle;
        }
        
        /*
         * EXECUTE
         */
        
        try {
            if (isset($sql['data'])) {
                $result = $this->_db->execute($queryHandle, $sql['data']);
            } else {
                $result = $this->_db->execute($queryHandle);
            }
        } catch (Exception $e) {
            /*
             * unit test: ADOdbException/
             *  _prepareAndExecute/throwDBException_complexQuery.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL '
                . 'query: ' . $query .  '; ' . $e->getMessage()
            );
        }
            
        if ($result === false && function_exists('ADODB_Pear_Error')) {
            $result = ADODB_Pear_Error();
        }
        
        if (class_exists('PEAR_Error') && $result instanceof PEAR_Error) {
            /*
             * unit test: ADOdbPEAR/
             *  _prepareAndExecute/throwDBException_complexQuery.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL query: '
                . $query . '; ' . $result->toString()
            );
        } elseif ($result === false) {
            /*
             * unit test: ADOdbDefault/
             *  _prepareAndExecute/throwDBException_complexQuery.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL query: '
                . $query . ' (false was returned)'
            );
        }
        return $result;
    }
}
?>