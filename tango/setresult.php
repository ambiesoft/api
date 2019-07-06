<?php
require 'funcs.php';

header ( 'Content-type: text/json; charset=utf-8' );

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	die ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit." );
}
require_once 'config.php';
// Initialize variable for database credentials
$dbhost = DBHOST;
$dbuser = DBUSER;
$dbpass = DBPASS;
$dbname = 'eitango';

$level = (int)@$_GET ['level'];
$lesson = (int)@$_GET ['lesson'];
$kindstring = @$_GET ['kind'];
$id_token= @$_GET ['token'];

// sanity check
if(!(1 <= $level && $level <= MAX_LEVEL)) {
	die('Illegal Level');
}
if(!(1 <= $lesson&& $lesson<= MAX_LESSON)) {
	die('Illegal lesson');
}
$kind = 0;
if($kindstring=='normal') {
	$kind=1;
} else if($kindstring=='speed') {
	$kind=2;
} else if($kindstring=='confirm') {
	$kind=3;
} else {
	die('Illegal Kind');
}

// validate token and get userid
$userid = '';
$CLIENT_ID="330872316416-lvi3ta181uma742srekov7nr7kcevfdc.apps.googleusercontent.com";
require_once 'google-api-php-client/src/Google/autoload.php';
$client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
$payload = $client->verifyIdToken($id_token);
if ($payload) {
	$userid = $payload['sub'];
} else {
	// Invalid ID token
	die('Invalid token');
}

exit();

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
