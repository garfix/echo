<?php

namespace agentecho\exception;

/**
 * Based class for all Echo's exceptions.
 */
class EchoException extends \Exception
{
	protected $messageText = null;

	/**
	 * Replaces this exception's message (for translation to the active language).
	 * @param $message
	 */
	public function setMessageText($messageText)
	{
		$this->messageText = $messageText;
	}

	public function getMessageText()
	{
		return $this->messageText;
	}

	public function buildMessage()
	{
		$this->message = $this->messageText;
	}
}