<?php
/**
 * This file contains the class XML_Query2XML_Data_Processor.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Processor.php,v 1.4 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Processor extends XML_Query2XML_Data.
 */
require_once 'XML/Query2XML/Data.php';

/**
 * Abstract class extended by all Data Processor Classes.
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
abstract class XML_Query2XML_Data_Processor extends XML_Query2XML_Data
{
    /**
     * Another instance of XML_Query2XML_Data to process before this one.
     * @var XML_Query2XML_Data
     */
    private $_preProcessor = null;
    
    /**
     * Constructor.
     *
     * @param XML_Query2XML_Data $preProcessor The pre-processor to be used.
     *                                         This argument is optional.
     */
    public function __construct(XML_Query2XML_Data $preProcessor = null)
    {
        $this->setPreProcessor($preProcessor);
    }
    
    /**
     * Allows the pre-processor to be set (or changed) after an instance was
     * created.
     *
     * @param mixed $preProcessor The pre-processor to be used. An instance
     *                            of XML_Query2XML_Data or null.
     *
     * @return void
     */
    public function setPreProcessor($preProcessor)
    {
        $this->_preProcessor = $preProcessor;
    }
    
    /**
     * Returns the pre-processor.
     *
     * @return mixed XML_Query2XML_Data or null
     */
    public function getPreProcessor()
    {
        return $this->_preProcessor;
    }
    
    /**
     * Returns the first pre-processor in the chain.
     *
     * @return XML_Query2XML_Data
     */
    public function getFirstPreProcessor()
    {
        if (!is_null($this->getPreProcessor())) {
            return $this->getPreProcessor()->getFirstPreProcessor();
        }
        return $this;
    }
    
    /**
     * Runs the pre-processor if one was defined and returns it's return value.
     *
     * @param array $record The record to process - this is an associative array.
     *
     * @return mixed Whatever was returned by the pre-processor
     * @throws XML_Query2XML_ConfigException If no pre-processor was defined.
     */
    protected function runPreProcessor(array $record)
    {
        if (!is_null($this->getPreProcessor())) {
            return $this->getPreProcessor()->execute($record);
        } else {
            include_once 'XML/Query2XML.php';
            // UNIT TEST: MISSING
            throw new XML_Query2XML_ConfigException(
                $this->getConfigPath()
                . get_class($this) . ' requires a pre-processor.'
            );
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
        $str = get_class($this) . '(';
        if (!is_null($this->getPreProcessor())) {
            $str .= $this->getPreProcessor()->toString();
        }
        return $str . ')';
    }
}
?>