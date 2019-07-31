<?php
define ( 'MAX_LEVEL', 8 );
define ( 'MAX_LEVEL_LESSON', 5 );
define ( 'MAX_LESSON', 20 );
define ( 'WORDS_PER_LESSON', 50 );

function maxLesson($level) {
	if( 1 <= $level && $level <= 7){
		return MAX_LESSON;
	}
	if($level==MAX_LEVEL){
		return MAX_LEVEL_LESSON;
	}
	return 0;
}
// $id is 1 to last
// each lessons contain 50 word
// each level contain 20 lessons(=1000 words)

// $id $level $lesson
// 1 1 1
// 2 1 1
// 50 1 1
// 51 1 2
// 951 1 20
function GetStartID($level, $lesson) {
	return ($level - 1) * 1000 + (($lesson - 1) * 50) + 1;
}
function  GetEndID($level, $lesson) {
	if($lesson >= MAX_LESSON) {
		++$level;
		$lesson = 1;
	} else {
		++$lesson;
	}
	return GetStartID($level, $lesson)-1;
}
function GetLevelFromID($id) {
	return ( int ) (($id-1) / 1000) + 1;
}
function GetLessonFromID($id) {
	return ( int ) ((($id - 1) % 1000) / 50) + 1;
}
// To install google-api run the following
// enable 'extension=openssl' in php.ini
//
// php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
// php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
// php composer-setup.php
// php -r "unlink('composer-setup.php');"

// php composer.phar install
// OR (if first time)
// php composer.phar require google/apiclient:"^2.0"

// validate token and get userid
function verifyGoogleToken($CLIENT_ID, $id_token) {
	require_once 'vendor/autoload.php';
	$client = new Google_Client ( [ 
			'client_id' => $CLIENT_ID 
	] ); // Specify the CLIENT_ID of the app that accesses the backend
	$payload = $client->verifyIdToken ( $id_token );
	if ($payload) {
		$userid = $payload ['sub'];
		return $userid;
	} else {
		// Invalid ID token
		die ( 'Invalid token:' . $id_token );
	}
}

// https://gist.github.com/inxilpro/6320414F
if (! function_exists ( 'http_response_code' )) {
	function http_response_code($code = null) {
		static $defaultCode = 200;
		
		if (null != $code) {
			switch ($code) {
				case 100 :
					$text = 'Continue';
					break; // RFC2616
				case 101 :
					$text = 'Switching Protocols';
					break; // RFC2616
				case 102 :
					$text = 'Processing';
					break; // RFC2518
				
				case 200 :
					$text = 'OK';
					break; // RFC2616
				case 201 :
					$text = 'Created';
					break; // RFC2616
				case 202 :
					$text = 'Accepted';
					break; // RFC2616
				case 203 :
					$text = 'Non-Authoritative Information';
					break; // RFC2616
				case 204 :
					$text = 'No Content';
					break; // RFC2616
				case 205 :
					$text = 'Reset Content';
					break; // RFC2616
				case 206 :
					$text = 'Partial Content';
					break; // RFC2616
				case 207 :
					$text = 'Multi-Status';
					break; // RFC4918
				case 208 :
					$text = 'Already Reported';
					break; // RFC5842
				case 226 :
					$text = 'IM Used';
					break; // RFC3229
				
				case 300 :
					$text = 'Multiple Choices';
					break; // RFC2616
				case 301 :
					$text = 'Moved Permanently';
					break; // RFC2616
				case 302 :
					$text = 'Found';
					break; // RFC2616
				case 303 :
					$text = 'See Other';
					break; // RFC2616
				case 304 :
					$text = 'Not Modified';
					break; // RFC2616
				case 305 :
					$text = 'Use Proxy';
					break; // RFC2616
				case 306 :
					$text = 'Reserved';
					break; // RFC2616
				case 307 :
					$text = 'Temporary Redirect';
					break; // RFC2616
				case 308 :
					$text = 'Permanent Redirect';
					break; // RFC-reschke-http-status-308-07
				
				case 400 :
					$text = 'Bad Request';
					break; // RFC2616
				case 401 :
					$text = 'Unauthorized';
					break; // RFC2616
				case 402 :
					$text = 'Payment Required';
					break; // RFC2616
				case 403 :
					$text = 'Forbidden';
					break; // RFC2616
				case 404 :
					$text = 'Not Found';
					break; // RFC2616
				case 405 :
					$text = 'Method Not Allowed';
					break; // RFC2616
				case 406 :
					$text = 'Not Acceptable';
					break; // RFC2616
				case 407 :
					$text = 'Proxy Authentication Required';
					break; // RFC2616
				case 408 :
					$text = 'Request Timeout';
					break; // RFC2616
				case 409 :
					$text = 'Conflict';
					break; // RFC2616
				case 410 :
					$text = 'Gone';
					break; // RFC2616
				case 411 :
					$text = 'Length Required';
					break; // RFC2616
				case 412 :
					$text = 'Precondition Failed';
					break; // RFC2616
				case 413 :
					$text = 'Request Entity Too Large';
					break; // RFC2616
				case 414 :
					$text = 'Request-URI Too Long';
					break; // RFC2616
				case 415 :
					$text = 'Unsupported Media Type';
					break; // RFC2616
				case 416 :
					$text = 'Requested Range Not Satisfiable';
					break; // RFC2616
				case 417 :
					$text = 'Expectation Failed';
					break; // RFC2616
				case 422 :
					$text = 'Unprocessable Entity';
					break; // RFC4918
				case 423 :
					$text = 'Locked';
					break; // RFC4918
				case 424 :
					$text = 'Failed Dependency';
					break; // RFC4918
				case 426 :
					$text = 'Upgrade Required';
					break; // RFC2817
				case 428 :
					$text = 'Precondition Required';
					break; // RFC6585
				case 429 :
					$text = 'Too Many Requests';
					break; // RFC6585
				case 431 :
					$text = 'Request Header Fields Too Large';
					break; // RFC6585
				
				case 500 :
					$text = 'Internal Server Error';
					break; // RFC2616
				case 501 :
					$text = 'Not Implemented';
					break; // RFC2616
				case 502 :
					$text = 'Bad Gateway';
					break; // RFC2616
				case 503 :
					$text = 'Service Unavailable';
					break; // RFC2616
				case 504 :
					$text = 'Gateway Timeout';
					break; // RFC2616
				case 505 :
					$text = 'HTTP Version Not Supported';
					break; // RFC2616
				case 506 :
					$text = 'Variant Also Negotiates';
					break; // RFC2295
				case 507 :
					$text = 'Insufficient Storage';
					break; // RFC4918
				case 508 :
					$text = 'Loop Detected';
					break; // RFC5842
				case 510 :
					$text = 'Not Extended';
					break; // RFC2774
				case 511 :
					$text = 'Network Authentication Required';
					break; // RFC6585
				
				default :
					$code = 500;
					$text = 'Internal Server Error';
			}
			
			$defaultCode = $code;
			
			$protocol = (isset ( $_SERVER ['SERVER_PROTOCOL'] ) ? $_SERVER ['SERVER_PROTOCOL'] : 'HTTP/1.0');
			header ( $protocol . ' ' . $code . ' ' . $text );
		}
		
		return $defaultCode;
	}
}

?>
