<?php
/**
 * This file contains the class XML_Query2XML_Driver_MDB2.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: MDB2.php,v 1.4 2008/05/09 20:54:26 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * XML_Query2XML_Driver_MDB2 extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * As the method PEAR::isError() is used within XML_Query2XML_Driver_MDB2
 * we require PEAR.php.
 */
require_once 'PEAR.php';

/**
 * Driver for the database abstraction layer PEAR MDB2.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory(MDB2::factory(...));
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
class XML_Query2XML_Driver_MDB2 extends XML_Query2XML_Driver
{
    /**
     * In instance of a class that extends MDB2_Driver_Common.
     * @var MDB2_Driver_Common
     */
    private $_db = null;
    
    /**
     * Constructor
     *
     * @param MDB2_Driver_Common $db An instance of MDB2_Driver_Common.
     *
     * @throws XML_Query2XML_DBException If the fetch mode cannot be set to
     *                               MDB2_FETCHMODE_ASSOC.
     */
    public function __construct(MDB2_Driver_Common $db)
    {
        $fetchModeError = $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($fetchModeError)) {
            throw new XML_Query2XML_DBException(
                'Could not set fetch mode to DB_FETCHMODE_ASSOC: '
                . $fetchModeError->toString()
            );
        }
        $this->_db = $db;
    }
    
    /**
     * Pre-processes a query specification and returns a string representation
     * of the query.
     * This method will call parent::preprocessQuery(). Additionally it will
     * verify $query['limit'] and $query['offset'].
     *
     * @param mixed  &$query     A string or an array containing the element 'query'.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return string The query statement as a string.
     * @throws XML_Query2XML_ConfigException If $query['limit'] or $query['offset']
     *                                       is set but not numeric. This exception
     *                                       might also bubble up from
     *                                       parent::preprocessQuery().
     */
    public function preprocessQuery(&$query, $configPath)
    {
        // will make $query an array if it is not already
        $queryString = parent::preprocessQuery($query, $configPath);
        
        foreach (array('limit', 'offset') as $sqlOption) {
            if (isset($query[$sqlOption])) {
                if (!is_numeric($query[$sqlOption])) {
                    /*
                     * unit test: getXML/
                     *  offsetlimit_throwConfigException_limit_not_numeric.phpt
                     *  offsetlimit_throwConfigException_offset_not_numeric.phpt
                     */
                    throw new XML_Query2XML_ConfigException(
                        $configPath . '[' . $sqlOption
                        . ']: integer expected, '
                        . gettype($query[$sqlOption]) . ' given.'
                    );
                }
            }
        }
        if (isset($query['limit'])) {
            if ($query['limit'] == 0) {
                // setting limit to 0 is like not setting it at all
                unset($query['limit']);
            } else {
                if (!isset($query['offset'])) {
                    // offset defaults to 0
                    $query['offset'] = 0;
                }
                $queryString .= '; LIMIT:' . $query['limit'];
                $queryString .= '; OFFSET:' . $query['offset'];
            }
        }
        return $queryString;
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
        if (isset($sql['limit']) && $sql['limit'] < 0) {
            return array();
        }
        $result  =& $this->_prepareAndExecute($sql, $configPath);
        $records = array();
        while ($record = $result->fetchRow()) {
            if (PEAR::isError($record)) {
                // no unit test for this exception as it cannot be produced easily
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not fetch rows for the following '
                    . 'SQL query: ' . $sql['query'] . '; '
                    . $record->toString()
                );
            }
            $records[] = $record;
        }
        $result->free();
        return $records;
    }
    
    /**
     * Private method that will use MDB2_Driver_Common::query() for simple and
     * MDB2_Driver_Common::prepare() & MDB2_Statement_Common::execute() for complex
     * query specifications.
     *
     * @param mixed  $sql        A string or an array.
     * @param string $configPath The config path used for exception messages.
     *
     * @return MDB2_Result
     * @throws XML_Query2XML_DBException If a database related error occures.
     */
    private function _prepareAndExecute($sql, $configPath)
    {
        $preparedQuery = $sql['query'];
        if (isset($sql['limit'])) {
            $preparedQuery .= '; LIMIT:' . $sql['limit'];
            $preparedQuery .= '; OFFSET:' . $sql['offset'];
            $this->_db->setLimit($sql['limit'], $sql['offset']);
        }
        
        if (isset($this->_preparedQueries[$preparedQuery])) {
            $queryHandle = $this->_preparedQueries[$preparedQuery];
        } else {
            // PREPARE
            $queryHandle = $this->_db->prepare($sql['query']);
            
            if (PEAR::isError($queryHandle)) {
                /*
                 * unit tests: (only if mysql or pgsql is used)
                 *  MDB2/_prepareAndExecute/throwDBException_complexQuery.phpt
                 */
                throw new XML_Query2XML_DBException(
                    $configPath . ': Could not prepare the following SQL query: '
                    . $sql['query'] . '; ' . $queryHandle->toString()
                );
            }
            $this->_preparedQueries[$preparedQuery] =& $queryHandle;
        }
        
        // EXECUTE
        if (isset($sql['data'])) {
            $result = $queryHandle->execute($sql['data']);
        } else {
            $result = $queryHandle->execute();
        }
        
        if (PEAR::isError($result)) {
            /*
             * unit tests:
             *  if sqlite is used: MDB2/_prepareAndExecute/
             *   throwDBException_complexQuery.phpt
             *  if sqlite or mysql is sued: MDB2/getXML/
             *   throwDBException_nullResultSet_complexQuery_multipleRecords.phpt
             *   throwDBException_nullResultSet_complexQuery_singleRecord.phpt
             */
            throw new XML_Query2XML_DBException(
                $configPath . ': Could not execute the following SQL query: '
                . $sql['query'] . '; ' . $result->toString()
            );
        }
        return $result;
    }
}
?>