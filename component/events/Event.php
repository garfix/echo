<?php

namespace agentecho\component\events;

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

}
