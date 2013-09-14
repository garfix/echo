<?php

namespace agentecho\component;

/**
 * @author Patrick van Bergen
 */
class Event
{
	private $content;

	public function __construct($content)
	{
		$this->content = $content;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function getType()
	{
		return 'log';
	}
}
