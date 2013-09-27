<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticParseException extends EchoException
{
	protected $messageText = 'Could not calculate semantics for the part that starts with "%s"';
}
