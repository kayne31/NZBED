<?php
/**
 * This file contains the class XML_Query2XML_Driver_LDAP2.
 *
 * PHP version 5
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2007 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   CVS: $Id: LDAP2.php,v 1.3 2008/05/09 20:54:26 lukasfeiler Exp $
 * @link      http://pear.php.net/package/XML_Query2XML
 */

/**
 * XML_Query2XML_Driver_LDAP2 extends XML_Query2XML_Driver.
 */
require_once 'XML/Query2XML.php';

/**
 * XML_Query2XML_Driver_LDAP2 uses Net_LDAP2.
 */
require_once 'Net/LDAP2.php';

/**
 * Net_LDAP2_Util is required for its escape_filter_value() method.
 */
require_once 'Net/LDAP2/Util.php';

/**
 * PEAR is required for its isError() method.
 */
require_once 'PEAR.php';

/**
 * Driver for Net_LDAP2.
 *
 * usage:
 * <code>
 * $driver = XML_Query2XML_Driver::factory(new Net_LDAP2(...));
 * </code>
 *
 * This LDAP driver is built upon PEAR Net_LDAP2 and provides three features:
 * - prepare & execute like usage of placeholders in "base" and "filter"
 * - handling missing attributes
 *   in LDAP an entity does not have to use all available attributes,
 *   while XML_Query2XML expects every record to have the same columns;
 *   this driver solves the problem by setting all missing columns to null.
 * - handling multi-value attributes
 *   XML_Query2XML expects every record to be a one-dimensional associative
 *   array. In order to achieve this result this driver creates as many
 *   records for each LDAP entry as are necassary to accomodate all values
 *   of an attribute.
 *
 * @category  XML
 * @package   XML_Query2XML
 * @author    Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @copyright 2006 Lukas Feiler
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @version   Release: 1.7.1
 * @link      http://pear.php.net/package/XML_Query2XML
 * @since     Release 1.7.0RC1
 */
class XML_Query2XML_Driver_LDAP2 extends XML_Query2XML_Driver
{
    /**
     * In instance of Net_LDAP2
     * @var Net_LDAP2
     */
    private $_ldap = null;
    
    /**
     * Constructor
     *
     * @param Net_LDAP2 $ldap An instance of PEAR Net_LDAP2.
     */
    public function __construct(Net_LDAP2 $ldap)
    {
        $this->_ldap = $ldap;
    }
    
    /**
     * Pre-processes LDAP query specifications.
     *
     * @param array  &$query     An array optionally containing the elements
     *                           'base', 'filter', 'options' and 'data'.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return string A string representation of $query
     */
    public function preprocessQuery(&$query, $configPath)
    {
        if (!is_array($query)) {
            /*
             * unit test: XML_Query2XML_Driver_LDAP-preprocessQuery/
             *  throwConfigException_queryNotAnArray.phpt
             */
            throw new XML_Query2XML_ConfigException(
                $configPath . ': array expected, ' . gettype($query) . ' given.'
            );
        }
        $queryStatement = 'basedn:';
        if (isset($query['base'])) {
            $queryStatement .= $query['base'];
        } else {
            $queryStatement .= 'default';
        }
        if (isset($query['filter'])) {
            if (class_exists('Net_LDAP2_Filter') &&
                $query['filter'] instanceof Net_LDAP2_Filter
            ) {
                $queryStatement .= '; filter:' . $query['filter']->asString();
            } else {
                $queryStatement .= '; filter:' . $query['filter'];
            }
        }
        if (isset($query['options'])) {
            $queryStatement .= '; options:' . print_r($query['options'], 1);
        }
        return $queryStatement;
    }
    
    /**
     * Execute a LDAP query stement and fetch all results.
     *
     * @param mixed  $query      The SQL query as a string or an array.
     * @param string $configPath The config path; used for exception messages.
     *
     * @return array An array of records.
     * @throws XML_Query2XML_LDAP2Exception If Net_LDAP2::search() returns an error.
     * @see XML_Query2XML_Driver::getAllRecords()
     */
    public function getAllRecords($query, $configPath)
    {
        $base    = null;
        $filter  = null;
        $options = array();
        if (isset($query['base'])) {
            $base = $query['base'];
        }
        if (isset($query['filter'])) {
            $filter = $query['filter'];
        }
        if (isset($query['options'])) {
            $options = $query['options'];
        }
        
        if (isset($options['query2xml_placeholder'])) {
            $placeholder = $options['query2xml_placeholder'];
        } else {
            $placeholder = '?';
        }
        unset($options['query2xml_placeholder']);
        
        if (isset($query['data']) && is_array($query['data'])) {
            $data = Net_LDAP2_Util::escape_filter_value($query['data']);
            $base = self::_replacePlaceholders($base, $data, $placeholder);
            if (is_string($filter)) {
                $filter = self::_replacePlaceholders($filter, $data, $placeholder);
            }
        }
        $search = $this->_ldap->search($base, $filter, $options);
        
        if (PEAR::isError($search)) {
            /*
             * unit test: getXML/throwLDAPException_queryError.phpt
             */
            throw new XML_Query2XML_LDAP2Exception(
                $configPath . ': Could not run LDAP search query: '
                . $search->toString()
            );
        }
        
        $records = array();
        $entries = $search->entries();
        foreach ($entries as $key => $entry) {
            $records[] = $entry->getValues();
        }
        $search->done();
        
        $records = self::_processMultiValueAttributes($records);
        
        // set missing attriubtes to null
        if (isset($options['attributes']) && is_array($options['attributes'])) {
            foreach ($options['attributes'] as $attribute) {
                for ($i = 0; $i < count($records); $i++) {
                    if (!array_key_exists($attribute, $records[$i])) {
                        $records[$i][$attribute] = null;
                    }
                }
            }
        }
        return $records;
    }
    
    /**
     * Creates multiple records for each entry that has mult-value attributes.
     * XML_Query2XML can only handle records represented by a one-dimensional
     * associative array. An entry like
     * <pre>
     * dn: cn=John Doe,ou=people,dc=example,dc=com
     * cn: John Doe
     * mail: john.doe@example.com
     * mail: jdoe@example.com
     * mail: jd@example.com
     * mobile: 555-666-777
     * mobile: 666-777-888
     * </pre>
     * therefore has to be converted into multiple one-dimensional associative
     * arrays (i.e. records):
     * <pre>
     * cn        mail                  mobile
     * -------------------------------------------------------
     * John Doe  john.doe@example.com  555-666-777
     * John Doe  jdoe@example.com      666-777-888
     * John Doe  jd@example.com        555-666-777
     * </pre>
     * Note that no cartasian product of the mail-values and the mobile-values
     * is produced. The number of records returned is equal to the number
     * values assigned to the attribute that has the most values (here
     * it's the mail attribute that has 3 values). To make sure that every
     * record has valid values for all attributes/columns, we start with
     * the first value after reaching the last one (e.g. the last record
     * for jd@example.com has a mobile of 555-666-777).
     *
     * @param array $entries A multi-dimensional associative array.
     *
     * @return void
     */
    private static function _processMultiValueAttributes($entries)
    {
        $records = array();
        foreach ($entries as $entry) {
            $multiValueAttributes = array();
            
            // will hold the name of the attribute with the most values
            $maxValuesAttribute = null;
            $maxValues          = 0;
            
            // loop over all attributes
            foreach ($entry as $attributeName => $attribute) {
                if (is_array($attribute)) {
                    $multiValueAttributes[$attributeName] = array($attribute, 0);
                    if ($maxValues < count($attribute)) {
                        $maxValues          = count($attribute);
                        $maxValuesAttribute = $attributeName;
                    }
                    $multiValueAttributesMap[$attributeName] = count($attribute);
                }
            }
            
            if (count($multiValueAttributes) > 0) {
                /*
                 * $multiValueAttributes is something like:
                 * array(
                 *   ['email'] => array(
                 *     array(
                 *       'john.doe@example.com'
                 *     ),
                 *     0  // index used to keep track of where we are
                 *   ['telephoneNumber'] => array(
                 *     array(
                 *       '555-111-222',
                 *       '555-222-333'
                 *     ),
                 *     0  // index used to keep track of where we are
                 *   )
                 * )
                 */
                $combinations = array();
                
                $maxValuesAttributeValues =
                    $multiValueAttributes[$maxValuesAttribute][0];
                unset($multiValueAttributes[$maxValuesAttribute]);
                
                foreach ($maxValuesAttributeValues as $value) {
                    $combination                      = array();
                    $combination[$maxValuesAttribute] = $value;
                    
                    /*
                     * Get the next value for each multi-value attribute.
                     * When the last value has been reached start again at
                     * the first one.
                     */
                    foreach (array_keys($multiValueAttributes) as $attributeName) {
                        $values =& $multiValueAttributes[$attributeName][0];
                        $index  =& $multiValueAttributes[$attributeName][1];
                        $count  =& count($values);
                        
                        if ($index == $count) {
                            $index = 0;
                        }
                        $combination[$attributeName] = $values[$index++];
                    }
                    $combinations[] = $combination;
                }
                foreach ($combinations as $combination) {
                    $records[] = array_merge($entry, $combination);
                }
            } else {
                $records[] = $entry;
            }
        }
        return $records;
    }
    
    /**
     * Replaces all placeholder strings (e.g. '?') with replacement strings.
     *
     * @param string $string        The string in which to replace the placeholder
     *                              strings.
     * @param array  &$replacements An array of replacement strings.
     * @param string $placeholder   The placeholder string.
     *
     * @return string The modified version of $string.
     */
    private static function _replacePlaceholders($string,
                                                 &$replacements,
                                                 $placeholder)
    {
        while (($pos = strpos($string, $placeholder)) !== false) {
            if (count($replacements) > 0) {
                $string = substr($string, 0, $pos) .
                          array_shift($replacements) .
                          substr($string, $pos+strlen($placeholder));
            } else {
                break;
            }
        }
        return $string;
    }
}

/**
 * Exception for LDAP errors
 *
 * @category XML
 * @package  XML_Query2XML
 * @author   Lukas Feiler <lukas.feiler@lukasfeiler.com>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL Version 2.1
 * @link     http://pear.php.net/package/XML_Query2XML
 */
class XML_Query2XML_LDAP2Exception extends XML_Query2XML_DriverException
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
?>