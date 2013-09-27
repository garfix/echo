<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class DataMappingFailedException extends EchoException
{
	protected $messageText = 'These predications could not be mapped to relations: %s';
}
