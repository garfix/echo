<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class OperandNotAcceptedException extends EchoException
{
	protected $messageText = 'Operand not accepted: %s';
}
