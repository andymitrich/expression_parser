<?php

require_once "/var/www/errors.php";
require_once "/var/www/parser/Parser.php";

$expr = "model*withoutSpaces,justDigits,digitToLetter,разбитьНаСлова(),specialToSpace,zi,!exclude('empty'),!include('lengthWithoutSpace>',4)";
$parser = new Parser();
$parser->parse($expr);