<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;

/**
 * A relation forms the major structure of the sentence.
 * It forms a set of entities, labeled by a predicate.
 *
 * For example:
 *
 * John gives Mary flowers in the afternoon.
 * - set of entities: John, Mary, flowers
 * - predicate: give
 *
 * Peter is nice to me.
 * - set of entities: Peter, nice
 * - predicate: be
 *
 */
class Relation implements PhraseStructure
{
	private $predicate = null;
	private $arguments = array();

	public function __constuct($predicate = null)
	{
		$this->predicate = $predicate;
	}

	public function setPredicate($predicate)
	{
		$this->predicate = $predicate;
	}

	public function getPredicate()
	{
		return $this->predicate;
	}

	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function setSubject(Entity $Subject)
	{
		$this->arguments[1] = $Subject;
	}

	public function setObject(Entity $Object)
	{
		$this->arguments[2] = $Object;
	}

	public function setIndirectObject(Entity $IndirectObject)
	{
		$this->arguments[3] = $IndirectObject;
	}
}