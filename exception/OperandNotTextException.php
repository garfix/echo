<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class OperandNotTextException extends EchoException
{
	protected $messageText = 'Operand cannot be converted to text: %s';
}
