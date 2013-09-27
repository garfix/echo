<?php

namespace agentecho\exception;

class SentenceException extends EchoException
{
	protected $messageText = 'Error building phrase structure: %s';
}