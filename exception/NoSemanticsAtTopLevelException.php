<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class NoSemanticsAtTopLevelException extends EchoException
{
	protected $messageText = 'The top level of the parse tree contains no semantics.';
}
