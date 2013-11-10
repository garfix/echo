<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class MissingSentenceRelationException extends EchoException
{
	protected $messageText = 'A sentence() relation is necessary to pinpoint the main clause';
}
