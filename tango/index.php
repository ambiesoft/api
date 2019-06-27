<?php
function GetTableName($level,$tbnum)
{
	while(strlen($level) < 3) {
		$level = '0' . $level;
	}
	
	while(strlen($tbnum) < 3) {
		$tbnum= '0' . $tbnum;
	}
	
	return "tango_{$level}_{$tbnum}";
}

// mb_internal_encoding("UTF-8");
header('Content-type: text/json; charset=utf-8');
$level = (int)@$_GET['level'];
if($level <= 0 || $level > 20 ) {
	die("Illegal level:$level");
}
$tbnum = (int)@$_GET['tbnum'];
if($tbnum<= 0 || $tbnum> 20 ) {
	die("Illegal tbnum:$tbnum");
}


if (!file_exists( dirname(__FILE__) . '/config.php')) {
    die("'config.php' does not exit. Copy 'config.php.samele' to it and edit.");
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
	printf ( "Failed to connect to database" );
	exit ();
}

mysqli_set_charset($dblink,"utf8");

$sql = sprintf("SELECT * FROM `%s`", GetTableName($level,$tbnum));

// Fetch 3 rows from actor table
$result = $dblink->query ( $sql );

if(!$result) {
    die('no data');
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
