<?php

namespace agentecho\knowledge;

use agentecho\component\events\EventSender;
use agentecho\datastructure\RelationList;

abstract class KnowledgeSource
{
	use EventSender;

	/**
	 * @param RelationList $Question
	 * @return array An array of result sets (bindings).
	 */
	public abstract function answer(RelationList $Question);
}