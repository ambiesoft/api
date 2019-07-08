<?php
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
function GetLevelFromID($id) {
	return ( int ) ($id / 1000) + 1;
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

define ( 'MAX_LEVEL', 7 );
define ( 'MAX_LESSON', 20 );
?>