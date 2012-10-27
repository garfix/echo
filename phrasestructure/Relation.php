<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Preposition;

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
		// Deep subject: the subject of the active form of the sentence
		'DeepSubject' => null,
		'Arg2' => null,
		'Arg3' => null,
		'Preposition' => null,
		'tense' => self::PRESENT,
		'Adverb' => null,
	);

	public function setPredicate($predicate)
	{
		$this->data['predicate'] = $predicate;
	}

	public function getPredicate()
	{
		return $this->data['predicate'];
	}

	public function setDeepSubject($arg)
	{
		$this->data['DeepSubject'] = $arg;
	}
	public function setArg2($arg)
	{
		$this->data['Arg2'] = $arg;
	}
	public function setArg3($arg)
	{
		$this->data['Arg3'] = $arg;
	}

	/**
	 * @return Entity
	 */
	public function getDeepSubject()
	{
		return $this->data['DeepSubject'];
	}

	/**
	 * @return Entity
	 */
	public function getArgument2()
	{
		return $this->data['Arg2'];
	}

	/**
	 * @return Entity
	 */
	public function getArgument3()
	{
		return $this->data['Arg3'];
	}

	public function setSubject(Entity $Subject)
	{
		$this->data['DeepSubject'] = $Subject;
	}

	public function setObject(Entity $Object)
	{
		$this->data['Arg2'] = $Object;
	}

	public function setIndirectObject(Entity $IndirectObject)
	{
		$this->data['Arg3'] = $IndirectObject;
	}

	public function setPreposition(Preposition $Preposition)
	{
		$this->data['Preposition'] = $Preposition;
	}

	/**
	 * @return Preposition
	 */
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

	public function setAdverb(Adverb $Adverb)
	{
		$this->data['Adverb'] = $Adverb;
	}

	public function getAdverb()
	{
		return $this->data['Adverb'];
	}
}