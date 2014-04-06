<?php

header('Content-Type: text/html; charset=utf-8');

ini_set("display_errors","1");
ini_set("display_startup_errors","1");
ini_set('error_reporting', E_ALL);

require_once "vendor/autoload.php";

$parser = new Parser();

// Example 1
$expr = "silicone*after('kill','me')";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

// Example 2
$expr = "[stop*destroy,after('kill','john,peter,dr\' Doolitle(jr)')]";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

// Example 3
$expr = "offer*caps(5,2)";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

// Example 4
$expr = "model*withoutSpaces,justDigits,digitToLetter,разбитьНаСлова(),specialToSpace,zi,!exclude('empty'),!include('lengthWithoutSpace>',4)";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

// Example 5
$expr = "silicone*!exclude('empty',include('full',f3('1',f4('empty',1)),f2('1,2')))";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";