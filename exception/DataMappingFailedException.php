<?php

namespace agentecho\exception;

/**
 * @author Patrick van Bergen
 */
class DataMappingFailedException extends EchoException
{
	const ERROR = 'These predications could not be mapped to relations: %s';

	private $missingPredications;

	public function __construct($missingPredications)
	{
		$this->missingPredications = $missingPredications;
		$this->message = self::ERROR;
	}

	public function __toString()
	{
		return sprintf($this->getMessage(), $this->missingPredications);
	}
}
