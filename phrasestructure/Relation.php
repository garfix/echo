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
	const PRESENT = 'present';
	const PAST = 'past';

	protected $data = array(
		'predicate' => null,
		'arg1' => null,
		'arg2' => null,
		'arg3' => null,
		'Preposition' => null,
		'tense' => self::PRESENT
	);

	public function setPredicate($predicate)
	{
		$this->data['predicate'] = $predicate;
	}

	public function getPredicate()
	{
		return $this->data['predicate'];
	}

	public function setArgument($index, $value)
	{
		$this->data['arg' . $index] = $value;
	}

	/**
	 * @return Entity
	 */
	public function getArgument1()
	{
		return $this->data['arg1'];
	}

	/**
	 * @return Entity
	 */
	public function getArgument2()
	{
		return $this->data['arg2'];
	}

	/**
	 * @return Entity
	 */
	public function getArgument3()
	{
		return $this->data['arg3'];
	}

	public function setSubject(Entity $Subject)
	{
		$this->data['arg1'] = $Subject;
	}

	public function setObject(Entity $Object)
	{
		$this->data['arg2'] = $Object;
	}

	public function setIndirectObject(Entity $IndirectObject)
	{
		$this->data['arg3'] = $IndirectObject;
	}

	public function setPreposition(Preposition $Preposition)
	{
		$this->data['Preposition'] = $Preposition;
	}

    public function getPreposition()
    {
        return $this->data['Preposition'];
    }

	public function setTense($tense)
	{
		$this->data['tense'] = $tense;
	}

	public function getTense()
	{
		return $this->data['tense'];
	}
}