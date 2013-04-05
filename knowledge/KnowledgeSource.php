<?php

namespace agentecho\knowledge;

use agentecho\datastructure\PredicationList;

abstract class KnowledgeSource
{
	/**
	 * @param PredicationList $Question
	 * @return array An array of result sets (bindings).
	 */
	public abstract function answer(PredicationList $Question);
}