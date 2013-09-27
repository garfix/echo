<?php

namespace agentecho\exception;

/**
 * Based class for all Echo's exceptions.
 */
class EchoException extends \Exception
{
	protected $messageText = 'Some exception has occurred: %s';

	/** @var  array */
	private $args;

	public function __construct($arg0 = null, $arg1 = null, $arg2 = null, $arg3 = null)
	{
		// fill exception arguments (and pad with defaults)
		$this->args = array($arg0, $arg1, $arg2, $arg3);

		$this->buildMessage();
	}

	/**
	 * Replaces this exception's message (for translation to the active language).
	 * @param $message
	 */
	public function setMessageText($messageText)
	{
		$this->messageText = $messageText;
		$this->buildMessage();
	}

	public function getMessageText()
	{
		return $this->messageText;
	}

	public function buildMessage()
	{
		// create
		$this->message = sprintf($this->messageText, $this->args[0], $this->args[1], $this->args[2], $this->args[3]);
	}
}