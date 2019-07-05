<?php
require 'funcs.php';

header ( 'Content-type: text/json; charset=utf-8' );

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	die ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit." );
}
require_once 'config.php';
// Initialize variable for database credentials
$dbhost = 'mysqlserverhost';
$dbuser = DBUSER;
$dbpass = DBPASS;
$dbname = 'eitango';

$q = @$_GET ['q'];

// Initialize array variable
$dbdata = array ();

if ($q) {
	// Create database connection
	$dblink = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	
	// Check connection was successful
	if ($dblink->connect_errno) {
		die ( "Failed to connect to database" );
	}
	
	mysqli_set_charset ( $dblink, "utf8" );
	
	$sql = sprintf ( "SELECT id,word,meaning,gpron FROM `tango` WHERE word LIKE '%%%s%%' LIMIT 50",
			mysqli_real_escape_string($dblink, $q));
	
	// Fetch 3 rows from actor table
	$result = $dblink->query ( $sql );
	if (! $result) {
		die ( 'db error' );
	}

	// Fetch into associative array
	while ( $row = $result->fetch_assoc () ) {
		$row['level'] = GetLevelFromID($row['id']);
		$row['lesson'] = GetLessonFromID($row['id']);
		// unset($row['id']);
		$dbdata [] = $row;
	}
}
// Print array in JSON format
echo json_encode ( $dbdata );

?>
