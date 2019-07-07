<?php
define ( 'DEBUGGING', false );

require 'funcs.php';

header ( 'Content-type: text/json; charset=utf-8' );
if (! session_start ()) {
	die ( 'failed to start session' );
}

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	die ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit." );
}
require_once 'config.php';
// Initialize variable for database credentials
$dbhost = DBHOST;
$dbuser = DBUSER;
$dbpass = DBPASS;
$dbname = 'eitango';

/*
 * https://www.quora.com/How-do-I-post-form-data-to-a-PHP-script-using-Axios
 * Axios posts data in JSON format (Content-Type: application/json) Standard $_POST
 * array is not populated when this content type is used. So it will always be empty.
 * In order to get post parameters sent via a json request, you need to use
 */
if ($_SERVER ['REQUEST_METHOD'] === 'POST' && empty ( $_POST )) {
	
	// set true to return array
	$_POST = json_decode ( file_get_contents ( 'php://input' ), true );
}

$level = ( int ) @$_POST ['level'];
$lesson = ( int ) @$_POST ['lesson'];
$kindstring = @$_POST ['kind'];
$id_token = @$_POST ['token'];

// sanity check
if (! (1 <= $level && $level <= MAX_LEVEL)) {
	die ( 'Illegal Level' );
}
if (! (1 <= $lesson && $lesson <= MAX_LESSON)) {
	die ( 'Illegal lesson' );
}
$kind = 0;
if ($kindstring == 'normal') {
	$kind = 1;
} else if ($kindstring == 'speed') {
	$kind = 2;
} else if ($kindstring == 'confirm') {
	$kind = 3;
} else {
	die ( 'Illegal Kind' );
}

// get userid from session cookie
$userid = @$_SESSION ['userid'];
$sessret = '';
if (DEBUGGING) { // debugging
	$userid = '0000000000000000001';
	$sessret = 'debbuging';
} else {
	if (! $userid) {
		// Could not have userid in session, get it from google
		$CLIENT_ID = "330872316416-lvi3ta181uma742srekov7nr7kcevfdc.apps.googleusercontent.com";
		try {
			$userid = verifyGoogleToken ( $CLIENT_ID, $id_token );
			if (! $userid) {
				die ( 'Invalide UserID' );
			}
		} catch ( Exception $e ) {
			die ( $e );
		}
		
		// save session in cookie
		$_SESSION ['userid'] = $userid;
		$sessret = 'authorized';
	} else {
		$sessret = 'hassession';
	}
}

$currentCount = - 1;

{
	// Create database connection
	$dblink = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );
	
	// Check connection was successful
	if ($dblink->connect_errno) {
		die ( "Failed to connect to database" );
	}
	
	mysqli_set_charset ( $dblink, "utf8" );
	function getCurrentCount($dblink, $userid, $level, $lesson, $kind) {
		$sql = sprintf ( "SELECT count FROM `guser` WHERE userid = '%s' AND level = '%d' AND lesson = '%d' LIMIT 1", // no format return
mysqli_real_escape_string ( $dblink, $userid ), // userid
mysqli_real_escape_string ( $dblink, $level ), // level
mysqli_real_escape_string ( $dblink, $lesson ) ); // lesson
		                                                  // end of sql
		
		$result = $dblink->query ( $sql );
		if (! $result) {
			die ( mysqli_error ( $dblink ) );
		}
		
		// Get count
		$ret = mysqli_fetch_all ( $result );
		if (! $ret) {
			return - 1;
		}
		return $ret [0] [0];
	}
	
	$currentCount = getCurrentCount ( $dblink, $userid, $level, $lesson, $kind );
	
	if ($currentCount < 0) {
		// first insert
		$currentCount = 0;
		$sql = sprintf ( "INSERT INTO `guser` (`userid`, `level`, `lesson`, `kind`, `count`) VALUES ('%s', '%d', '%d', '%d', '%d')", // no for
mysqli_real_escape_string ( $dblink, $userid ), // userid
mysqli_real_escape_string ( $dblink, $level ), // userid
mysqli_real_escape_string ( $dblink, $lesson ), // userid
mysqli_real_escape_string ( $dblink, $kind ), // userid
mysqli_real_escape_string ( $dblink, 1 + $currentCount ) ); // userid
		                                                            // end of sql
		
		$result = $dblink->query ( $sql );
		if (! $result) {
			die ( mysqli_error ( $dblink ) );
		}
	} else {
		// Increment count
		$sql = sprintf ( "UPDATE guser SET `count` = '%d' WHERE userid='%s' AND level='%d' AND lesson='%d' AND kind='%d'", // no return
mysqli_real_escape_string ( $dblink, 1 + $currentCount ), // new count
mysqli_real_escape_string ( $dblink, $userid ), // userid
mysqli_real_escape_string ( $dblink, $level ), // userid
mysqli_real_escape_string ( $dblink, $lesson ), // userid
mysqli_real_escape_string ( $dblink, $kind ) ); // userid
		                                                // end of sql
		$result = $dblink->query ( $sql );
		if (! $result) {
			die ( mysqli_error ( $dblink ) );
		}
	}
	
	// Get the current value again from the DB
	$newCurrentCount = getCurrentCount ( $dblink, $userid, $level, $lesson, $kind );
	
	// Initialize array variable
	$retarray = array ();
	$retarray ['level'] = $level;
	$retarray ['lesson'] = $lesson;
	$retarray ['kind'] = $kind;
	$retarray ['newcount'] = $newCurrentCount;
}

// Print array in JSON format
$retarray['sessret'] = $sessret;
echo json_encode ( $retarray );

?>
