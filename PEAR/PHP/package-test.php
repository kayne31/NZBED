<?php
/**
 * Ensure all components are correctly included.
 *
 * This checks the filesystem, and compares it to the components.php file, and the package2.xml
 */

// Helper function
function phpt2php($filename)
{
    return substr($filename, 0, -1);
}

// Get array of functions on filesystem
$filesystem = scandir('Compat/Function/');
unset($filesystem[array_search('.', $filesystem)]);
unset($filesystem[array_search('..', $filesystem)]);
unset($filesystem[array_search('CVS', $filesystem)]);
unset($filesystem[array_search('.DS_Store', $filesystem)]);
sort($filesystem);

// Get array of tests on filesystem
$tests = scandir('tests/function/');
unset($tests[array_search('.', $tests)]);
unset($tests[array_search('..', $tests)]);
unset($tests[array_search('CVS', $tests)]);
unset($tests[array_search('.DS_Store', $tests)]);
$tests_org = $tests;
$tests = array_map('phpt2php', $tests);
sort($tests);
	
// Find any incomplete tests
$incompleteTests = array();
foreach ($tests_org as $testfile) {
	$testfilename = 'tests/function/' . $testfile;
	if (file_get_contents($testfilename, null, null, 9, 12) === '[INCOMPLETE]') {
		$incompleteTests[] = $testfile;
	}
}

// Get a list of files from the package2.xml
$xml = simplexml_load_file('package2.xml');
$xml->registerXPathNamespace('pear', 'http://pear.php.net/dtd/package-2.0');
$xpath = '/pear:package/pear:contents/pear:dir[@name="/"]/pear:dir[@name="Compat"]/pear:dir[@name="Function"]/pear:file';
$filexml = array();
foreach ($xml->xpath($xpath) as $file) {
    $filexml[] = (string) $file['name'];
}
sort($filexml);
	
// Get a list of tests from the package2.xml
$xpath = '/pear:package/pear:contents/pear:dir[@name="/"]/pear:dir[@name="tests"]/pear:dir[@name="function"]/pear:file';
$testxml = array();
foreach ($xml->xpath($xpath) as $file) {
	$testxml[] = (string) $file['name'];
}
sort($testxml);

// Get list of files from Components.php
require 'Compat/Components.php';
$filecomps = array_keys($components['function']);
foreach ($filecomps as $k => $comp) { $filecomps[$k] = $comp . '.php'; }
sort($filecomps);

// Diff them
$error = false;
$res = array_diff($filesystem, $filexml);
if (!empty($res)) {
    echo "Exists in filesystem but not in XML:\n";
    echo '- ' . implode($res, ', ') . "\n";
    $error = true;
}

$res = $incompleteTests;
if (!empty($res)) {
	echo "Incomplete test:\n";
	echo '- ' . implode($res, ', ') . "\n";
	$error = true;
}
	
$res = array_diff($tests_org, $testxml);
if (!empty($res)) {
	echo "Exists in filesystem but not in tests XML:\n";
	echo '- ' . implode($res, ', ') . "\n";
	$error = true;
}

$res = array_diff($filesystem, $filecomps);
if (!empty($res)) {
   echo "Exists in filesystem but not in Components:\n";
   echo '- ' . implode($res, ', ') . "\n";
   $error = true;
}

$res = array_diff($filexml, $filesystem);
if (!empty($res)) {
    echo "Exists in XML but not in Filesytem:\n";
    echo '- ' . implode($res, ', ') . "\n";
    $error = true;
}

$res = array_diff($filesystem, $tests);
if (!empty($res)) {
    echo "Tests not found for the following files:\n";
    echo '- ' . implode($res, ', ') . "\n";
    $error = true;
}

if ($error === false) {
    echo "No errors found\n";
}

?>
