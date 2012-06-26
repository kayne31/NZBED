<?php
/**
 * This file contains the class XML_Query2XML_Data.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Data.php,v 1.3 2009/03/01 13:17:07 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
* XML_Query2XML_Data implements the interface XML_Query2XML_Callback.
*/
require_once 'XML/Query2XML/Callback.php';

/**
 * Abstract class extended by all Data classes.
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
abstract class XML_Query2XML_Data implements XML_Query2XML_Callback
{
    /**
     * The configuration path; it is used for exception messages.
     * @var string
     */
    private $_configPath = '';
    
    /**
     * This method will be called by XML_Query2XML to create a new instance
     * of a class extending this class.
     *
     * @param mixed  $primaryArg The primary argument: if it's is a
     *                           Data Processor or Condition Class this will
     *                           be a preprocessor (i.e. an instance of
     *                           XML_Query2XML_Data); if it's a Data Source
     *                           this will most likely be a string.
     * @param string $configPath The configuration path within the $options
     *                           array.
     *
     * @return XML_Query2XML_Data_Processor
     */
    public abstract function create($primaryArg, $configPath);
                                    
    /**
     * Returns the first pre-processor in the chain.
     *
     * @return XML_Query2XML_Data
     */
    public abstract function getFirstPreProcessor();
    
    /**
     * Set the configuration path to be used for exception messages.
     *
     * @param string $configPath The configuration path.
     *
     * @return void
     */
    protected function setConfigPath($configPath)
    {
        if ($configPath) {
            $configPath .= ': ';
        }
        $this->_configPath = $configPath;
    }
    
    /**
     * Return the configuration path to be used for exception messages.
     *
     * @return string The configuration path.
     */
    protected function getConfigPath()
    {
        return $this->_configPath;
    }
    
    /**
     * Returns a textual representation of this instance.
     *
     * @return string
     */
    public abstract function toString();
}
?>