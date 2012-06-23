<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;

/**
 * A determiner
 */
class Determiner implements PhraseStructure
{
	private $category = null;

	public function setCategory($category)
	{
		$this->category = $category;
	}
}