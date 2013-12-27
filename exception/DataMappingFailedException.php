<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class DataMappingFailedException extends EchoException
{
	protected $messageText = 'These relations could not be mapped: %s';
}
