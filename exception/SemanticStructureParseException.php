<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class SemanticStructureParseException extends EchoException
{
	protected $messageText = 'Could not parse the part that starts with "%s"';
}
