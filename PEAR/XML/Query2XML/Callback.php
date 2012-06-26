<?php
/**
 * This file contains the interface XML_Query2XML_Callback.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2007 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Callback.php,v 1.6 2009/03/01 13:17:07 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML 
 */

/**
 * Callback interface
 *
 * If you want to use a non-static method as a callback for XML_Query2XML
 * you have to use an instance of a class that implements this interface.
 * Your command class (read more about the command pattern 
 * {@link http://en.wikipedia.org/wiki/Command_pattern here}) therefore
 * has to implement a public method that is named "execute" and accepts an
 * array as its first argument. Here goes an example:
 * <code>
 * require_once 'XML/Query2XML/Callback.php';
 * class MyCallback implements XML_Query2XML_Callback
 * {
 *   public function execute(array $record)
 *   {
 *       $data = $record['some_column'];
 *       // do some really complex things with $data
 *
 *       return $data;
 *   }
 * }
 * $myCallback = new MyCallback();
 * </code>
 * XML_Query2XML will always invoke the execute() method and will pass
 * the current record as an associative array as the first and only argument.
 * A command object can be used for
 * - Simple Element Specifications
 * - Complex Element Specifications ($options['value')
 * - Simple Attribute Specifications
 * - Complex Attribute Specifications ($options['value')
 * - $options['condition']
 * - $options['sql']['data']
 * - $options['idColumn']
 *
 * If you want to use the same command class for different columns, I suggest
 * you pass the column name to the constructor:
 * <code>
 * require_once 'XML/Query2XML/Callback.php';
 * class MyCallback implements XML_Query2XML_Callback
 * {
 *   private $_columnName = '';
 *   public function __construct($columnName)
 *   {
 *       $this->_columnName = $columnName;
 *   }
 *   public function execute(array $record)
 *   {
 *       if (!isset($record[$this->_columnName])) {
 *           // throw an exception here
 *       }
 *       $data = $record[$this->_columnName];
 *       // do some really complex things with $data
 *
 *       return $data;
 *   }
 * }
 * $myCallback = new MyCallback('some_column_name');
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @since     Release 1.1.0
 */
interface XML_Query2XML_Callback
{
    /**
     * This method will be called by XML_Query2XML.
     * This method has to return a value that can be cast to a string
     * or if used within a Complex Element Specification, an instance
     * of DOMNode.
     *
     * @param array $record A record as an associative array.
     *
     * @return mixed A value that can be cast to a string or an instance of DOMNode.
     */
    public function execute(array $record);
}
?>