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
?>