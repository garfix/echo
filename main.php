#!/usr/bin/php
<?php

require_once __DIR__ . '/bot/ChatbotEcho.php';

do {
	$input = trim(fgets(STDIN));
	$lcInput = strtolower($input);

	echo ChatbotEcho::getInstance()->processInput($input) . "\n";

} while ($lcInput != 'quit' && $lcInput != 'exit');