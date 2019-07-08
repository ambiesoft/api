<?php
require 'funcs.php';

// mb_internal_encoding("UTF-8");
header ( 'Content-type: text/json; charset=utf-8' );

/*
 * https://www.quora.com/How-do-I-post-form-data-to-a-PHP-script-using-Axios
 * Axios posts data in JSON format (Content-Type: application/json) Standard $_POST
 * array is not populated when this content type is used. So it will always be empty.
 * In order to get post parameters sent via a json request, you need to use
 */
if ($_SERVER ['REQUEST_METHOD'] === 'POST' && empty ( $_POST )) {
	
	// set true to return array
	$_POST = json_decode ( file_get_contents ( 'php://input' ), true );
	if ($_POST === null) {
		die ( 'Illegal json' );
	}
}

$method = @$_GET ['method'];
$id_token = @$_POST ['token'];

// if ($method != 'tango' && $method != 'levels' && $method != 'search' && ) {
if (! in_array ( $method, [ 
		'tango',
		'lessons',
		'search',
		'setresult' 
] )) {
	die ( "Illegal method:$method" );
}

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	die ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit." );
}
require_once 'config.php';

// Initialize variable for database credentials
$dbhost = DBHOST;
$dbuser = DBUSER;
$dbpass = DBPASS;
$dbname = DBNAME;

// Create database connection
$link = new mysqli ( $dbhost, $dbuser, $dbpass, $dbname );

// Check connection was successful
if ($link->connect_errno) {
	die ( "Failed to connect to database" );
}

mysqli_set_charset ( $link, "utf8" );

// Initialize array variable
$dbdata = array ();
function getParamLevel() {
	$level = ( int ) @$_GET ['level'];
	if (0 < $level && $level <= MAX_LEVEL)
		return $level;
	
	$level = ( int ) @$_POST ['level'];
	if (0 < $level && $level <= MAX_LEVEL)
		return $level;
	
	die ( "Illegal level:$level" );
}
function getParamLesson() {
	$lesson = ( int ) @$_GET ['lesson'];
	if (0 < $lesson && $lesson <= MAX_LESSON)
		return $lesson;
	
	$lesson = ( int ) @$_POST ['lesson'];
	if (0 < $lesson && $lesson <= MAX_LESSON)
		return $lesson;
	
	die ( "Illegal lesson:$lesson" );
}
function getGoogleUserID($id_token) {
	if (! session_start ()) {
		die ( 'failed to start session' );
	}
	
	$userid = @$_SESSION ['userid'];
	$sessret = '';
	if (defined ( 'DEBUGGING' ) && DEBUGGING) { // debugging
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
	return array (
			$userid,
			$sessret 
	);
}

switch ($method) {
	
	case 'tango' :
		// sanity check
		$level = getParamLevel ();
		$lesson = getParamLesson ();
		
		$startI = GetStartID ( $level, $lesson );
		$sql = sprintf ( "SELECT word,meaning,gpron FROM `tango` WHERE %d <= id AND id < %d", $startI, $startI + 50 );
		
		// Fetch 3 rows from actor table
		$result = $link->query ( $sql );
		if (! $result) {
			die ( 'db error' );
		}
		
		// Fetch into associative array
		while ( $row = $result->fetch_assoc () ) {
			$dbdata [] = $row;
		}
		break;
	
	case 'search' :
		$q = @$_GET ['q'];
		
		if ($q) {
			$sql = sprintf ( "SELECT id,word,meaning,gpron FROM `tango` WHERE word LIKE '%%%s%%' LIMIT 50", mysqli_real_escape_string ( $link, $q ) );
			
			// Fetch 3 rows from actor table
			$result = $link->query ( $sql );
			if (! $result) {
				die ( 'db error' );
			}
			
			// Fetch into associative array
			while ( $row = $result->fetch_assoc () ) {
				$row ['level'] = GetLevelFromID ( $row ['id'] );
				$row ['lesson'] = GetLessonFromID ( $row ['id'] );
				// unset($row['id']);
				$dbdata [] = $row;
			}
		}
		break;
	
	case 'setresult' :
		
		$level = getParamLevel ();
		$lesson = getParamLesson ();
		$kindstring = @$_POST ['kind'];
		$id_token = @$_POST ['token'];
		
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
		list ( $userid, $sessret ) = getGoogleUserID ($id_token);
		if (! $userid) {
			die ( 'User id not found' );
		}
		
		$currentCount = - 1;
		
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
		
		$currentCount = getCurrentCount ( $link, $userid, $level, $lesson, $kind );
		
		if ($currentCount < 0) {
			// first insert
			$currentCount = 0;
			$sql = sprintf ( "INSERT INTO `guser` (`userid`, `level`, `lesson`, `kind`, `count`) VALUES ('%s', '%d', '%d', '%d', '%d')", // no for
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ), // userid
mysqli_real_escape_string ( $link, 1 + $currentCount ) ); // userid
			                                                          // end of sql
			
			$result = $link->query ( $sql );
			if (! $result) {
				die ( mysqli_error ( $link ) );
			}
		} else {
			// Increment count
			$sql = sprintf ( "UPDATE guser SET `count` = '%d' WHERE userid='%s' AND level='%d' AND lesson='%d' AND kind='%d'", // no return
mysqli_real_escape_string ( $link, 1 + $currentCount ), // new count
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ) ); // userid
			                                              // end of sql
			$result = $link->query ( $sql );
			if (! $result) {
				die ( mysqli_error ( $link ) );
			}
		}
		
		// Get the current value again from the DB
		$newCurrentCount = getCurrentCount ( $link, $userid, $level, $lesson, $kind );
		
		// Initialize array variable
		$dbdata ['level'] = $level;
		$dbdata ['lesson'] = $lesson;
		$dbdata ['kind'] = $kind;
		$dbdata ['newcount'] = $newCurrentCount;
		
		break;
	case 'lessons' :
		$level = getParamLevel ();
		list ( $userid, $sessret ) = getGoogleUserID ($id_token);
		if (! $userid) {
			die ( 'User id not found' );
		}
		
		$sql = sprintf ( "SELECT *  FROM `guser` WHERE `userid` = '%s' AND `level` = %d", // no ret
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ) ) // $level
; // endof sqlF
		$result = $link->query ( $sql );
		if (! $result) {
			die ( mysqli_error ( $link ) );
		}
		
		// create empty ret
// 		for($i=1 ; $i <= MAX_LESSON ; ++$i) {
// 			$dbdata[$i] = array();
// 			$dbdata[$i]['lesson']=$i;
// 		}
		
		while ($row = $result->fetch_assoc()) {
			unset($row['userid']);
			
			/// $dbdata[$row['lesson']] = $row;
			$dbdata[] = $row;
		}
		
		break;
}

// Print array in JSON format
echo json_encode ( $dbdata );

?>
