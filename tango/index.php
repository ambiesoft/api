<?php
require 'funcs.php';

// mb_internal_encoding("UTF-8");
header ( 'Content-type: text/json; charset=utf-8' );
$level = ( int ) @$_GET ['level'];
if ($level <= 0 || $level > 8) {
	die ( "Illegal level:$level" );
}
$lesson = ( int ) @$_GET ['lesson'];
if ($lesson <= 0 || $lesson > 20) {
	die ( "Illegal lesson:$lesson" );
}

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	die ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit." );
}
require_once 'config.php';
// Initialize variable for database credentials
$dbhost = 'mysqlserverhost';
$dbuser = DBUSER;
$dbpass = DBPASS;
$dbname = 'eitango';

// Create database connection
$dblink = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );

// Check connection was successful
if ($dblink->connect_errno) {
	die ( "Failed to connect to database" );
}

mysqli_set_charset ( $dblink, "utf8" );

$startI = GetStartID ( $level, $lesson );
$sql = sprintf ( "SELECT word,meaning,gpron FROM `tango` WHERE %d <= id AND id < %d", $startI, $startI + 50 );

// Fetch 3 rows from actor table
$result = $dblink->query ( $sql );
if (! $result) {
	die ( 'db error' );
}

// Initialize array variable
$dbdata = array ();

// Fetch into associative array
while ( $row = $result->fetch_assoc () ) {
	$dbdata [] = $row;
}

// Print array in JSON format
echo json_encode ( $dbdata );

?>
