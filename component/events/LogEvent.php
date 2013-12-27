<?php

namespace agentecho\component\events;

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
