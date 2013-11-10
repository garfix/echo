<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class NoRuleFoundForAntecedent extends EchoException
{
	protected $messageText = 'When generating a response, no production rule matched the antecedent %s';
}
