<?php

header('Content-Type: text/html; charset=utf-8');

require_once "/var/www/errors.php";
require_once "/var/www/parser/src/Parser.php";

$parser = new Parser();

//$expr = "silicone*after('kill','me')";
//$result = $parser->parse($expr);
//echo "<PRE>"; print_r($result); echo "</PRE>";
//
//$expr = "[stop*destroy,after('kill','me')]";
//$result = $parser->parse($expr);
//echo "<PRE>"; print_r($result); echo "</PRE>";
//
//$expr = "[stop*destroy,after('kill','john,peter,dr\' Doolitle(jr)')]";
//$result = $parser->parse($expr);
//echo "<PRE>"; print_r($result); echo "</PRE>";
//
$expr = "offer*caps(5,2)";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

$expr = "model*withoutSpaces,justDigits,digitToLetter,разбитьНаСлова(),specialToSpace,zi,!exclude('empty'),!include('lengthWithoutSpace>',4)";
$result = $parser->parse($expr);
echo "<PRE>"; print_r($result); echo "</PRE>";

//$expr = "silicone*after('kill',before(f3(3)))";
//$result = $parser->parse($expr);
//echo "<PRE>"; print_r($result); echo "</PRE>";
//
//$expr = "silicone*!exclude('empty',include('full',f3('1',2),f2('1,2))";
//$result = $parser->parse($expr);
//echo "<PRE>"; print_r($result); echo "</PRE>";
