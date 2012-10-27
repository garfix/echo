<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Preposition;

/**
 * A clause forms the major structure of the sentence.
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
class Clause extends PhraseStructure
{
	const PRESENT = 'present';
	const PAST = 'past';

	protected $data = array(
		'predicate' => null,
		// Deep subject: the subject of the active form of the sentence
		'DeepSubject' => null,
		'DeepDirectObject' => null,
		'DeepIndirectObject' => null,
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
	public function setDeepDirectObject($arg)
	{
		$this->data['DeepDirectObject'] = $arg;
	}
	public function setDeepIndirectObject($arg)
	{
		$this->data['DeepIndirectObject'] = $arg;
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
	public function getDeepDirectObject()
	{
		return $this->data['DeepDirectObject'];
	}

	/**
	 * @return Entity
	 */
	public function getDeepIndirectObject()
	{
		return $this->data['DeepIndirectObject'];
	}

	public function setSubject(Entity $Subject)
	{
		$this->data['DeepSubject'] = $Subject;
	}

	public function setObject(Entity $Object)
	{
		$this->data['DeepDirectObject'] = $Object;
	}

	public function setIndirectObject(Entity $IndirectObject)
	{
		$this->data['DeepIndirectObject'] = $IndirectObject;
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