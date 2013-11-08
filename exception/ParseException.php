<?php

namespace agentecho\exception;

class ParseException extends EchoException
{
	protected $messageText = 'Could not create a syntax tree for the part that starts with "%s"';
}