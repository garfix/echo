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
class Relation extends PhraseStructure
{
	protected $data = array(
		'predicate' => null,
		'arguments' => array(
			1 => null,
			2 => null,
			3 => null
		),
		'Preposition' => null
	);

	public function setPredicate($predicate)
	{
		$this->data['predicate'] = $predicate;
	}

	public function getPredicate()
	{
		return $this->data['predicate'];
	}

	public function setArguments(array $arguments)
	{
		$this->data['arguments'] = $arguments;
	}

	public function getArguments()
	{
		return $this->data['arguments'];
	}

	/**
	 * @return Entity
	 */
	public function getArgument1()
	{
		return $this->data['arguments'][1];
	}

	/**
	 * @return Entity
	 */
	public function getArgument2()
	{
		return $this->data['arguments'][2];
	}

	/**
	 * @return Entity
	 */
	public function getArgument3()
	{
		return $this->data['arguments'][3];
	}

	public function setSubject(Entity $Subject)
	{
		$this->data['arguments'][1] = $Subject;
	}

	public function setObject(Entity $Object)
	{
		$this->data['arguments'][2] = $Object;
	}

	public function setIndirectObject(Entity $IndirectObject)
	{
		$this->data['arguments'][3] = $IndirectObject;
	}

	public function setPreposition(Preposition $Preposition)
	{
		$this->data['Preposition'] = $Preposition;
	}

    public function getPreposition()
    {
        return $this->data['Preposition'];
    }
}