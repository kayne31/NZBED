<?php
/**
 * This file contains the class XML_Query2XML_Data_Source_PHPCallback.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2009 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: PHPCallback.php,v 1.3 2009/03/01 13:17:08 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 */

/**
 * XML_Query2XML_Data_Source_PHPCallback extends the class
 * XML_Query2XML_Data_Source.
 */
require_once 'XML/Query2XML/Data/Source.php';


/**
 * Data Source Class that invokes a callback function, using the return value as the
 * data source.
 *
 * This command class does not accept a pre-processor.
 *
 * usage:
 * <code>
 * function myFunction($record) { ... }
 * $commandObject = new XML_Query2XML_Data_Source_PHPCallback('myFunction');
 * </code>
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @access    private
 * @since     Release 1.7.1RC1
 */
class XML_Query2XML_Data_Source_PHPCallback extends XML_Query2XML_Data_Source
{
    /**
     * A pseudo-type callback.
     * @var mixed A string or an array.
     */
    private $_callback = null;
    
    /**
     * The string definition of the callback
     * as it was passed to the constructor.
     * This will be returned by toString().
     * @var string
     */
    private $_callbackString = '';
    
    /**
     * The arguments to be bassed to the callback function.
     * @var array An index array of arguments.
     */
    private $_args = array();
    
    /**
     * Constructor
     *
     * The following formats are supported for $callback:
     * - 'myFunction'
     * - 'myFunction(arg1, arg2, ...)'
     * - 'MyClass::myStaticMethod'
     * - 'MyClass::myStaticMethod(arg1, arg2, ...)'
     * You can also pass additional string arguments to the callback function by
     * specifing them within the opening and closing brace; e.g. 'Utils::limit(12)'
     * will result in Util::limit() being called by execute() with the $record as
     * the first and '12' as the second argument.
     * If you do not want to pass additional arguments to the callback function,
     * the opening and closing brace are optional.
     *
     * @param string $callback The callback as a string.
     *
     * @throws XML_Query2XML_ConfigException If $callback is not callable.
     */
    public function __construct($callback)
    {
        $this->_callbackString = $callback;
        
        $braceOpen = strpos($callback, '(');
        if ($braceOpen !== false) {
            $braceClose = strpos($callback, ')');
            if ($braceOpen + 1 < $braceClose) {
                $argsString  = substr(
                    $callback, $braceOpen + 1, $braceClose - $braceOpen - 1
                );
                $this->_args = explode(',', str_replace(', ', ',', $argsString));
            }
            if ($braceOpen < $braceClose) {
                $callback = substr($callback, 0, $braceOpen);
            }
        }
        if (strpos($callback, '::') !== false) {
            $callback = split('::', $callback);
        }
        if (!is_callable($callback, false, $callableName)) {
            /*
            * unit tests: _applyColumnStringToRecord/
            *  throwConfigException_callback_function1.phpt
            *  throwConfigException_callback_function2.phpt
            *  throwConfigException_callback_method1.phpt
            *  throwConfigException_callback_method2.phpt
            */
            throw new XML_Query2XML_ConfigException(
                $this->getConfigPath() . 'The method/function "'
                . $callableName . '" is not callable.'
            );
        }
        $this->_callback = $callback;
    }
    
    /**
     * Creates a new instance of this class.
     * This method is called by XML_Query2XML.
     *
     * @param string $callback   The callback as a string.
     * @param string $configPath The configuration path within the $options array.
     *
     * @return XML_Query2XML_Data_Source_PHPCallback
     */
    public function create($callback, $configPath)
    {
        $source = new XML_Query2XML_Data_Source_PHPCallback($callback);
        $source->setConfigPath($configPath);
        return $source;
    }
    
    /**
     * Called by XML_Query2XML for every record in the result set.
     *
     * @param array $record An associative array.
     *
     * @return mixed Whatever the callback function returned.
     */
    public function execute(array $record)
    {
        return call_user_func_array(
            $this->_callback,
            array_merge(array($record), $this->_args)
        );
    }
    
    /**
     * This method is called by XML_Query2XML in case the asterisk shortcut was used.
     *
     * The interface XML_Query2XML_Data_Source requires an implementation of
     * this method.
     *
     * @param string $columnName The column name that is to replace every occurance
     *                           of the asterisk character '*' in any of the
     *                           arguments specified in the
     *                           'myFunction(arg1, arg2, ...)' or
     *                           'MyClass::myStaticMethod(arg1, arg2, ...)' notation.
     *
     * @return void
     */
    public function replaceAsterisks($columnName)
    {
        foreach ($this->_args as $key => $arg) {
            $this->_args[$key] = str_replace('*', $columnName, $this->_args[$key]);
        }
    }
    
    /**
     * Returns a string representation of this class.
     *
     * @return string
     */
    public function toString()
    {
        return get_class($this) . '(' . $this->_callbackString . ')';
    }
}
?>