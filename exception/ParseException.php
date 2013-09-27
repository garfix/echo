<?php

namespace agentecho\exception;

class ParseException extends EchoException
{
	protected $messageText = 'Could not parse the part that starts with "%s"';
}