<?php

if (!defined('MYSQL_INCLUDE')) { require_once( INCLUDEPATH .'mysql.php' ); }

$options['hostname'] = 'localhost'; // Hostname
$options['username'] = ''; // Username
$options['password'] = ''; // Password
$options['dbname'] = ''; // Database name

$db = new mysql( $options );
$db->connect();
?>