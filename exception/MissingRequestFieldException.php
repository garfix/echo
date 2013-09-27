<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class MissingRequestFieldException extends EchoException
{
	protected $messageText = 'The semantics of the sentence misses the `S.request` field';
}
