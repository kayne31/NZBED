<?php
/**
 * This file contains the class XML_Query2XML_Data_Source_ColumnValue.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: ColumnValue.php,v 1.3 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Source_ColumnValue extends the class
 * XML_Query2XML_Data_Source.
 */
require_once 'XML/Query2XML/Data/Source.php';

/**
 * Data Source Class that allows the column value to be used as the data source.
 *
 * This command class does not accept a pre-processor.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Source_ColumnValue('name');
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 * @since     Release 1.7.1RC1
 */
class XML_Query2XML_Data_Source_ColumnValue extends XML_Query2XML_Data_Source
{
    /**
     * The column name.
     * @var string
     */
    private $_column = '';
    
    /**
     * Constructor
     *
     * @param string $column The name of the column.
     */
    public function __construct($column)
    {
        $this->_column = $column;
    }
    
    /**
     * Creates a new instance of this class.
     * This method is called by XML_Query2XML.
     *
     * @param string $column     The name of the column.
     * @param string $configPath The configuration path within the $options array.
     *                           This argument is optional.
     *
     * @return XML_Query2XML_Data_Source_ColumnValue
     */
    public function create($column, $configPath)
    {
        $source = new XML_Query2XML_Data_Source_ColumnValue($column);
        $source->setConfigPath($configPath);
        return $source;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed The contents of $record[$column] where $column is the first
     *               argument passed to the constructor.
     * @throws XML_Query2XML_ConfigException If $column does not exist in the
     *               result set, i.e. $record[$column] does not exist.
     */
    public function execute(array $record)
    {
        if (array_key_exists($this->_column, $record)) {
            return $record[$this->_column];
        }
        throw new XML_Query2XML_ConfigException(
            $this->getConfigPath() . 'The column "' . $this->_column
            . '" was not found in the result set'
        );
    }
    
    /**
     * This method is called by XML_Query2XML in case the asterisk shortcut was used.
     *
     * The interface XML_Query2XML_Data_Source requires an implementation of
     * this method.
     *
     * @param string $columnName The column name that is to replace every occurance
     *                           of the asterisk character '*' in $column (the first
     *                           argument passed to the constructor).
     *
     * @return void
     */
    public function replaceAsterisks($columnName)
    {
        $this->_column = str_replace('*', $columnName, $this->_column);
    }
    
    /**
     * Returns a textual representation of this instance.
     * This might be useful for debugging.
     *
     * @return string
     */
    public function toString()
    {
        return get_class($this) . '(' . $this->_column . ')';
    }
}
?>