<?php
/**
 * This file contains the class XML_Query2XML_Data_Condition_NonEmpty.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: NonEmpty.php,v 1.3 2009/03/01 12:58:57 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Condition_NonEmpty extends
 * XML_Query2XML_Data_Condition.
 */
require_once 'XML/Query2XML/Data/Condition.php';

/**
 * Data Condition Class implementing a condition based on whether the
 * value returned by a pre-processor is an object or a non-empty string.
 *
 * XML_Query2XML_Data_Condition_NonEmpty requires a pre-processor to be used.
 *
 * usage:
 * <code>
 * $commandObject = new XML_Query2XML_Data_Condition_NonEmpty(
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
class XML_Query2XML_Data_Condition_NonEmpty extends XML_Query2XML_Data_Condition
{
    /**
     * Create a new instance of this class.
     *
     * @param mixed  $preProcessor The pre-processor to be used. An instance of
     *                             XML_Query2XML_Data or null.
     * @param string $configPath   The configuration path within the $options
     *                             array.
     *
     * @return XML_Query2XML_Data_Condition_NonEmpty
     */
    public function create($preProcessor, $configPath)
    {
        $condition = new XML_Query2XML_Data_Condition_NonEmpty($preProcessor);
        $condition->setConfigPath($configPath);
        return $condition;
    }
    
    /**
     * As this class implements XML_Query2XML_Data_Condition, XML_Query2XML
     * will call this method to determin whether the condition is fulfilled.
     *
     * @param mixed $value The value previously returned by $this->execute().
     *
     * @return boolean Whether the condition is fulfilled.
     */
    public function evaluateCondition($value)
    {
        return is_object($value) ||
            !(is_null($value) || (is_string($value) && strlen($value) == 0));
    }
}
?>