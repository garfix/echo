<?php

namespace agentecho\component;

/**
 * @author Patrick van Bergen
 */
class LogEvent extends Event
{
	private $params;

	public function __construct($params)
	{
		$this->params = $params;
	}

	public function getParams()
	{
		return $this->params;
	}
}
