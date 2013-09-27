<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class DataBaseMultipleResultsException extends EchoException
{
	protected $messageText = 'The database returned more than one result.';
}
