<?php
/**
 * Created by JetBrains PhpStorm.
 * User: patrick
 * Date: 9/13/13
 * Time: 10:34 PM
 * To change this template use File | Settings | File Templates.
 */

namespace agentecho\component;


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