<?php
/**
 * This file contains the class XML_Query2XML_Data_Processor_CDATA.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: CDATA.php,v 1.4 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Processor_CDATA extends the class XML_Query2XML_Data_Processor.
 */
require_once 'XML/Query2XML/Data/Processor.php';

/**
 * Data Processor Class that creates a CDATA section around the string
 * returned by a pre-processor.
 *
 * XML_Query2XML_Data_Processor_CDATA only works with a pre-processor
 * that returns a string.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Processor_CDATA(
 *   new XML_Query2XML_Data_Source_ColumnValue('name')  //pre-processor
 * );
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
class XML_Query2XML_Data_Processor_CDATA extends XML_Query2XML_Data_Processor
{
    /**
     * Create a new instance of this class.
     *
     * @param mixed  $preProcessor The pre-processor to be used. An instance of
     *                             XML_Query2XML_Data or null.
     * @param string $configPath   The configuration path within the $options
     *                             array.
     *
     * @return XML_Query2XML_Data_Processor_CDATA
     */
    public function create($preProcessor, $configPath)
    {
        $processor = new XML_Query2XML_Data_Processor_CDATA($preProcessor);
        $processor->setConfigPath($configPath);
        return $processor;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     * This method will return an instance of DOMCDATASection or null
     * if an empty string was returned by the pre-processor.
     *
     * @param array $record An associative array.
     *
     * @return DOMCDATASection
     * @throws XML_Query2XML_ConfigException If the pre-processor returns
     *                      something that cannot be converted to a string (i.e. an
     *                      array or an object) or if no pre-processor was set.
     */
    public function execute(array $record)
    {
        $doc  = new DOMDocument();
        $data = $this->runPreProcessor($record);
        if (is_array($data) || is_object($data)) {
            throw new XML_Query2XML_ConfigException(
                $this->getConfigPath()
                . 'XML_Query2XML_Data_Processor_CDATA: string expected '
                . 'from pre-processor, but ' . gettype($data) . ' returned.'
            );
        }
        if (strlen($data) > 0) {
            return $doc->createCDATASection($data);
        } else {
            return null;
        }
    }
}
?>