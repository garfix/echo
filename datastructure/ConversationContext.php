<?php

namespace agentecho\datastructure;

use \agentecho\phrasestructure\Entity;

/**
 * This class keeps a list of all roles in the current conversation.
 * It helps to resolve subjects and objects.
 *
 * @author Patrick van Bergen
 */
class ConversationContext
{
	/** @var Entity $Subject */
	private $Subject = null;

	public function setSubject(Entity $Subject)
	{
		$this->Subject = $Subject;
	}

	public function getSubject()
	{
		return $this->Subject;
	}
}
