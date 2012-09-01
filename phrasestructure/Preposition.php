<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;

/**
 * The modifier corresponds to an prepositional phrase in a sentence.
 */
class Preposition extends PhraseStructure
{
	protected $data = array(
		// of, in, ...
		'category' => null,
		'Object' => null,
	);

	public function setCategory($category)
	{
		$this->data['category'] = $category;
	}

	public function getCategory()
	{
		return $this->data['category'];
	}

	/**
	 * @param Entity $Entity
	 */
	public function setObject(EntityStructure $Entity)
	{
		$this->data['Object'] = $Entity;
	}

	/**
	 * @return Entity
	 */
	public function getObject()
	{
		return $this->data['Object'];
	}
}