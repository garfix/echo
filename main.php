#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/ChatbotEcho.php';

$Echo = ChatbotEcho::getInstance();

// tell Echo who it is that is currently speaking to him
// for now: this is a different person every time
$objectId = uniqid('user/', true);

$Echo->startConversation($objectId);

do {
	$input = trim(fgets(STDIN));
	$lcInput = strtolower($input);


	echo $Echo->interact($input) . "\n";

} while ($lcInput != 'quit' && $lcInput != 'exit');