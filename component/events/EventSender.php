<?php

namespace agentecho\component\events;

trait EventSender
{

	/** @var EventManager */
	private $EventManager = null;

	public function setEventManager(EventManager $EventManager)
	{
		$this->EventManager = $EventManager;
	}

	public function send(Event $Event)
	{
		if ($this->EventManager) {
			$this->EventManager->send($Event);
		}
	}
}