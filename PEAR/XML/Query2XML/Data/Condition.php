<?php
/**
 * This file contains the interface XML_Query2XML_Callback.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Condition.php,v 1.3 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML 
 * @access    private
 */

/**
 * XML_Query2XML_Data_Condition extends the class
 * XML_Query2XML_Data_Processor.
 */
require_once 'XML/Query2XML/Data/Processor.php';

/**
 * Abstract class extended by all Data Condition Classes.
 * Such classes allow the implementation of a condition as to
 * whether the return value of execute() is to be used.
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
abstract class XML_Query2XML_Data_Condition extends XML_Query2XML_Data_Processor
{
    /**
     * Returns a boolean value indicating whether the return value of execute()
     * is to be used.
     *
     * @param mixed $value The return value of execute()
     *
     * @return boolean
     */
    abstract public function evaluateCondition($value);
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed Whatever is returned by the pre-processor.
     * @throws XML_Query2XML_ConfigException Bubbles up if no
     *                                       pre-processor was set.
     */
    public function execute(array $record)
    {
        return $this->runPreProcessor($record);
    }
}
?>