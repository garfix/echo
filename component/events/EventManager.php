<?php

namespace agentecho\component\events;

/**
 * @author Patrick van Bergen
 */
class EventManager
{
	private $listeners = [];

	public function addListener(callable $listener)
	{
		$this->listeners[] = $listener;
	}

	public function send(Event $Event)
	{
		/** @var callable $listener */
		foreach ($this->listeners as $listener) {
			$listener($Event);
		}
	}
}
