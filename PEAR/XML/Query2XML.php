<?php
/**
 * This file contains the class XML_Query2XML and all exception classes.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: Query2XML.php,v 1.37 2009/03/01 13:17:07 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * PEAR_Exception is used as the parent for XML_Query2XML_Exception.
 */
require_once 'PEAR/Exception.php';

/**
 * Create XML data from SQL queries.
 *
 * XML_Query2XML heavily uses exceptions and therefore requires PHP5.
 * Additionally one of the following database abstraction layers is
 * required: PDO (compiled-in by default since PHP 5.1), PEAR DB,
 * PEAR MDB2, ADOdb.
 *
 * The two most important public methods this class provides are:
 *
 * <b>{@link XML_Query2XML::getFlatXML()}</b>
 * Transforms your SQL query into flat XML data.
 *
 * <b>{@link XML_Query2XML::getXML()}</b>
 * Very powerful and flexible method that can produce whatever XML data you want. It
 * was specifically written to also handle LEFT JOINS.
 *
 * They both return an instance of the class DOMDocument provided by PHP5's
 * built-in DOM API.
 *
 * A typical usage of XML_Query2XML looks like this:
 * <code>
 * <?php
 * require_once 'XML/Query2XML.php';
 * $query2xml = XML_Query2XML::factory(MDB2::factory($dsn));
 * $dom = $query2xml->getXML($sql, $options);
 * header('Content-Type: application/xml');
 * print $dom->saveXML();
 * ?>
 * </code>
 *
 * Please read the <b>{@tutorial XML_Query2XML.pkg tutorial}</b> for
 * detailed usage examples and more documentation.
 * 
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML
{
    /**
     * Primary driver.
     * @var XML_Query2XML_Driver A subclass of XML_Query2XML_Driver.
     */
    private $_driver;
    
    /**
     * An instance of PEAR Log
     * @var mixed An object that has a method with the signature log(String $msg);
     *            preferably PEAR Log.
     * @see enableDebugLog
     * @see disableDebugLog
     */
    private $_debugLogger;
    
    /**
     * Whether debug logging is to be performed
     * @var boolean
     * @see enableDebugLog
     * @see disableDebugLog
     */
    private $_debug = false;
    
    /**
     * Whether profiling is to be performed
     * @var boolean
     * @see startProfiling()
     * @see stopProfiling()
     */
    private $_profiling = false;
    
    /**
     * The profiling data.
     * @var array A multi dimensional associative array
     * @see startProfiling()
     * @see stopProfiling()
     * @see _debugStartQuery()
     * @see _debugStopQuery()
     * @see _stopDBProfiling()
     */
    private $_profile = array();
    
    /**
     * An associative array of global options.
     * @var array An associative array
     * @see setGlobalOption()
     */
    private $_globalOptions = array(
        'hidden_container_prefix' => '__'
    );
    
    /**
     * An associative array that will contain an element for each prefix.
     * The prefix is used as the element key. Each array element consists
     * of an indexed array containing a file path and a class name.
     * @var array An associative multidimensional array.
     * @see _buildCommandChain()
     */
    private $_prefixes = array(
        '?' => array(
            'XML/Query2XML/Data/Condition/NonEmpty.php',
            'XML_Query2XML_Data_Condition_NonEmpty'
        ),
        '&' => array(
            'XML/Query2XML/Data/Processor/Unserialize.php',
            'XML_Query2XML_Data_Processor_Unserialize'
        ),
        '=' => array(
            'XML/Query2XML/Data/Processor/CDATA.php',
            'XML_Query2XML_Data_Processor_CDATA'
        ),
        '^' => array(
            'XML/Query2XML/Data/Processor/Base64.php',
            'XML_Query2XML_Data_Processor_Base64'
        ),
        ':' => array(
            'XML/Query2XML/Data/Source/Static.php',
            'XML_Query2XML_Data_Source_Static'
        ),
        '#' => array(
            'XML/Query2XML/Data/Source/PHPCallback.php',
            'XML_Query2XML_Data_Source_PHPCallback'
        ),
        '~' => array(
            'XML/Query2XML/Data/Source/XPath.php',
            'XML_Query2XML_Data_Source_XPath'
        )
    );
    
    /**
     * Constructor
     *
     * @param mixed $backend A subclass of XML_Query2XML_Driver or
     *                       an instance of PEAR DB, PEAR MDB2, ADOdb,
     *                       PDO, Net_LDAP2 or Net_LDAP.
     */
    private function __construct($backend)
    {
        if ($backend instanceof XML_Query2XML_Driver) {
            $this->_driver = $backend;
        } else {
            $this->_driver = XML_Query2XML_Driver::factory($backend);
        }
    }
    
    /**
     * Factory method.
     * As first argument pass an instance of PDO, PEAR DB, PEAR MDB2, ADOdb,
     * Net_LDAP or an instance of any class that extends XML_Query2XML_Driver:
     * <code>
     * <?php
     * require_once 'XML/Query2XML.php';
     * $query2xml = XML_Query2XML::factory(
     *   new PDO('mysql://root@localhost/Query2XML_Tests')
     * );
     * ?>
     * </code>
     *
     * <code>
     * <?php
     * require_once 'XML/Query2XML.php';
     * require_once 'DB.php';
     * $query2xml = XML_Query2XML::factory(
     *   DB::connect('mysql://root@localhost/Query2XML_Tests')
     * );
     * ?>
     * </code>
     * 
     * <code>
     * <?php
     * require_once 'XML/Query2XML.php';
     * require_once 'MDB2.php';
     * $query2xml = XML_Query2XML::factory(
     *   MDB2::factory('mysql://root@localhost/Query2XML_Tests')
     * );
     * ?>
     * </code>
     * 
     * <code>
     * <?php
     * require_once 'XML/Query2XML.php';
     * require_once 'adodb/adodb.inc.php';
     * $adodb = ADONewConnection('mysql');
     * $adodb->Connect('localhost', 'root', '', 'Query2XML_Tests');
     * $query2xml = XML_Query2XML::factory($adodb);
     * ?>
     * </code>
     *
     * @param mixed $backend An instance of PEAR DB, PEAR MDB2, ADOdb, PDO,
     *                       Net_LDAP or a subclass of XML_Query2XML_Driver.
     *
     * @return XML_Query2XML A new instance of XML_Query2XML
     * @throws XML_Query2XML_DriverException If $backend already is a PEAR_Error.
     * @throws XML_Query2XML_ConfigException If $backend is not an instance of a
     *                  child class of DB_common, MDB2_Driver_Common, ADOConnection
     *                  PDO, Net_LDAP or XML_Query2XML_Driver.
     */
    public static function factory($backend)
    {
        return new XML_Query2XML($backend);
    }
    
    /**
     * Set a global option.
     * Currently the following global options are available:
     *
     * hidden_container_prefix: The prefix to use for container elements that are
     *   to be removed before the DOMDocument before it is returned by
     *   {@link XML_Query2XML::getXML()}. This has to be a non-empty string.
     *   The default value is '__'.
     *
     * @param string $option The name of the option
     * @param mixed  $value  The option value
     *
     * @return void
     * @throws XML_Query2XML_ConfigException If the configuration option
     *         does not exist or if the value is invalid for that option
     */
    public function setGlobalOption($option, $value)
    {
        switch ($option) {
        case 'hidden_container_prefix':
            if (is_string($value) && strlen($value) > 0) {
                // unit test: setGlobalOption/hidden_container_prefix.phpt
                $this->_globalOptions[$option] = $value;
            } else {
                /*
                 * unit test: setGlobalOption/
                 * configException_hidden_container_prefix_wrongTypeObject.phpt
                 * configException_hidden_container_prefix_wrongTypeEmptyStr.phpt
                 */
                throw new XML_Query2XML_ConfigException(
                    'The value for the hidden_container_prefix option '
                    . 'has to be a non-empty string'
                );
            }
            break;

        default:
            // unit tests: setGlobalOption/configException_noSuchOption.phpt
            throw new XML_Query2XML_ConfigException(
                'No such global option: ' . $option
            );
        }
    }
    
    /**
     * Returns the current value for a global option.
     * See {@link XML_Query2XML::setGlobalOption()} for a list
     * of available options.
     *
     * @param string $option The name of the option
     *
     * @return mixed The option's value
     * @throws XML_Query2XML_ConfigException If the option does not exist
     */
    public function getGlobalOption($option)
    {
        if (!isset($this->_globalOptions[$option])) {
            // unit test: getGlobalOption/configException_noSuchOption.phpt
            throw new XML_Query2XML_ConfigException(
                'No such global option: ' . $option
            );
        }
        // unit test: getGlobalOption/hidden_container_prefix.phpt
        return $this->_globalOptions[$option];
    }
    
    /**
     * Enable the logging of debug messages.
     * This will include all queries sent to the database.
     * Example:
     * <code>
     * <?php
     * require_once 'Log.php';
     * require_once 'XML/Query2XML.php';
     * $query2xml = XML_Query2XML::factory(MDB2::connect($dsn));
     * $debugLogger = Log::factory('file', 'out.log', 'XML_Query2XML');
     * $query2xml->enableDebugLog($debugLogger);
     * ?>
     * </code>
     * Please see {@link http://pear.php.net/package/Log} for details on PEAR Log.
     *
     * @param mixed $log Most likely an instance of PEAR Log but any object
     *                   that provides a method named 'log' is accepted.
     *
     * @return void
     */
    public function enableDebugLog($log)
    {
        // unit test: enableDebugLog/enableDebugLog.phpt
        $this->_debugLogger = $log;
        $this->_debug       = true;
    }
    
    /**
     * Disable the logging of debug messages
     *
     * @return void
     */
    public function disableDebugLog()
    {
        // unit test: disableDebugLog/disableDebugLog.phpt
        $this->_debug = false;
    }
    
    /**
     * Start profiling.
     *
     * @return void
     */
    public function startProfiling()
    {
        // unit tests: startProfile/startProfile.phpt
        $this->_profiling = true;
        $this->_profile   = array(
            'queries'    => array(),
            'start'      => microtime(1),
            'stop'       => 0,
            'duration'   => 0,
            'dbStop'     => 0,
            'dbDuration' => 0
        );
    }
    
    /**
     * Stop profiling.
     *
     * @return void
     */
    public function stopProfiling()
    {
        // unit test: stopProfile/stopProfile.phpt
        $this->_profiling = false;
        if (isset($this->_profile['start']) && $this->_profile['stop'] == 0) {
            $this->_profile['stop']     = microtime(1);
            $this->_profile['duration'] =
                $this->_profile['stop'] - $this->_profile['start'];
        }
    }
    
    /**
     * Returns all raw profiling data.
     * In 99.9% of all cases you will want to use getProfile().
     *
     * @see getProfile()
     * @return array
     */
    public function getRawProfile()
    {
        // unit test: getRawProfile/getRawProfile.phpt
        $this->stopProfiling();
        return $this->_profile;
    }
    
    /**
     * Returns the profile as a single multi line string.
     *
     * @return string The profiling data.
     */
    public function getProfile()
    {
        // unit test: getProfile/getProfile.phpt
        $this->stopProfiling();
        if (count($this->_profile) === 0) {
            return '';
        }
        $ret = 'COUNT AVG_DURATION DURATION_SUM SQL' . "\n";
        foreach ($this->_profile['queries'] as $sql => $value) {
            $durationSum   = 0.0;
            $durationCount = 0;
            $runTimes      =& $this->_profile['queries'][$sql]['runTimes'];
            foreach ($runTimes as $runTime) {
                $durationSum += ($runTime['stop'] - $runTime['start']);
                ++$durationCount;
            }
            if ($durationCount == 0) {
                // so that division does not fail
                $durationCount = 1;
            }
            $durationAverage = $durationSum / $durationCount;
            
            $ret .= str_pad($this->_profile['queries'][$sql]['count'], 5)
                  . ' '
                  . substr($durationAverage, 0, 12). ' '
                  . substr($durationSum, 0, 12). ' '
                  . $sql . "\n";
        }
        $ret .= "\n";
        $ret .= 'TOTAL_DURATION: ' . $this->_profile['duration'] . "\n";
        $ret .= 'DB_DURATION:    ' . $this->_profile['dbDuration'] . "\n";
        return $ret;
    }
    
    /**
     * Calls {@link XML_Query2XML::stopProfiling()} and then clears the profiling
     * data by resetting a private property.
     *
     * @return void
     */
    public function clearProfile()
    {
        // unit test: clearProfile/clearProfile.phpt
        $this->stopProfiling();
        $this->_profile = array();
    }
    
    /**
     * Transforms the data retrieved by a single SQL query into flat XML data.
     *
     * This method will return a new instance of DOMDocument. The column names
     * will be used as element names.
     *
     * Example:
     * <code>
     * <?php
     * require_once 'XML/Query2XML.php';
     * $query2xml = XML_Query2XML::factory(MDB2::connect($dsn));
     * $dom = $query2xml->getFlatXML(
     *   'SELECT * FROM artist',
     *   'music_library',
     *   'artist'
     * );
     * ?>
     * </code>
     *
     * @param string $sql         The query string.
     * @param string $rootTagName The name of the root tag; this argument is optional
     *                            (default: 'root').
     * @param string $rowTagName  The name of the tag used for each row; this
     *                            argument is optional (default: 'row').
     *
     * @return DOMDocument        A new instance of DOMDocument.
     * @throws XML_Query2XML_Exception This is the base class for the exception
     *                            types XML_Query2XML_DBException and
     *                            XML_Query2XML_XMLException. By catching
     *                            XML_Query2XML_Exception you can catch all
     *                            exceptions this method will ever throw.
     * @throws XML_Query2XML_DBException If a database error occurrs.
     * @throws XML_Query2XML_XMLException If an XML error occurrs - most likely
     *                            $rootTagName or $rowTagName is not a valid
     *                            element name.
     */
    public function getFlatXML($sql, $rootTagName = 'root', $rowTagName = 'row')
    {
        /*
         * unit tests: getFlatXML/*.phpt
         */
        $dom     = self::_createDOMDocument();
        $rootTag = self::_addNewDOMChild($dom, $rootTagName, 'getFlatXML');
        $records = $this->_getAllRecords(array('query' => $sql), 'getFlatXML', $sql);
        foreach ($records as $record) {
            $rowTag = self::_addNewDOMChild($rootTag, $rowTagName, 'getFlatXML');
            foreach ($record as $field => $value) {
                self::_addNewDOMChild(
                    $rowTag,
                    $field,
                    'getFlatXML',
                    self::_utf8encode($value)
                );
            }
        }
        return $dom;
    }
    
    /**
     * Transforms your SQL data retrieved by one or more queries into complex and
     * highly configurable XML data.
     *
     * This method will return a new instance of DOMDocument.
     * Please see the <b>{@tutorial XML_Query2XML.pkg tutorial}</b> for details.
     * 
     * @param mixed $sql     A string an array or the boolean value false.
     * @param array $options Options for the creation of the XML data stored in an
     *                       associative, potentially mutli-dimensional array
     *                       (please see the tutorial).
     *
     * @return DOMDocument   The XML data as a new instance of DOMDocument.
     * @throws XML_Query2XML_Exception This is the base class for the exception types
     *                       XML_Query2XML_DBException, XML_Query2XML_XMLException
     *                       and XML_Query2XML_ConfigException. By catching
     *                       XML_Query2XML_Exception you can catch all exceptions
     *                       this method will ever throw.
     * @throws XML_Query2XML_DBException If a database error occurrs.
     * @throws XML_Query2XML_XMLException If an XML error occurrs - most likely
     *                       an invalid XML element name.
     * @throws XML_Query2XML_ConfigException If some configuration options passed
     *                       as second argument are invalid or missing.
     */
    public function getXML($sql, $options)
    {
        /*
        * unit tests: getXML/*.phpt
        */
        
        // the default root tag name is 'root'
        if (isset($options['rootTag'])) {
            $rootTagName = $options['rootTag'];
        } else {
            $rootTagName = 'root';
        }
        
        $dom     = self::_createDOMDocument();
        $rootTag = self::_addNewDOMChild($dom, $rootTagName, '[rootTag]');
        
        $options['sql'] = $sql;
        
        if ($options['sql'] === false) {
            $options['sql'] = '';
        }
        $this->_preprocessOptions($options);
        
        /* Used to store the information which element has been created
        *  for which ID column value.
        */
        $tree = array();
        
        if ($sql === false) {
            $records = array(array()); // one empty record
        } else {
            $records = $this->_applySqlOptionsToRecord(
                $options,
                $emptyRecord = array()
            );
        }
        
        foreach ($records as $key => $record) {
            $tag = $this->_getNestedXMLRecord($records[$key], $options, $dom, $tree);
            
            /* _getNestedXMLRecord() returns false if an element already existed for
            *  the current ID column value.
            */
            if ($tag !== false) {
                $rootTag->appendChild($tag);
            }
        }
        
        $this->_stopDBProfiling();
        
        self::_removeContainers(
            $dom,
            $this->getGlobalOption('hidden_container_prefix')
        );
        return $dom;
    }
    
    /**
     * Perform pre-processing on $options.
     * This is a recursive method; it will call itself for every complex element
     * specification and every complex attribute specification found.
     *
     * @param array  &$options An associative array
     * @param string $context  Indecates whether an element or an attribute is
     *                         to be processed.
     *
     * @return void
     * @throws XML_Query2XML_ConfigException If a mandatory option is missing
     *                       or any option is defined incorrectly.
     */
    private function _preprocessOptions(&$options, $context = 'elements')
    {
        if (!isset($options['--q2x--path'])) {
            // things to do only at the root level
            $options['--q2x--path'] = '';
            
            if (!isset($options['rowTag'])) {
                $options['rowTag'] = 'row';
            }
            
            if (!isset($options['idColumn'])) {
                /*
                * unit test: _preprocessOptions/
                *  throwConfigException_idcolumnOptionMissing.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    'The configuration option "idColumn" '
                    . 'is missing on the root level.'
                );
            }
        }
        
        foreach (array('encoder', 'mapper') as $option) {
            if (isset($options[$option])) {
                if (
                    is_string($options[$option]) &&
                    strpos($options[$option], '::') !== false
                ) {
                    $options[$option] = split('::', $options[$option]);
                }
                if (
                    $options[$option] !== false
                    &&
                    !($option == 'encoder' && $options[$option] === null)
                    &&
                    !($option == 'mapper' && $options[$option] == false)
                    &&
                    !is_callable($options[$option], false, $callableName)
                ) {
                    /*
                    * Only check whether $options['encoder'] is callable if it's not
                    * set to:
                    * - false (don't use an encoder)
                    * - null (use self::_utf8encode()).
                    *
                    * unit test: _preprocessOptions/
                    *  throwConfigException_encoderNotCallableStaticMethod1.phpt
                    *  throwConfigException_encoderNotCallableStaticMethod2.phpt
                    *  throwConfigException_encoderNotCallableNonstaticMethod.phpt
                    *  throwConfigException_encoderNotCallableFunction.phpt
                    *
                    *
                    * Only check whether $options['mapper'] is callable if
                    * - $options['mapper'] != false
                    *
                    * unit tests: _preprocessOptions/
                    *  throwConfigException_mapperNotCallableStaticMethod1.phpt
                    *  throwConfigException_mapperNotCallableStaticMethod2.phpt
                    *  throwConfigException_mapperNotCallableNonstaticMethod.phpt
                    *  throwConfigException_mapperNotCallableFunction.phpt
                    */
                    throw new XML_Query2XML_ConfigException(
                        $options['--q2x--path'] . '[' . $option . ']: The '
                        . 'method/function "' . $callableName . '" is not callable.'
                    );
                }
            } else {
                $options[$option] = null;
            }
        }
        
        if ($context == 'elements') {
            foreach (array('elements', 'attributes') as $option) {
                if (isset($options[$option])) {
                    if (!is_array($options[$option])) {
                        /*
                        * unit test: _preprocessOptions/
                        *  throwConfigException_attributesOptionWrongType.phpt
                        *  throwConfigException_elementsOptionWrongType.phpt
                        */
                        throw new XML_Query2XML_ConfigException(
                            $options['--q2x--path'] . '[' . $option . ']: '
                            . 'array expected, ' . gettype($options[$option])
                            . ' given.'
                        );
                    }
                    foreach ($options[$option] as $key => $columnStr) {
                        $configPath = $options['--q2x--path'] . '[' . $option
                                      . '][' . $key . ']';
                        if (is_string($columnStr)) {
                            $options[$option][$key] =
                                $this->_buildCommandChain($columnStr, $configPath);
                            if (
                                is_numeric($key) &&
                                is_object($options[$option][$key])
                            ) {
                                /*
                                 * unit test: _preprocessOptions/
                                 *  throwConfigException_prefix_noArrayKey.phpt
                                 */
                                throw new XML_Query2XML_ConfigException(
                                    $configPath . ': the element name has to be '
                                    . 'specified as the array key when prefixes '
                                    . 'are used within the value specification'
                                );
                            }
                        } elseif (is_array($columnStr)) {
                            $options[$option][$key]['--q2x--path'] = $configPath;
                            
                            // encoder option used by elements as well as attributes
                            if (
                                !array_key_exists(
                                    'encoder',
                                    $options[$option][$key]
                                 )
                            ) {
                                $options[$option][$key]['encoder'] =
                                    $options['encoder'];
                            }
                            if ($option == 'elements') {
                                // these options are only used by elements
                                if (
                                    !isset($options[$option][$key]['rootTag']) ||
                                    $options[$option][$key]['rootTag'] == ''
                                ) {
                                    /*
                                     * If rootTag is not set or an empty string:
                                     * create a hidden root tag
                                     */
                                    $options[$option][$key]['rootTag'] = 
                                        $this->getGlobalOption(
                                            'hidden_container_prefix'
                                        ) . $key;
                                }
                                if (!isset($options[$option][$key]['rowTag'])) {
                                    $options[$option][$key]['rowTag'] = $key;
                                }
        
                                foreach (array('mapper', 'idColumn') as $option2) {
                                    if (
                                        !array_key_exists(
                                            $option2,
                                            $options[$option][$key]
                                        )
                                    ) {
                                        $options[$option][$key][$option2] =
                                            $options[$option2];
                                    }
                                }
                            }
                            $this->_preprocessOptions(
                                $options[$option][$key],
                                $option
                            );
                        } elseif (self::_isCallback($columnStr)) {
                            if (is_numeric($key)) {
                                /*
                                 * unit test: _preprocessOptions/
                                 *  throwConfigException_callbackInterface_
                                 *  noArrayKey.phpt
                                 */
                                throw new XML_Query2XML_ConfigException(
                                    $configPath . ': the element name has to be '
                                    . 'specified as the array key when the value '
                                    . 'is specified using an instance of '
                                    . 'XML_Query2XML_Callback.'
                                );
                            }
                        } else {
                            /*
                             * $columnStr is neither a string, an array or an
                             * instance of XML_Query2XML_Callback.
                             *
                             * unit tests:
                             *  _getNestedXMLRecord/
                             *   throwConfigException_attributeSpecWrongType.phpt
                             *  _preprocessOptions/
                             *   throwConfigException_callbackInterface_
                             *    complexAttributeSpec.phpt
                             *    simpleAttributeSpec.phpt
                             *    simpleElementSpec.phpt
                             */
                            throw new XML_Query2XML_ConfigException(
                                $configPath . ': array, string or instance of'
                                . ' XML_Query2XML_Callback expected, '
                                . gettype($columnStr)
                                . ' given.'
                            );
                        }
                    }
                }
            } // end of foreach (array('elements', 'attributes'))
        } else {
            // $context == 'attributes'
            if (!isset($options['value'])) {
                /*
                * the option "value" is mandatory
                * unit test: _preprocessOptions/
                *  throwConfigException_valueOptionMissing.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    $options['--q2x--path'] . '[value]: Mandatory option "value" '
                    . 'missing from the complex attribute specification.'
                );
            }
        }
        
        $opt = array('value', 'condition', 'dynamicRowTag', 'idColumn');
        foreach ($opt as $option) {
            if (isset($options[$option])) {
                if (is_string($options[$option])) {
                    $options[$option] = $this->_buildCommandChain(
                        $options[$option],
                        $options['--q2x--path'] . '[value]'
                    );
                } elseif (
                    !self::_isCallback($options[$option]) &&
                    !($option == 'idColumn' && $options[$option] === false)
                ) {
                    /*
                    * unit tests:
                    *  _preprocessOptions/
                    *   throwConfigException_callbackInterface_
                    *    complexElementSpec.phpt
                    *    condition.phpt
                    *    idColumn.phpt
                    */
                    throw new XML_Query2XML_ConfigException(
                        $options['--q2x--path'] . '[' . $option . ']: string or'
                        . ' instance of XML_Query2XML_Callback expected, '
                        . gettype($options[$option])
                        . ' given.'
                    );
                }
            }
        }
        
        if (isset($options['query'])) {
            $options['sql'] = $options['query'];
        }
        if (isset($options['sql'])) {
            
            // we will pre-process $options['sql_options'] first
            if (isset($options['query_options'])) {
                $options['sql_options'] = $options['query_options'];
            }
            if (!isset($options['sql_options'])) {
                $options['sql_options'] = array();
            }
            $sql_options = array(
                'cached', 'single_record', 'merge', 'merge_master', 'merge_selective'
            );
            foreach ($sql_options as $option) {
                if (!isset($options['sql_options'][$option])) {
                    $options['sql_options'][$option] = false;
                }
            }
            if (isset($options['sql_options']['uncached'])) {
                $options['sql_options']['cached'] =
                    !$options['sql_options']['uncached'];
            }
            
            if ($options['sql_options']['cached']) {
                if (!is_array($options['sql'])) {
                    $options['sql'] = array('query' => $options['sql']);
                }
                if (isset($options['sql']['driver'])) {
                    $driver = $options['sql']['driver'];
                } else {
                    $driver = $this->_driver;
                }
                if (
                    !class_exists('XML_Query2XML_Driver_Caching') ||
                    !($driver instanceof XML_Query2XML_Driver_Caching)
                ) {
                    include_once 'XML/Query2XML/Driver/Caching.php';
                    $options['sql']['driver'] = new XML_Query2XML_Driver_Caching(
                        $driver
                    );
                }
            }
            
            if (
                $options['sql_options']['merge_selective'] !== false &&
                !is_array($options['sql_options']['merge_selective'])
            ) {
                /*
                * unit test: _preprocessOptions/
                *  throwConfigException_mergeselectiveOptionWrongType.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    $options['--q2x--path'] . '[sql_options][merge_selective]: '
                    . 'array expected, '
                    . gettype($options['sql_options']['merge_selective']) . ' given.'
                );
            }
            // end of pre-processing of $options['sql_options']
            
            if (
                is_array($options['sql']) && 
                isset($options['sql']['driver']) &&
                $options['sql']['driver'] instanceof XML_Query2XML_Driver
            ) {
                $query = $options['sql']['driver']->preprocessQuery(
                    $options['sql'],
                    $options['--q2x--path'] . '[sql]'
                );
            } else {
                $query = $this->_driver->preprocessQuery(
                    $options['sql'],
                    $options['--q2x--path'] . '[sql]'
                );
            }
            $options['--q2x--query_statement'] = $query;
            if (
                is_array($options['sql']) && 
                isset($options['sql']['driver']) &&
                !($options['sql']['driver'] instanceof XML_Query2XML_Driver)
            ) {
                /*
                 * unit test: _preprocessOptions
                 *  throwConfigException_sqlOptionWrongType.phpt
                 */
                throw new XML_Query2XML_ConfigException(
                    $options['--q2x--path'] . '[sql][driver]: '
                    . 'instance of XML_Query2XML_Driver expected, '
                    . gettype($options['sql']['driver']) . ' given.'
                );
            }
            
            if (is_array($options['sql'])) {
                if (isset($options['sql']['data'])) {
                    if (is_array($options['sql']['data'])) {
                        foreach ($options['sql']['data'] as $key => $data) {
                            if (is_string($data)) {
                                $options['sql']['data'][$key] =
                                    $this->_buildCommandChain(
                                        $options['sql']['data'][$key],
                                        $options['--q2x--path']
                                            . '[sql][data][' . $key . ']'
                                    );
                            } elseif (!self::_isCallback($data)) {
                                /*
                                * unit tests: _preprocessOptions/
                                *   throwConfigException_callbackInterface_data.phpt
                                */
                                throw new XML_Query2XML_ConfigException(
                                    $options['--q2x--path'] . '[sql][data][' . $key
                                    . ']: string or'
                                    . ' instance of XML_Query2XML_Callback expected,'
                                    . ' ' . gettype($options['sql']['data'][$key])
                                    . ' given.'
                                );
                            }
                        }
                    } else {
                        /*
                        * unit test: _preprocessOptions/
                        *  throwConfigException_dataOptionWrongType.phpt
                        */
                        throw new XML_Query2XML_ConfigException(
                            $options['--q2x--path'] . '[sql][data]: array expected, '
                            . gettype($options['sql']['data']) . ' given.'
                        );
                    }
                }
            }
        } // end of if (isset($options['sql'])
    }
    
    /**
     * Private recursive method that creates the nested XML elements from a record.
     *
     * getXML calls this method for every row in the initial result set.
     * The $tree argument deserves some more explanation. All DOMNodes are stored
     * in $tree the way they appear in the XML document. The same hirachy needs to be
     * built so that we can know if a DOMNode that corresponds to a column ID of 2 is
     * already a child node of a certain XML element. Let's have a look at an example
     * to clarify this:
     * <code>
     * <music_library>
     *   <artist>
     *     <artistid>1</artistid>
     *     <albums>
     *       <album>
     *         <albumid>1</albumid>
     *       </album>
     *       <album>
     *         <albumid>2</albumid>
     *       </album>
     *     </albums>
     *   </artist>
     *   <artist>
     *     <artistid>3</artistid>
     *     <albums />
     *   </artist>
     * </music_library>
     * </code>
     * would be represended in the $tree array as something like this:
     * <code>
     * array (
     *   [1] => array (
     *     [tag] => DOMElement Object
     *     [elements] => array (
     *       [albums] => array (
     *         [1] => array (
     *           [tag] => DOMElement Object
     *         )
     *         [2] => array (
     *           [tag] => DOMElement Object
     *         )
     *       )
     *     )
     *   )
     *   [2] => array (
     *     [tag] => DOMElement Object
     *     [elements] => array
     *     (
     *       [albums] => array ()
     *     )
     *   )
     * )
     * </code>
     * The numbers in the square brackets are column ID values.
     *
     * @param array       $record   An associative array representing a record;
     *                              column names must be used as keys.
     * @param array       &$options An array containing the options for this nested 
     *                              element; this will be a subset of the array
     *                              originally passed to getXML().
     * @param DOMDocument $dom      An instance of DOMDocument.
     * @param array       &$tree    An associative multi-dimensional array, that is
     *                              used to store the information which tag has
     *                              already been created for a certain ID column
     *                              value. It's format is:
     *                              Array(
     *                                "$id1" => Array(
     *                                  'tag' => DOMElement,
     *                                  'elements' => Array(
     *                                    "$id2" => Array(
     *                                      'tag' => DOMElement,
     *                                      'elements' => Array( ... )
     *                                    ),
     *                                    "$id3" => ...
     *                                  )
     *                                )
     *                              )
     *
     * @return mixed           The XML element's representation as a new instance of
     *                         DOMNode or the boolean value false (meaning no
     *                         new tag was created).
     * @throws XML_Query2XML_DBException  Bubbles up through this method if thrown by
     *                         - _processComplexElementSpecification()
     * @throws XML_Query2XML_XMLException Bubbles up through this method if thrown by
     *                         - _createDOMElement()
     *                         - _setDOMAttribute
     *                         - _appendTextChildNode()
     *                         - _addNewDOMChild()
     *                         - _addDOMChildren()
     *                         - _processComplexElementSpecification()
     *                         - _expandShortcuts()
     *                         - _executeEncoder()
     * @throws XML_Query2XML_ConfigException  Thrown if
     *                         - $options['idColumn'] is not set
     *                         - $options['elements'] is set but not an array
     *                         - $options['attributes'] is set but not an array
     *                         Bubbles up through this method if thrown by
     *                         - _applyColumnStringToRecord()
     *                         - _processComplexElementSpecification()
     *                         - _expandShortcuts()
     * @throws XML_Query2XML_Exception  Bubbles up through this method if thrown by
     *                         - _expandShortcuts()
     *                         - _applyColumnStringToRecord()
     */
    private function _getNestedXMLRecord($record, &$options, $dom, &$tree)
    {
        // the default row tag name is 'row'
        if (isset($options['dynamicRowTag'])) {
            $rowTagName = $this->_applyColumnStringToRecord(
                $options['dynamicRowTag'],
                $record,
                $options['--q2x--path'] . '[dynamicRowTag]'
            );
        } else {
            $rowTagName = $options['rowTag'];
        }
        
        if ($options['idColumn'] === false) {
            static $uniqueIdCounter = 0;
            $id = ++$uniqueIdCounter;
        } else {
            $id = $this->_applyColumnStringToRecord(
                $options['idColumn'],
                $record,
                $options['--q2x--path'] . '[idColumn]'
            );
        
            if ($id === null) {
                // the ID column is NULL
                return false;
            } elseif (is_object($id) || is_array($id)) {
                /*
                * unit test: _getNestedXMLRecord/
                *   throwConfigException_idcolumnOptionWrongTypeArray.phpt
                *   throwConfigException_idcolumnOptionWrongTypeObject.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    $options['--q2x--path'] . '[idColumn]: Must evaluate to a '
                    . 'value that is not an object or an array.'
                );
            }
        }
        
        /* Is there already an identical tag (identity being determined by the
        *  value of the ID-column)?
        */
        if (isset($tree[$id])) {
            if (isset($options['elements'])) {
                foreach ($options['elements'] as $tagName => $column) {
                    if (is_array($column)) {
                        $this->_processComplexElementSpecification(
                            $record,
                            $options['elements'][$tagName],
                            $tree[$id],
                            $tagName
                        );
                    }
                }
            }
            /*
            * We return false because $tree[$id]['tag'] is already
            * a child of the parent element.
            */
            return false;
        } else {
            $tree[$id] = array();
            
            if (isset($options['value'])) {
                $parsedValue = $this->_applyColumnStringToRecord(
                    $options['value'],
                    $record,
                    $options['--q2x--path'] . '[value]'
                );
                if (!$this->_evaluateCondition($parsedValue, $options['value'])) {
                    // this element is to be skipped
                    return false;
                }
            }
            if (isset($options['condition'])) {
                $continue = $this->_applyColumnStringToRecord(
                    $options['condition'],
                    $record,
                    $options['--q2x--path'] . '[condition]'
                );
                if (!$continue) {
                    // this element is to be skipped
                    return false;
                }
            }
            $tree[$id]['tag'] = self::_createDOMElement(
                $dom,
                $rowTagName,
                $options['--q2x--path'] . '[rowTag/dynamicRowTag]'
            );

            $tag = $tree[$id]['tag'];
            
            // add attributes
            if (isset($options['attributes'])) {
                if (!isset($options['processed'])) {
                    $options['attributes'] = self::_expandShortcuts(
                        $options['attributes'],
                        $record,
                        $options['mapper'],
                        $options['--q2x--path'] . '[attributes]'
                    );
                }
                foreach ($options['attributes'] as $attributeName => $column) {
                    if (is_array($column)) {
                        // complex attribute specification
                        $this->_processComplexAttributeSpecification(
                            $attributeName, $record, $column, $tree[$id]['tag']
                        );
                    } else {
                        // simple attribute specifications
                        $attributeValue = $this->_applyColumnStringToRecord(
                            $column,
                            $record,
                            $options['--q2x--path']
                            . '[attributes][' . $attributeName . ']'
                        );
                        if ($this->_evaluateCondition($attributeValue, $column)) {
                            self::_setDOMAttribute(
                                $tree[$id]['tag'],
                                $attributeName,
                                self::_executeEncoder(
                                    $attributeValue,
                                    $options
                                ),
                                $options['--q2x--path']
                                . '[attributes][' . $attributeName . ']'
                            );
                        }
                    }
                }
            }
            if (isset($options['value'])) {
                if ($parsedValue instanceof DOMNode || is_array($parsedValue)) {
                    /*
                    * The value returned from _applyColumnStringToRecord() and
                    * stored in $parsedValue is an instance of DOMNode or an
                    * array of DOMNode instances. _addDOMChildren() will handle
                    * both.
                    */
                    self::_addDOMChildren(
                        $tree[$id]['tag'],
                        $parsedValue,
                        $options['--q2x--path'] . '[value]',
                        true
                    );
                } else {
                    if ($parsedValue !== false && !is_null($parsedValue)) {
                        self::_appendTextChildNode(
                            $tree[$id]['tag'],
                            self::_executeEncoder(
                                $parsedValue,
                                $options
                            ),
                            $options['--q2x--path'] . '[value]'
                        );
                    }
                }
            }
            
            // add child elements
            if (isset($options['elements'])) {
                if (!isset($options['processed'])) {
                    $options['elements'] = self::_expandShortcuts(
                        $options['elements'],
                        $record,
                        $options['mapper'],
                        $options['--q2x--path'] . '[elements]'
                    );
                }
                foreach ($options['elements'] as $tagName => $column) {
                    if (is_array($column)) {
                        // complex element specification
                        $this->_processComplexElementSpecification(
                            $record,
                            $options['elements'][$tagName],
                            $tree[$id],
                            $tagName
                        );
                    } else {
                        // simple element specification
                        $tagValue = $this->_applyColumnStringToRecord(
                            $column,
                            $record,
                            $options['--q2x--path'] . '[elements][' . $tagName . ']'
                        );
                        if ($this->_evaluateCondition($tagValue, $column)) {
                            if (
                                $tagValue instanceof DOMNode ||
                                is_array($tagValue)
                            ) {
                                /*
                                * The value returned from
                                * _applyColumnStringToRecord() and stored in
                                * $tagValue is an instance of DOMNode or an array
                                * of DOMNode instances. self::_addDOMChildren()
                                * will handle both.
                                */
                                self::_addDOMChildren(
                                    self::_addNewDOMChild(
                                        $tree[$id]['tag'],
                                        $tagName,
                                        $options['--q2x--path']
                                        . '[elements][' . $tagName . ']'
                                    ),
                                    $tagValue,
                                    $options['--q2x--path']
                                    . '[elements][' . $tagName . ']',
                                    true
                                );
                            } else {
                                self::_addNewDOMChild(
                                    $tree[$id]['tag'],
                                    $tagName,
                                    $options['--q2x--path']
                                    . '[elements][' . $tagName . ']',
                                    self::_executeEncoder(
                                        $tagValue,
                                        $options
                                    )
                                );
                            }
                        }
                    }
                }
            }
            
            // some things only need to be done once
            $options['processed'] = true;
            
            /*
            *  We return $tree[$id]['tag'] because it needs to be added to it's
            *  parent; this is to be handled by the method that called
            *  _getNestedXMLRecord().
            */
            return $tree[$id]['tag'];
        }
    }
    
    /**
     * Private method that will expand asterisk characters in an array
     * of simple element specifications.
     *
     * This method gets called to handle arrays specified using the 'elements'
     * or the 'attributes' option. An element specification that contains an
     * asterisk will be duplicated for each column present in $record.
     * Please see the {@tutorial XML_Query2XML.pkg tutorial} for details.
     *
     * @param Array  &$elements  An array of simple element specifications.
     * @param Array  &$record    An associative array that represents a single
     *                           record.
     * @param mixed  $mapper     A valid argument for call_user_func(), a full method
     *                           method name (e.g. "MyMapperClass::map") or a value
     *                           that == false for no special mapping at all.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return Array The extended array.
     * @throws XML_Query2XML_ConfigException If only the column part but not the
     *                        explicitly defined tagName part contains an asterisk.
     * @throws XML_Query2XML_Exception Will bubble up if it is thrown by
     *                        _mapSQLIdentifierToXMLName(). This should never
     *                        happen as _getNestedXMLRecord() already checks if
     *                        $mapper is callable.
     * @throws XML_Query2XML_XMLException Will bubble up if it is thrown by
     *                        _mapSQLIdentifierToXMLName() which will happen if the
     *                        $mapper function called, throws any exception.
     */
    private function _expandShortcuts(&$elements, &$record, $mapper, $configPath)
    {
        $newElements = array();
        foreach ($elements as $tagName => $column) {
            if (is_numeric($tagName)) {
                $tagName = $column;
            }
            if (!is_array($column) && strpos($tagName, '*') !== false) {
                // expand all occurences of '*' to all column names
                foreach ($record as $columnName => $value) {
                    $newTagName = str_replace('*', $columnName, $tagName);
                    if (is_string($column)) {
                        $newColumn = str_replace('*', $columnName, $column);
                    } elseif (
                        class_exists('XML_Query2XML_Data') &&
                        $column instanceof XML_Query2XML_Data
                    ) {
                        $newColumn = clone $column;
                        $callback  = $newColumn->getFirstPreProcessor();
                        if (
                            class_exists('XML_Query2XML_Data_Source') &&
                            $callback instanceof XML_Query2XML_Data_Source
                        ) {
                            $callback->replaceAsterisks($columnName);
                        }
                    } else {
                        $newColumn =& $column;
                    }
                    // do the mapping
                    $newTagName = self::_mapSQLIdentifierToXMLName(
                        $newTagName,
                        $mapper,
                        $configPath . '[' . $tagName . ']'
                    );
                    if (!isset($newElements[$newTagName])) {
                        // only if the tagName hasn't already been used
                        $newElements[$newTagName] = $newColumn;
                    }
                }
            } else {
                /*
                * Complex element specifications will always be dealt with here.
                * We don't want any mapping or handling of the asterisk shortcut
                * to be done for complex element specifications.
                */
            
                if (!is_array($column)) {
                    // do the mapping but not for complex element specifications
                    $tagName = self::_mapSQLIdentifierToXMLName(
                        $tagName,
                        $mapper,
                        $configPath . '[' . $tagName . ']'
                    );
                }
                    
                /*
                 * explicit specification without an asterisk;
                 * this always overrules an expanded asterisk
                 */
                unset($newElements[$tagName]);
                $newElements[$tagName] = $column;
            }
        }
        return $newElements;
    }
    
    /**
     * Maps an SQL identifier to an XML name using the supplied $mapper.
     *
     * @param string $sqlIdentifier The SQL identifier as a string.
     * @param mixed  $mapper        A valid argument for call_user_func(), a full
     *                              method method name (e.g. "MyMapperClass::map")
     *                              or a value that == false for no special mapping
     *                              at all.
     * @param string $configPath    The config path; used for exception messages.
     *
     * @return string The mapped XML name.
     * @throws XML_Query2XML_Exception If $mapper is not callable. This should never
     *                              happen as _getNestedXMLRecord() already checks
     *                              if $mapper is callable.
     * @throws XML_Query2XML_XMLException If the $mapper function called, throws any
     *                              exception.
     */
    private function _mapSQLIdentifierToXMLName($sqlIdentifier, $mapper, $configPath)
    {
        if (!$mapper) {
            // no mapper was defined
            $xmlName = $sqlIdentifier;
        } else {
            if (is_callable($mapper, false, $callableName)) {
                try {
                    $xmlName = call_user_func($mapper, $sqlIdentifier);
                } catch (Exception $e) {
                    /*
                    * This will also catch XML_Query2XML_ISO9075Mapper_Exception
                    * if $mapper was "XML_Query2XML_ISO9075Mapper::map".
                    * unit test:
                    *  _mapSQLIdentifierToXMLName/throwXMLException.phpt
                    */
                    throw new XML_Query2XML_XMLException(
                        $configPath . ': Could not map "' . $sqlIdentifier
                        . '" to an XML name using the mapper '
                        . $callableName . ': ' . $e->getMessage()
                    );
                }
            } else {
                /*
                * This should never happen as _preprocessOptions() already
                * checks if $mapper is callable. Therefore no unit tests
                * can be provided for this exception.
                */
                throw new XML_Query2XML_ConfigException(
                    $configPath . ': The mapper "' . $callableName
                    . '" is not callable.'
                );
            }
        }
        return $xmlName;
    }
    
    /**
     * Private method that processes a complex element specification
     * for {@link XML_Query2XML::_getNestedXMLRecord()}.
     *
     * @param array  &$record  The current record.
     * @param array  &$options The current options.
     * @param array  &$tree    Associative multi-dimensional array, that is used to
     *                         store which tags have already been created
     * @param string $tagName  The element's name.
     *
     * @return void
     * @throws XML_Query2XML_XMLException This exception will bubble up
     *                        if it is thrown by _getNestedXMLRecord(),
     *                        _applySqlOptionsToRecord() or _addDOMChildren().
     * @throws XML_Query2XML_DBException  This exception will bubble up
     *                        if it is thrown by _applySqlOptionsToRecord()
     *                        or _getNestedXMLRecord().
     * @throws XML_Query2XML_ConfigException This exception will bubble up
     *                        if it is thrown by _applySqlOptionsToRecord()
     *                        or _getNestedXMLRecord().
     * @throws XML_Query2XML_Exception  This exception will bubble up if it
     *                        is thrown by _getNestedXMLRecord().
     */
    private function _processComplexElementSpecification(&$record, &$options, &$tree,
        $tagName)
    {
        $tag = $tree['tag'];
        if (!isset($tree['elements'])) {
            $tree['elements'] = array();
        }
        if (!isset($tree['elements'][$tagName])) {
            $tree['elements'][$tagName]            = array();
            $tree['elements'][$tagName]['rootTag'] = self::_addNewDOMChild(
                $tag,
                $options['rootTag'],
                $options['--q2x--path'] . '[rootTag]'
            );
        }
        
        $records =& $this->_applySqlOptionsToRecord($options, $record);
        
        for ($i = 0; $i < count($records); $i++) {
            self::_addDOMChildren(
                $tree['elements'][$tagName]['rootTag'],
                $this->_getNestedXMLRecord(
                    $records[$i],
                    $options,
                    $tag->ownerDocument,
                    $tree['elements'][$tagName]
                ),
                $options['--q2x--path']
            );
        }
    }
    
    /**
     * Private method that processes a complex attribute specification
     * for {@link XML_Query2XML::_getNestedXMLRecord()}.
     *
     * A complex attribute specification consists of an associative array
     * with the keys 'value' (mandatory), 'condition', 'sql' and 'sql_options'.
     *
     * @param string  $attributeName The name of the attribute as it was specified
     *                               using the array key of the complex attribute
     *                               specification.
     * @param array   &$record       The current record.
     * @param array   &$options      The complex attribute specification itself.
     * @param DOMNode $tag           The DOMNode to which the attribute is to be
     *                               added.
     *
     * @return void
     * @throws XML_Query2XML_XMLException This exception will bubble up
     *                          if it is thrown by _setDOMAttribute(),
     *                          _applyColumnStringToRecord(),
     *                          _applySqlOptionsToRecord() or _executeEncoder().
     * @throws XML_Query2XML_DBException  This exception will bubble up
     *                          if it is thrown by _applySqlOptionsToRecord().
     * @throws XML_Query2XML_ConfigException This exception will bubble up
     *                          if it is thrown by _applySqlOptionsToRecord() or
     *                          _applyColumnStringToRecord(). It will also be thrown 
     *                          by this method if $options['value'] is not set.
     */
    private function _processComplexAttributeSpecification($attributeName, &$record,
        &$options, $tag)
    {
        if (isset($options['condition'])) {
            $continue = $this->_applyColumnStringToRecord(
                $options['condition'],
                $record,
                $options['--q2x--path'] . '[condition]'
            );
            if (!$continue) {
                // this element is to be skipped
                return;
            }
        }
        
        // only fetching a single record makes sense for a single attribute
        $options['sql_options']['single_record'] = true;
        
        $records = $this->_applySqlOptionsToRecord($options, $record);
        if (count($records) == 0) {
            /*
            * $options['sql'] was set but the query did not return any records.
            * Therefore this attribute is to be skipped.
            */
            return;
        }
        $attributeRecord = $records[0];
        
        $attributeValue = $this->_applyColumnStringToRecord(
            $options['value'],
            $attributeRecord,
            $options['--q2x--path'] . '[value]'
        );
        if ($this->_evaluateCondition($attributeValue, $options['value'])) {
            self::_setDOMAttribute(
                $tag,
                $attributeName,
                self::_executeEncoder($attributeValue, $options),
                $options['--q2x--path'] . '[value]'
            );
        }
    }
                    
    /**
     * Private method to apply the givenen sql option to a record.
     *
     * This method handles the sql options 'single_record',
     * 'merge', 'merge_master' and 'merge_selective'. Please see the
     * {@tutorial XML_Query2XML.pkg tutorial} for details.
     * 
     * @param array &$options An associative multidimensional array of options.
     * @param array &$record  The current record as an associative array.
     *
     * @return array          An indexed array of records that are themselves
     *                        represented as associative arrays.
     * @throws XML_Query2XML_ConfigException This exception is thrown if
     *                        - a column specified in merge_selective does not exist
     *                          in the result set
     *                        - it bubbles up from _applyColumnStringToRecord()
     * @throws XML_Query2XML_DBException This exception will bubble up
     *                        if it is thrown by _getAllRecords().
     * @throws XML_Query2XML_XMLException It will bubble up if it is thrown
     *                        by _applyColumnStringToRecord().
     */
    private function _applySqlOptionsToRecord(&$options, &$record)
    {
        if (!isset($options['sql'])) {
            return array($record);
        }
        
        $single_record   = $options['sql_options']['single_record'];
        $merge           = $options['sql_options']['merge'];
        $merge_master    = $options['sql_options']['merge_master'];
        $merge_selective = $options['sql_options']['merge_selective'];

        $sql = $options['sql'];
        if (is_array($sql)) {
            if (isset($sql['data'])) {
                foreach ($sql['data'] as $key => $columnStr) {
                    $sql['data'][$key] = $this->_applyColumnStringToRecord(
                        $columnStr,
                        $record,
                        $options['--q2x--path'] . '[sql][data][' . $key . ']'
                    );
                }
            }
        }
        $sqlConfigPath = $options['--q2x--path'] . '[sql]';
        
        $records =& $this->_getAllRecords(
            $sql,
            $sqlConfigPath,
            $options['--q2x--query_statement']
        );
        if ($single_record && isset($records[0])) {
            $records = array($records[0]);
        }
        
        if (is_array($merge_selective)) {
            // selective merge
            if ($merge_master) {
                // current records are master
                for ($ii = 0; $ii < count($merge_selective); $ii++) {
                    for ($i = 0; $i < count($records); $i++) {
                        if (!array_key_exists($merge_selective[$ii], $record)) {
                            /* Selected field does not exist in the parent record
                            * (passed as argumnet $record)
                            * unit test: _applySqlOptionsToRecord/
                            *  throwConfigException_mergeMasterTrue.phpt
                            */
                            throw new XML_Query2XML_ConfigException(
                                $options['--q2x--path'] . '[sql_options]'
                                . '[merge_selective]['. $ii . ']: The column "'
                                . $merge_selective[$ii] . '" '
                                . 'was not found in the result set.'
                            );
                        }
                        if (!array_key_exists($merge_selective[$ii], $records[$i])) {
                            // we are the master, so only if it does not yet exist
                            $records[$i][$merge_selective[$ii]] =
                                $record[$merge_selective[$ii]];
                        }
                    }
                }
            } else {
                // parent record is master
                for ($ii = 0; $ii < count($merge_selective); $ii++) {
                    for ($i = 0; $i < count($records); $i++) {
                        if (!array_key_exists($merge_selective[$ii], $record)) {
                            /* Selected field does not exist in the parent record
                            *  (passed as argumnet $record)
                            *  unit test: _applySqlOptionsToRecord/
                            *   throwConfigException_mergeMasterFalse.phpt
                            */
                            throw new XML_Query2XML_ConfigException(
                                $options['--q2x--path'] . '[sql_options]'
                                . '[merge_selective]['. $ii . ']: The column "'
                                . $merge_selective[$ii] . '" '
                                . 'was not found in the result set.'
                            );
                        }
                        // parent is master!
                        $records[$i][$merge_selective[$ii]] =
                            $record[$merge_selective[$ii]];
                    }
                }
            }
        } elseif ($merge) {
            // regular merge
            if ($merge_master) {
                for ($i = 0; $i < count($records); $i++) {
                    $records[$i] = array_merge($record, $records[$i]);
                } 
            } else {
                for ($i = 0; $i < count($records); $i++) {
                    $records[$i] = array_merge($records[$i], $record);
                }
            }
        }
        return $records;
    }
    
    /**
     * Private method to apply a column string to a record.
     * Please see the tutorial for details on the different column strings.
     *
     * @param string $columnStr  A valid column name or an instance of a class
     *                           implementing XML_Query2XML_Callback.
     * @param array  &$record    The record as an associative array.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return mixed A value that can be cast to a string or an instance of DOMNode.
     * @throws XML_Query2XML_ConfigException  Thrown if $columnStr is not
     *               a string or an instance of XML_Query2XML_Callback or if
     *               $record[$columnStr] does not exist (and $columnStr has
     *               no special prefix).
     * @throws XML_Query2XML_XMLException     Thrown if the '&' prefix was used
     *               but the data was not unserializeable, i.e. not valid XML data.
     */
    private function _applyColumnStringToRecord($columnStr, &$record, $configPath)
    {
        if (self::_isCallback($columnStr)) {
            $value = $columnStr->execute($record);
        } elseif (is_string($columnStr)) {
            if (array_key_exists($columnStr, $record)) {
                $value = $record[$columnStr];
            } else {
                /*
                * unit test:
                *  _applyColumnStringToRecord/throwConfigException_element1.phpt
                *  _applyColumnStringToRecord/throwConfigException_element2.phpt
                *  _applyColumnStringToRecord/throwConfigException_idcolumn.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    $configPath . ': The column "' . $columnStr
                    . '" was not found in the result set.'
                );
                
            }
        } else {
            // should never be reached
            throw new XML_Query2XML_ConfigException(
                $configPath . ': string or instance of XML_Query2XML_Callback'
                . ' expected, ' . gettype($columnStr) . ' given.'
            );
        }
        return $value;
    }
    
    /**
     * Returns whether $value is to be included in the output.
     * If $spec is a string an is prefixed by a question mark this method will
     * return false if $value is null or is a string with a length of zero. In
     * any other case, this method will return the true.
     *
     * @param string $value The value.
     * @param mixed  $spec  The value specification. This can be a string
     *                      or an instance of XML_Query2XML_Callback.
     *
     * @return boolean Whether $value is to be included in the output.
     */
    private function _evaluateCondition($value, $spec)
    {
        return !class_exists('XML_Query2XML_Data_Condition') ||
               !$spec instanceof XML_Query2XML_Data_Condition ||
               $spec->evaluateCondition($value);
    }
            
    /**
     * Private method to fetch all records from a result set.
     *
     * @param mixed  $sql            The SQL query as a string or an array.
     * @param string $configPath     The config path; used for exception messages.
     * @param string $queryStatement The query as a string; it will be used for
     *                               logging and profiling.
     *
     * @return array An array of records. Each record itself will be an
     *                   associative array.
     */
    private function &_getAllRecords($sql, $configPath, $queryStatement)
    {
        // $queryStatement will be used for profiling
        if ($this->_profiling || $this->_debug) {
            $loggingQuery = $queryStatement;
            if (is_array($sql) && isset($sql['data']) && is_array($sql['data'])) {
                $loggingQuery .= '; DATA:' . implode(',', $sql['data']);
            }
            $this->_debugStartQuery($loggingQuery, $queryStatement);
        }
        
        if (is_array($sql) && isset($sql['driver'])) {
            $driver = $sql['driver'];
        } else {
            $driver = $this->_driver;
        }
        $records = $driver->getAllRecords($sql, $configPath);
        
        $this->_debugStopQuery($queryStatement);
        return $records;
    }
    
    /**
     * Initializes a query's profile (only used if profiling is turned on).
     *
     * @param mixed &$sql The SQL query as a string or an array.
     *
     * @return void
     * @see startProfiling()
     */
    private function _initQueryProfile(&$sql)
    {
        if (!isset($this->_profile['queries'][$sql])) {
            $this->_profile['queries'][$sql] = array(
                'count' => 0,
                'runTimes' => array()
            );
        }
    }
    
    /**
     * Starts the debugging and profiling of the query passed as argument.
     *
     * @param string $loggingQuery   The query statement as it will be logged.
     * @param string $profilingQuery The query statement as it will be used for
     *                               profiling.
     *
     * @return void
     */
    private function _debugStartQuery($loggingQuery, $profilingQuery)
    {
        $this->_debug('QUERY: ' . $loggingQuery);
        if ($this->_profiling) {
            $this->_initQueryProfile($profilingQuery);
            ++$this->_profile['queries'][$profilingQuery]['count'];
            $this->_profile['queries'][$profilingQuery]['runTimes'][] = array(
                'start' => microtime(true),
                'stop' => 0
            );
        }
    }
    
    /**
     * Ends the debugging and profiling of the query passed as argument.
     *
     * @param string $profilingQuery The query statement as it will be used for
     *                               profiling.
     *
     * @return void
     */
    private function _debugStopQuery($profilingQuery)
    {
        $this->_debug('DONE');
        if ($this->_profiling) {
            $this->_initQueryProfile($profilingQuery);
            $lastIndex =
                count(
                    $this->_profile['queries'][$profilingQuery]['runTimes']
                ) - 1;
            
            $this->_profile['queries'][$profilingQuery]['runTimes'][$lastIndex]['stop'] =
                microtime(true);
        }
    }
    
    /**
     * Stops the DB profiling.
     * This will set $this->_profile['dbDuration'].
     *
     * @return void
     */
    private function _stopDBProfiling()
    {
        if ($this->_profiling && isset($this->_profile['start'])) {
            $this->_profile['dbStop']     = microtime(1);
            $this->_profile['dbDuration'] =
                $this->_profile['dbStop'] - $this->_profile['start'];
        }
    }
    
    /**
     * Private method used to log debug messages.
     * This method will do no logging if $this->_debug is set to false.
     *
     * @param string $msg The message to log.
     *
     * @return void
     * @see _debugLogger
     * @see _debug
     */
    private function _debug($msg)
    {
        if ($this->_debug) {
            $this->_debugLogger->log($msg);
        }
    }
    
    /**
     * Returns whether $object is an instance of XML_Query2XML_Callback.
     *
     * @param mixed $object The variable to check.
     *
     * @return boolean
     */
    private static function _isCallback($object)
    {
        return is_object($object) &&
               interface_exists('XML_Query2XML_Callback') &&
               $object instanceof XML_Query2XML_Callback;
    }
    
    /**
     * Parse specifications that use the prifixes ?, &, =, ^, :,  or #.
     *
     * This method will produce a number of chained Data Class objects all of
     * which be an instance of the abstract class XML_Query2XML_Data.
     *
     * @param string $columnStr  The original specification.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return mixed An instance of XML_Query2XML_Callback or a column
     *               name as a string.
     * @throws XML_Query2XML_ConfigException Bubbles up through this method if
     *                                       thrown by any of the command class
     *                                       constructors.
     */
    private function _buildCommandChain($columnStr, $configPath)
    {
        $prefixList = implode('', array_keys($this->_prefixes));
        if (ltrim($columnStr, $prefixList) == $columnStr) {
            return $columnStr;
        }
        
        $firstCallback = null;
        for ($i = 0; $i < strlen($columnStr); $i++) {
            $prefix = substr($columnStr, $i, 1);
            if (isset($this->_prefixes[$prefix])) {
                $columnSubStr = substr($columnStr, $i + 1);
                $filePath     = $this->_prefixes[$prefix][0];
                $className    = $this->_prefixes[$prefix][1];
                if ($columnSubStr === false) {
                    $columnSubStr = '';
                }
                
                if ($filePath) {
                    include_once $filePath;
                }
                
                if (!in_array(
                        'XML_Query2XML_Data',
                        class_parents($className)
                    )
                ) {
                    throw new XML_Query2XML_ConfigException(
                        $configPath . ': Prefix class ' . $className . ' does ' .
                        'not extend XML_Query2XML_Data.'
                    );
                }
                
                if (in_array(
                        'XML_Query2XML_Data_Source',
                        class_parents($className)
                    )
                ) {
                    // data source prefix
                    $callback = call_user_func_array(
                        array($className, 'create'),
                        array($columnSubStr, $configPath)
                    );
                } else {
                    // data processing prefix
                    $callback = call_user_func_array(
                        array($className, 'create'),
                        array(null, $configPath)
                    );
                    
                    if (ltrim($columnSubStr, $prefixList) == $columnSubStr) {
                        // no more prefixes: ColumnValue is the default data source
                        include_once 'XML/Query2XML/Data/Source/ColumnValue.php';
                        $callback->setPreProcessor(
                            new XML_Query2XML_Data_Source_ColumnValue(
                                $columnSubStr,
                                $configPath
                            )
                        );
                    }
                }
                
                if (is_null($firstCallback)) {
                    $firstCallback = $callback;
                } else {
                    if (
                        $callback instanceof XML_Query2XML_Data_Condition &&
                        !($firstCallback instanceof XML_Query2XML_Data_Condition)
                    ) {
                        throw new XML_Query2XML_ConfigException(
                            $configPath . ': conditional prefixes always have to '
                            . 'go first.'
                        );
                    }
                    $firstCallback->getFirstPreProcessor()->setPreProcessor(
                        $callback
                    );
                }
                if (
                    $firstCallback->getFirstPreProcessor()
                    instanceof XML_Query2XML_Data_Source
                ) {
                    // there can only be one data source
                    break;
                }
            } else {
                break;
            }
        }
        if (is_null($firstCallback)) {
            return $columnStr;
        } else {
            return $firstCallback;
        }
    }
    
    /**
     * Creates a new instance of DOMDocument.
     * '1.0' is passed as first argument and 'UTF-8' as second to the
     * DOMDocument constructor.
     *
     * @return DOMDocument The new instance.
     */
    private static function _createDOMDocument()
    {
        return new DOMDocument('1.0', 'UTF-8');
    }
    
    /**
     * Create and then add a new child element.
     *
     * @param DOMNode $element    The parent DOMNode the new DOM element should be
     *                            appended to.
     * @param string  $name       The tag name of the new element.
     * @param string  $configPath The config path; used for exception messages.
     * @param string  $value      The value of a child text node. This argument is
     *                            optional. The default is the boolean value false,
     *                            which means that no child text node will be
     *                            appended.
     *
     * @return DOMNode The newly created DOMNode instance that was appended
     *                 to $element.
     * @throws XML_Query2XML_XMLException This exception will bubble up if it is
     *                 thrown by _createDOMElement().
     */
    private static function _addNewDOMChild(DOMNode $element, $name, $configPath,
        $value = false)
    {
        if ($element instanceof DOMDocument) {
            $dom = $element;
        } else {
            $dom = $element->ownerDocument;
        }
        $child = self::_createDOMElement($dom, $name, $configPath, $value);
        $element->appendChild($child);
        return $child;
    }
    
    /**
     * Helper method to create a new instance of DOMNode
     *
     * @param DOMDocument $dom        An instance of DOMDocument. It's
     *                                createElement() method is used to create the
     *                                new DOMNode instance.
     * @param string      $name       The tag name of the new element.
     * @param string      $configPath The config path; used for exception messages.
     * @param string      $value      The value of a child text node. This argument
     *                                is optional. The default is the boolean value
     *                                false, which means that no child text node will
     *                                be appended.
     *
     * @return DOMNode An instance of DOMNode.
     * @throws XML_Query2XML_XMLException If $name is an invalid XML identifier.
     *                                    Also it will bubble up if it is thrown by
     *                                    _appendTextChildNode().
     */
    private static function _createDOMElement(DOMDocument $dom, $name, $configPath,
        $value = false)
    {
        try {
            $element = $dom->createElement($name);
        } catch(DOMException $e) {
            /*
            * unit tests:
            *  _createDOMElement/throwXMLException_elementInvalid1.phpt
            *  _createDOMElement/throwXMLException_elementInvalid2.phpt
            *  _createDOMElement/throwXMLException_roottagOptionInvalid1.phpt
            *  _createDOMElement/throwXMLException_roottagOptionInvalid2.phpt
            *  _createDOMElement/throwXMLException_rowtagOptionInvalid.phpt
            */
            throw new XML_Query2XML_XMLException(
                $configPath . ': "' . $name . '" is an invalid XML element name: '
                . $e->getMessage(),
                $e
            );
        }
        self::_appendTextChildNode($element, $value, $configPath);
        return $element;
    }
    
    /**
     * Append a new child text node to $element.
     * $value must already be UTF8-encoded; this is to be handled
     * by self::_executeEncoder() and $options['encoder'].
     *
     * This method will not create and append a child text node
     * if $value === false || is_null($value).
     *
     * @param DOMNode $element    An instance of DOMNode
     * @param string  $value      The value of the text node.
     * @param string  $configPath The config path; used for exception messages.
     *
     * @return void
     * @throws XML_Query2XML_XMLException Any lower-level DOMException will 
     *                 wrapped and re-thrown as a XML_Query2XML_XMLException. This 
     *                 will happen if $value cannot be UTF8-encoded for some reason.
     *                 It will also be thrown if $value is an object or an array
     *                 (and can therefore not be converted into a string).
     */
    private static function _appendTextChildNode(DOMNode $element,
                                                 $value,
                                                 $configPath)
    {
        if ($value === false || is_null($value)) {
            return;
        } elseif (is_object($value) || is_array($value)) {
            /*
            * Objects and arrays cannot be cast
            * to a string without an error.
            *
            * unit test:
            * _appendTextChildNode/throwXMLException.phpt
            */
            throw new XML_Query2XML_XMLException(
                $configPath . ': A value of the type ' . gettype($value)
                . ' cannot be used for a text node.'
            );
        }
        $dom = $element->ownerDocument;
        try {
            $element->appendChild($dom->createTextNode($value));
        } catch(DOMException $e) {
            // this should never happen as $value is UTF-8 encoded
            throw new XML_Query2XML_XMLException(
                $configPath . ': "' . $value . '" is not a vaild text node: '
                . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Set the attribute $name with a value of $value for $element.
     * $value must already be UTF8-encoded; this is to be handled
     * by self::_executeEncoder() and $options['encoder'].
     *
     * @param DOMNode $element    An instance of DOMNode
     * @param string  $name       The name of the attribute to set.
     * @param string  $value      The value of the attribute to set.
     * @param string  $configPath The config path; used for exception messages.
     *
     * @return void
     * @throws XML_Query2XML_XMLException Any lower-level DOMException will be
     *                 wrapped and re-thrown as a XML_Query2XML_XMLException. This
     *                 will happen if $name is not a valid attribute name. It will
     *                 also be thrown if $value is an object or an array (and can
     *                 therefore not be converted into a string).
     */
    private static function _setDOMAttribute(DOMNode $element,
                                             $name,
                                             $value,
                                             $configPath)
    {
        if (is_object($value) || is_array($value)) {
            /*
            * Objects and arrays cannot be cast
            * to a string without an error.
            *
            * unit test:
            * _setDOMAttribute/throwXMLException.phpt
            */
            throw new XML_Query2XML_XMLException(
                $configPath . ': A value of the type ' . gettype($value)
                . ' cannot be used for an attribute value.'
            );
        }
        
        try {
            $element->setAttribute($name, $value);
        } catch(DOMException $e) {
            // no unit test available for this one
            throw new XML_Query2XML_XMLException(
                $configPath . ': "' . $name . '" is an invalid XML attribute name: '
                . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Adds one or more child nodes to an existing DOMNode instance.
     *
     * @param DOMNode $base       An instance of DOMNode.
     * @param mixed   $children   An array of DOMNode instances or
     *                            just a single DOMNode instance.
     *                            Boolean values of false are always ignored.
     * @param string  $configPath The config path; used for exception messages.
     * @param boolean $import     Whether DOMDocument::importNode() should be called
     *                            for $children. This is necessary if the instance(s)
     *                            passed as $children was/were created using a
     *                            different DOMDocument instance. This argument is
     *                            optional. The default is false.
     *
     * @return void
     * @throws XML_Query2XML_XMLException If one of the specified children
     *                         is not one of the following: an instance of DOMNode,
     *                         the boolean value false, or an array containing
     *                         these two.
     */
    private static function _addDOMChildren(DOMNode $base,
                                            $children,
                                            $configPath,
                                            $import = false)
    {
        if ($children === false) {
            // don't do anything
            return;
        } elseif ($children instanceof DOMNode) {
            // $children is a single complex child
            if ($import) {
                $children = $base->ownerDocument->importNode($children, true);
            }
            $base->appendChild($children);
        } elseif (is_array($children)) {
            for ($i = 0; $i < count($children); $i++) {
                if ($children[$i] === false) {
                    // don't do anything
                } elseif ($children[$i] instanceof DOMNode) {
                    if ($import) {
                        $children[$i] = $base->ownerDocument->importNode(
                            $children[$i],
                            true
                        );
                    }
                    $base->appendChild($children[$i]);
                } else {
                    /*
                    * unit tests:
                    * _addDOMChildren/throwXMLException_arrayWithObject.phpt
                    * _addDOMChildren/throwXMLException_arrayWithString.phpt
                    * _addDOMChildren/throwXMLException_arrayWithInt.phpt
                    * _addDOMChildren/throwXMLException_arrayWithBool.phpt
                    * _addDOMChildren/throwXMLException_arrayWithDouble.phpt
                    */
                    throw new XML_Query2XML_XMLException(
                        $configPath . ': DOMNode, false or an array of the two '
                        . 'expected, but ' . gettype($children[$i]) . ' given '
                        . '(hint: check your callback).'
                    );
                }
            }
        } else {
            /*
             * This should never happen because _addDOMChildren() is only called
             * for arrays and instances of DOMNode.
             */
            throw new XML_Query2XML_XMLException(
                $configPath . ': DOMNode, false or an array of the two '
                . 'expected, but ' . gettype($children) . ' given '
                . '(hint: check your callback).'
            );
        }
    }
    
    /**
     * Remove all container elements created by XML_Query2XML to ensure that all
     * elements are correctly ordered.
     *
     * This is a recursive method. This method calls
     * {@link XML_Query2XML::_replaceParentWithChildren()}. For the concept of
     * container elements please see the {@tutorial XML_Query2XML.pkg tutorial}.
     *
     * @param DOMNode $element               An instance of DOMNode.
     * @param string  $hiddenContainerPrefix The containers that will be removed
     *                                       all start with this string.
     *
     * @return void
     */
    private static function _removeContainers($element, $hiddenContainerPrefix)
    {
        $xpath      = new DOMXPath($element);
        $containers = $xpath->query(
            '//*[starts-with(name(),\'' . $hiddenContainerPrefix . '\')]'
        );
        foreach ($containers as $container) {
            if (!is_null($container->parentNode)) {
                self::_replaceParentWithChildren($container);
            }
        }
    }
    
    /**
     * Replace a certain node with its child nodes.
     *
     * @param DOMNode $parent An instance of DOMNode.
     *
     * @return void
     */
    private static function _replaceParentWithChildren(DOMNode $parent)
    {
        
        $child = $parent->firstChild;
        while ($child) {
            $nextChild = $child->nextSibling;
            $parent->removeChild($child);
            $parent->parentNode->insertBefore($child, $parent);
            $child = $nextChild;
        }
        $parent->parentNode->removeChild($parent);
    }
    
    /**
     * Calls an encoder for XML node and attribute values
     * $options['encoder'] can be one of the following:
     * - null: self::_utf8encode() will be used
     * - false: no encoding will be performed
     * - callback: a string or an array as defined by the
     *   callback pseudo-type; please see
     *   http://www.php.net/manual/en/
     *   language.pseudo-types.php#language.types.callback
     *
     * @param string $str     The string to encode
     * @param array  $options An associative array with $options['encoder'] set.
     *
     * @return void
     * @throws XML_Query2XML_XMLException If the $options['encoder'] is a callback
     *                                    function that threw an exception.
     */
    private static function _executeEncoder($str, $options)
    {
        if (!is_string($str) || $options['encoder'] === false) {
            return $str;
        }
        
        if ($options['encoder'] === null) {
            return self::_utf8encode($str);
        }
        
        try {
            return call_user_func($options['encoder'], $str);
        } catch (Exception $e) {
            /*
            * unit test:
            *  _executeEncoder/throwXMLException.phpt
            */
            throw new XML_Query2XML_XMLException(
                $options['--q2x--path'] . '[encoder]: Could not encode '
                . '"' . $str . '": ' . $e->getMessage()
            );
        }
    }
    
    /**
     * UTF-8 encode $str using mb_conver_encoding or if that is not
     * present, utf8_encode.
     *
     * @param string $str The string to encode
     *
     * @return String The UTF-8 encoded version of $str
     */
    private static function _utf8encode($str)
    {
        if (function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($str, 'UTF-8');
        } else {
            $str = utf8_encode($str);
        }
        return $str;
    }
}

/**
 * Parent class for ALL exceptions thrown by this package.
 * By catching XML_Query2XML_Exception you will catch all exceptions
 * thrown by XML_Query2XML.
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_Exception extends PEAR_Exception
{
    
    /**
     * Constructor method
     *
     * @param string    $message   The error message.
     * @param Exception $exception The Exception that caused this exception 
     *                             to be thrown. This argument is optional.
     */
    public function __construct($message, $exception = null)
    {
        parent::__construct($message, $exception);
    }
}

/**
 * Exception for driver errors
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 * @since    Release 1.6.0RC1
 */
class XML_Query2XML_DriverException extends XML_Query2XML_Exception
{
    /**
     * Constructor
     *
     * @param string $message The error message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**
 * Exception for database errors
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_DBException extends XML_Query2XML_DriverException
{
    /**
     * Constructor
     *
     * @param string $message The error message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**
 * Exception for XML errors
 * In most cases this exception will be thrown if a DOMException occurs.
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_XMLException extends XML_Query2XML_Exception
{
    /**
     * Constructor
     *
     * @param string       $message   The error message.
     * @param DOMException $exception The DOMException that caused this exception 
     *                                to be thrown. This argument is optional.
     */
    public function __construct($message, DOMException $exception = null)
    {
        parent::__construct($message, $exception);
    }
}

/**
 * Exception that handles configuration errors.
 *
 * This exception handels errors in the $options array passed to
 * XML_Query2XML::getXML() and wrong arguments passed to the constructor via
 * XML_Query2XML::factory().
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 * @see      XML_Query2XML::getXML()
 */
class XML_Query2XML_ConfigException extends XML_Query2XML_Exception
{
    /**
     * Constructor method
     *
     * @param string $message A detailed error message.
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}

/**
 * Abstract driver class.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory($backend);
 * </code>
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version  Release: 1.7.1
 * @link     http://pear.php.net/package/XML_Query2XML
 * @since    Release 1.5.0RC1
 */
abstract class XML_Query2XML_Driver
{
    /**
     * This method, when implemented executes the query passed as the
     * first argument and returns all records from the result set.
     *
     * The format of the first argument depends on the driver being used.
     *
     * @param mixed  $sql        The SQL query as a string or an array.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return array An array of records. Each record itself will be an
     *               associative array.
     * @throws XML_Query2XML_DriverException If some driver related error occures.
     */
    abstract public function getAllRecords($sql, $configPath);
    
    /**
     * Pre-processes a query specification and returns a string representation
     * of the query.
     *
     * The returned string will be used for logging purposes. It
     * does not need to be valid SQL.
     *
     * If $query is a string, it will be changed to array('query' => $query).
     *
     * @param mixed  &$query     A string or an array containing the element 'query'.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return string The query statement as a string.
     * @throws XML_Query2XML_ConfigException If $query is an array but does not
     *                                       contain the element 'query'.
     */
    public function preprocessQuery(&$query, $configPath)
    {
        if (is_string($query)) {
            $query = array('query' => $query);
        } elseif (is_array($query)) {
            if (!isset($query['query'])) {
                /*
                * unit test: _preprocessOptions/
                *  throwConfigException_queryOptionMissing.phpt
                */
                throw new XML_Query2XML_ConfigException(
                    $configPath . ': The configuration option'
                    . ' "query" is missing.'
                );
            }
        } else { //neither a string nor an array
            /*
            * unit test: _preprocessOptions/
            *  throwConfigException_sqlOptionWrongType.phpt
            */
            throw new XML_Query2XML_ConfigException(
                $configPath . ': array or string expected, '
                . gettype($query) . ' given.'
            );
        }
        return $query['query'];
    }
    
    /**
     * Factory method.
     *
     * @param mixed $backend An instance of MDB2_Driver_Common, PDO, DB_common,
     *                  ADOConnection, Net_LDAP2 or Net_LDAP.
     *
     * @return XML_Query2XML_Driver An instance of a driver class that
     *                  extends XML_Query2XML_Driver.
     * @throws XML_Query2XML_DriverException If $backend already is a PEAR_Error.
     * @throws XML_Query2XML_ConfigException If $backend is not an instance of a
     *                  child class of MDB2_Driver_Common, PDO, DB_common,
     *                  ADOConnection, Net_LDAP2 or Net_LDAP.
     */
    public static function factory($backend)
    {
        if (
            class_exists('MDB2_Driver_Common') &&
            $backend instanceof MDB2_Driver_Common
        ) {
            include_once 'XML/Query2XML/Driver/MDB2.php';
            return new XML_Query2XML_Driver_MDB2($backend);
        } elseif (class_exists('PDO') && $backend instanceof PDO) {
            include_once 'XML/Query2XML/Driver/PDO.php';
            return new XML_Query2XML_Driver_PDO($backend);
        } elseif (class_exists('DB_common') && $backend instanceof DB_common) {
            include_once 'XML/Query2XML/Driver/DB.php';
            return new XML_Query2XML_Driver_DB($backend);
        } elseif (
            class_exists('ADOConnection') &&
            $backend instanceof ADOConnection
        ) {
            include_once 'XML/Query2XML/Driver/ADOdb.php';
            return new XML_Query2XML_Driver_ADOdb($backend);
        } elseif (class_exists('Net_LDAP') && $backend instanceof Net_LDAP) {
            include_once 'XML/Query2XML/Driver/LDAP.php';
            return new XML_Query2XML_Driver_LDAP($backend);
        } elseif (class_exists('Net_LDAP2') && $backend instanceof Net_LDAP2) {
            include_once 'XML/Query2XML/Driver/LDAP2.php';
            return new XML_Query2XML_Driver_LDAP2($backend);
        } elseif (class_exists('PEAR_Error') && $backend instanceof PEAR_Error) {
            //unit tests: NoDBLayer/factory/throwDBException.phpt
            throw new XML_Query2XML_DriverException(
                'Driver error: ' . $backend->toString()
            );
        } else {
            //unit test: NoDBLayer/factory/throwConfigException.phpt
            throw new XML_Query2XML_ConfigException(
                'Argument passed to the XML_Query2XML constructor is not an '
                . 'instance of DB_common, MDB2_Driver_Common, ADOConnection'
                . ', PDO, Net_LDAP or Net_LDAP2.'
            );
        }
    }
}
?>