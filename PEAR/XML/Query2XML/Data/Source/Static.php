<?php
/**
 * This file contains the class XML_Query2XML_Data_Source_Static.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Static.php,v 1.4 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Source_Static extends the class
 * XML_Query2XML_Data_Source.
 */
require_once 'XML/Query2XML/Data/Source.php';

/**
 * Data Source Class that allows a static value to be used as the data source.
 *
 * This command class does not accept a pre-processor.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Source_Static('my static value');
 * </code>
 *
 * The static value can also be an instance of DOMNode or an array of DOMNode
 * instances:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Source_Static(new DOMElement('test'));
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
class XML_Query2XML_Data_Source_Static extends XML_Query2XML_Data_Source
{
    /**
     * The static data.
     * @var mixed
     */
    private $_data = null;
    
    /**
     * Constructor
     *
     * @param mixed $data The static data.
     */
    public function __construct($data)
    {
        if ($data === false) {
            $data = '';
        }
        $this->_data = $data;
    }
    
    /**
     * Creates a new instance of this class.
     * This method is called by XML_Query2XML.
     *
     * @param string $data       The static data.
     * @param string $configPath The configuration path within the $options array.
     *
     * @return XML_Query2XML_Data_Source_Static
     */
    public function create($data, $configPath)
    {
        $source = new XML_Query2XML_Data_Source_Static($data);
        $source->setConfigPath($configPath);
        return $source;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed Whatever was passed as $data to the constructor.
     */
    public function execute(array $record)
    {
        return $this->_data;
    }
    
    /**
     * This method is called by XML_Query2XML in case the asterisk shortcut was used.
     *
     * The interface XML_Query2XML_Data_Source requires an implementation of
     * this method.
     *
     * @param string $columnName The column name that is to replace every occurance
     *                           of the asterisk character '*' in the static value,
     *                           in case it is a string.
     *
     * @return void
     */
    public function replaceAsterisks($columnName)
    {
        if (is_string($this->_data)) {
            $this->_data = str_replace('*', $columnName, $this->_data);
        }
    }
    
    /**
     * Returns a textual representation of this instance.
     * This might be useful for debugging.
     *
     * @return string
     */
    public function toString()
    {
        if (is_string($this->_data)) {
            $data = $this->_data;
        } else {
            $data = gettype($this->_data);
        }
        return get_class($this) . '(' . $data . ')';
    }
}
?>