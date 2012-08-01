<?php

if (!defined('MYSQL_INCLUDE')) { require_once( INCLUDEPATH .'mysql.php' ); }

$options['hostname'] = 'localhost'; // Hostname
$options['username'] = 'nzbed'; // Username
$options['password'] = 'erika98'; // Password
$options['dbname'] = 'nzbed'; // Database name

$db = new mysql( $options );
$db->connect();
?>