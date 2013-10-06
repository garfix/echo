<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class WordNotFoundException extends EchoException
{
	protected $messageText = 'Could not find a word for the part-of-speech: %s with `%s`';
}
