<?php

namespace agentecho\knowledge;

use agentecho\component\EventSender;
use agentecho\datastructure\PredicationList;

abstract class KnowledgeSource
{
	use EventSender;

	/**
	 * @param PredicationList $Question
	 * @return array An array of result sets (bindings).
	 */
	public abstract function answer(PredicationList $Question);
}