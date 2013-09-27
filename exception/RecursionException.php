<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class RecursionException extends EchoException
{
	protected $messageText = 'Too much recursion when binding predications.';
}
