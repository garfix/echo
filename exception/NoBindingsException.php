<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class NoBindingsException extends EchoException
{
	protected $messageText = 'The database returned no results';
}
