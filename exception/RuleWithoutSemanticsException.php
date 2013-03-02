<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class RuleWithoutSemanticsException extends EchoException
{
	const SEMANTIC = 'This rule has no semantics attached: %s';

	private $rule = null;

	public function __construct($rule)
	{
		$this->rule = $rule;
		$this->message = self::SEMANTIC;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), $this->rule);
	}
}
