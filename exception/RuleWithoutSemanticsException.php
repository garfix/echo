<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class RuleWithoutSemanticsException extends EchoException
{
	protected $messageText = 'This rule has no semantics attached: %s';
}
