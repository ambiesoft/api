<?php
// define ( 'DEBUGGING', true );
define ( 'USESESSION', false );

require 'funcs.php';

// mb_internal_encoding("UTF-8");
header ( 'Content-type: text/json; charset=utf-8' );
function mydie($message, $errornum) {
	if (defined ( 'DEBUGGING' ) && DEBUGGING) {
		http_response_code ( 500 + 50 );
		$message .= ' DEBUGGING';
	} else {
		http_response_code ( 500 + 50 + $errornum );
	}
	die ( $message );
}

if ($_SERVER ['REQUEST_METHOD'] !== 'POST' && $_SERVER ['REQUEST_METHOD'] !== 'GET') {
	// return OK
	exit ( 0 );
}

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
		mydie ( 'Illegal json', 1 );
	}
}

$method = @$_GET ['method'];
$id_token = @$_POST ['token'];

// if ($method != 'tango' && $method != 'levels' && $method != 'search' && ) {
if (! in_array ( $method, [ 
		'tango',
		'lessons',
		'search',
		'setresult',
		'quiz',
		'quizscore' 
] )) {
	mydie ( "Illegal method:$method", 2 );
}

if (! file_exists ( dirname ( __FILE__ ) . '/config.php' )) {
	mydie ( "'config.php' does not exit. Copy 'config.php.samele' to it and edit.", 3 );
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
	mydie ( "Failed to connect to database", 4 );
}

mysqli_set_charset ( $link, "utf8" );

// Initialize array variable
$dbdata = array ();
function getParamLevel() {
	$level = ( int ) @$_GET ['level'];
	if ((0 < $level && $level <= MAX_LEVEL) || $level == - 1)
		return $level;
	
	$level = ( int ) @$_POST ['level'];
	if ((0 < $level && $level <= MAX_LEVEL) || $level == - 1)
		return $level;
	
	mydie ( "Illegal level:$level", 5 );
}
function getParamLesson() {
	$lesson = ( int ) @$_GET ['lesson'];
	if ((0 < $lesson && $lesson <= MAX_LESSON) || $lesson == - 1)
		return $lesson;
	
	$lesson = ( int ) @$_POST ['lesson'];
	if ((0 < $lesson && $lesson <= MAX_LESSON) || $lesson == - 1)
		return $lesson;
	
	mydie ( "Illegal lesson:$lesson", 6 );
}
function getParam($param) {
	if (isset ( $_GET [$param] )) {
		return $_GET [$param];
	}
	return @$_POST [$param];
}
function getGoogleUserID($id_token) {
	if (USESESSION) {
		if (! session_start ()) {
			mydie ( 'failed to start session' );
		}
		$userid = @$_SESSION ['userid'];
	} else {
		$userid = '';
	}
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
					mydie ( 'Invalide UserID', 7 );
				}
			} catch ( Exception $e ) {
				mydie ( $e, 8 );
			}
			
			// save session in cookie
			if (USESESSION) {
				$_SESSION ['userid'] = $userid;
			}
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
function getDBCurrentTime() {
	return gmdate ( 'Y-m-d H:i:s' );
}
function GetParamKind() {
	$kindstring = @$_POST ['kind'];
	
	$kind = 0;
	if ($kindstring == 'normal') {
		$kind = 1;
	} else if ($kindstring == 'speed') {
		$kind = 2;
	} else if ($kindstring == 'confirm') {
		$kind = 3;
	} else if ($kindstring == 'test') {
		$kind = 4;
	} else {
		mydie ( 'Illegal Kind', 11 );
	}
	return $kind;
}

switch ($method) {
	
	case 'tango' :
		// sanity check
		$level = getParamLevel ();
		$lesson = getParamLesson ();
		
		$startI = GetStartID ( $level, $lesson );
		$sql = sprintf ( "SELECT word,meaning,gpron FROM `tango` WHERE %d <= id AND id < %d", // n
$startI, // n
$startI + WORDS_PER_LESSON ) // n
; // n
		   
		// Fetch 3 rows from actor table
		$result = $link->query ( $sql );
		if (! $result) {
			mydie ( 'db error', 9 );
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
				mydie ( 'db error', 10 );
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
		
		$kind = GetParamKind ();
		
		list ( $userid, $sessret ) = getGoogleUserID ( $id_token );
		if (! $userid) {
			mydie ( 'User id not found', 12 );
		}
		
		function getCurrentCountAndScore($dblink, $userid, $level, $lesson, $kind) {
			$sql = sprintf ( "SELECT count,scores FROM `guser` WHERE userid = '%s' AND level = '%d' AND lesson = '%d' AND kind = '%d' LIMIT 1", // no format return
mysqli_real_escape_string ( $dblink, $userid ), // userid
mysqli_real_escape_string ( $dblink, $level ), // level
mysqli_real_escape_string ( $dblink, $lesson ), // lesson
mysqli_real_escape_string ( $dblink, $kind ) ); // lesson
			                                                
			// end of sql
			
			$result = $dblink->query ( $sql );
			if (! $result) {
				mydie ( mysqli_error ( $dblink ), 13 );
			}
			
			// Get count
			$ret = mysqli_fetch_all ( $result );
			if (! $ret) {
				return [ 
						- 1,
						null 
				];
			}
			return [ 
					$ret [0] [0],
					$ret [0] [1] 
			];
		}
		
		list ( $currentCount, $currentScores ) = getCurrentCountAndScore ( $link, $userid, $level, $lesson, $kind );
		
		if ($currentCount < 0) {
			// first insert
			$currentCount = 0;
			$sql = sprintf ( "INSERT INTO `guser` (`userid`, `level`, `lesson`, `kind`, `count`, `lastupdate`) VALUES ('%s', '%d', '%d', '%d', '%d', '%s')", // no for
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ), // userid
mysqli_real_escape_string ( $link, 1 + $currentCount ), // count
mysqli_real_escape_string ( $link, getDBCurrentTime () ) ); // lastupdate
			                                                            // userid
			                                                            // end of sql
			
			$result = $link->query ( $sql );
			if (! $result) {
				mydie ( mysqli_error ( $link ), 14 );
			}
		} else {
			// Increment count
			$sql = sprintf ( "UPDATE guser SET `count` = '%d', `lastupdate` = '%s' WHERE userid='%s' AND level='%d' AND lesson='%d' AND kind='%d'", // no return
mysqli_real_escape_string ( $link, 1 + $currentCount ), // new count
mysqli_real_escape_string ( $link, getDBCurrentTime () ), // update
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ) ); // userid
			                                              // end of sql
			$result = $link->query ( $sql );
			if (! $result) {
				mydie ( mysqli_error ( $link ), 15 );
			}
		}
		
		if ($kind == 4) {
			// get score and check sanity
			$score = getParam ( 'score' );
			if (! is_int ( $score )) {
				mydie ( "Score must be int:$score", 16 );
			}
			if (! (0 <= $score && $score <= 100)) {
				mydie ( "Illegal score:$score", 17 );
			}
			
			// test, save score
			// convert DB score to array |$scoresToSave|
			if (! $currentScores) {
				$currentScores = '';
				$scoresToSave = [ ];
			} else {
				$scoresToSave = explode ( ':', $currentScores );
			}
			
			// add new score
			$scoresToSave [] = $score;
			
			// preserve last 3 scores
			while ( count ( $scoresToSave ) > 3 ) {
				array_shift ( $scoresToSave );
			}
			
			$scoresToSaveDB = implode ( ':', $scoresToSave );
			
			$sql = sprintf ( "UPDATE guser SET scores = '%s' WHERE userid='%s' AND level='%d' AND lesson='%d' AND kind='%d'", // no return
mysqli_real_escape_string ( $link, $scoresToSaveDB ), // new count
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ) ); // userid
			$result = $link->query ( $sql );
			if (! $result) {
				mydie ( mysqli_error ( $link ), 18 );
			}
		}
		
		// Get the current value again from the DB
		list ( $newCurrentCount, $newScore ) = getCurrentCountAndScore ( $link, $userid, $level, $lesson, $kind );
		
		// Initialize array variable
		$dbdata ['level'] = $level;
		$dbdata ['lesson'] = $lesson;
		$dbdata ['kind'] = $kind;
		$dbdata ['newcount'] = $newCurrentCount;
		if ($kind == 4) {
			// quiz
			$dbdata ['newscore'] = $newScore;
		}
		$dbdata ['sessret'] = $sessret;
		break;
	case 'lessons' :
		$level = getParamLevel ();
		list ( $userid, $sessret ) = getGoogleUserID ( $id_token );
		if (! $userid) {
			mydie ( 'User id not found', 19 );
		}
		
		$sql = sprintf ( "SELECT *  FROM `guser` WHERE `userid` = '%s' AND `level` = %d", // no ret
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ) ); // $level
		                                               // endof sqlF
		$result = $link->query ( $sql );
		if (! $result) {
			mydie ( mysqli_error ( $link ), 20 );
		}
		
		// create empty ret
		// for($i=1 ; $i <= MAX_LESSON ; ++$i) {
		// $dbdata[$i] = array();
		// $dbdata[$i]['lesson']=$i;
		// }
		
		while ( $row = $result->fetch_assoc () ) {
			unset ( $row ['userid'] );
			
			// / $dbdata[$row['lesson']] = $row;
			$dbdata [] = $row;
		}
		
		break;
	
	case 'quiz' :
		function GetQuizSQLALL($link, $level) {
			$startI = GetStartID ( $level, 1 );
			$endI = GetStartID ( $level + 1, 1 ) - 1;
			
			return sprintf ( "SELECT * FROM `tango` WHERE %d <= id && id <= %d ORDER BY RAND() LIMIT 5", // no ret
mysqli_real_escape_string ( $link, $startI ), // startI
mysqli_real_escape_string ( $link, $endI ) ); // endI
		}
		function GetQuizSQLLesson($link, $level, $lesson) {
			$startI = GetStartID ( $level, $lesson );
			$endI = $startI + WORDS_PER_LESSON - 1;
			
			return sprintf ( "SELECT * FROM `tango` WHERE %d <= id && id <= %d ORDER BY RAND() LIMIT 20", // no ret
mysqli_real_escape_string ( $link, $startI ), // startI
mysqli_real_escape_string ( $link, $endI ) ); // endI
		}
		
		$level = getParamLevel ();
		$lesson = getParamLesson ();
		
		if ($level == - 1 && $lesson == - 1) {
			// take 5 words from each level
			for($i = 1; $i <= MAX_LEVEL; ++ $i) {
				$sql = GetQuizSQLALL ( $link, $i );
				
				$result = $link->query ( $sql );
				if (! $result)
					mydie ( mysqli_error ( $link ), 21 );
				
				while ( $row = $result->fetch_assoc () ) {
					$row ['level'] = GetLevelFromID ( $row ['id'] );
					$dbdata [] = $row;
				}
			}
		} else if($level > 0 && $lesson > 0) {
			$sql = GetQuizSQLLesson( $link, $level,$lesson );
			
			$result = $link->query ( $sql );
			if (! $result)
				mydie ( mysqli_error ( $link ), 21 );
			
			while ( $row = $result->fetch_assoc () ) {
				if(GetLevelFromID ( $row ['id'] ) != $level)
					mydie('Internal Error', 13);
				$row ['level'] = $level;
				$dbdata [] = $row;
			}
		} else {
			mydie('Illegal level and lesson in quz', 14);
		}
		
		// get wrong answers and fill
		foreach ( $dbdata as &$value ) {
			$id = $value ['id'];
			$sql = sprintf ( "SELECT * FROM `tango` WHERE id != %d ORDER BY RAND() LIMIT 4", // no ret
mysqli_real_escape_string ( $link, $id ) );
			
			$result = $link->query ( $sql );
			if (! $result)
				mydie ( mysqli_error ( $link ), 22 );
			
			$wrongs = [ ];
			while ( $row = $result->fetch_assoc () ) {
				$wrongs [] = $row ['meaning'];
			}
			$value ['wronganswers'] = $wrongs;
		}
		break;
	
	case 'quizscore' :
		$level = getParamLevel ();
		$lesson = getParamLesson ();
		$kind = GetParamKind ();
		list ( $userid, $sessret ) = getGoogleUserID ( $id_token );
		if (! $userid) {
			mydie ( 'User id not found', 12 );
		}
		$sql = sprintf ( "SELECT scores FROM `guser` WHERE userid='%s' AND level='%d' AND lesson='%d' AND kind='%d'", // nn
mysqli_real_escape_string ( $link, $userid ), // userid
mysqli_real_escape_string ( $link, $level ), // userid
mysqli_real_escape_string ( $link, $lesson ), // userid
mysqli_real_escape_string ( $link, $kind ) ); // userid
		
		$result = $link->query ( $sql );
		if (! $result)
			mydie ( mysqli_error ( $link ), 22 );
		
		$score = mysqli_fetch_all ( $result ) [0] [0];
		$dbdata ['level'] = $level;
		$dbdata ['lesson'] = $lesson;
		$dbdata ['kind'] = $kind;
		$dbdata ['score'] = $score;
		break;
}

if (defined ( 'DEBUGGING' ) && DEBUGGING)
	$dbdata ['DEBUGGING'] = ' DEBUGGING';

// Print array in JSON format
echo json_encode ( $dbdata );

?>
