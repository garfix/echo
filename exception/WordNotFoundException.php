<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class WordNotFoundException extends EchoException
{
	protected $messageText = 'Production exception: could not find a word for the part-of-speech: %s';
}
