<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Relation;
use \agentecho\exception\SentenceException;

/**
 * A syntactic-semantic construction that results from a parse process and
 * serves as input for a production process.
 */
class Sentence implements PhraseStructure
{
	/**
	 * Types of sentences
	 * http://voices.yahoo.com/four-kinds-sentences-declarative-interrogative-484238.html
	 * http://en.wikipedia.org/wiki/Sentence_%28linguistics%29
	 *
	 * This are meant to express the intent of the speaker, and are likely to change.
	 */
	const DECLARATIVE = 'declarative';
	const IMPERATIVE = 'imperative';
	const INTERROGATIVE_WH = 'wh-question';
	const INTERROGATIVE_YES_NO = 'yes-no-question';
	const EXCLAMATORY = 'exclamatory';

	const PASSIVE = 'passive';
	const ACTIVE = 'active';

	/** @var Type of sentence */
	private $type = self::DECLARATIVE;

	/** @var Relation The relation this sentence is about */
	private $Relation = null;

	private $voice = self::ACTIVE;

	/**
	 * Set this type's sentence. Use one of the class constants.
	 * @param int $type
	 */
	public function setType($type)
	{
		if (!in_array($type, array(self::DECLARATIVE, self::IMPERATIVE, self::INTERROGATIVE_WH, self::INTERROGATIVE_YES_NO, self::EXCLAMATORY))) {
			throw new SentenceException('Invalid type given');
		}

		$this->type = $type;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setRelation(Relation $Relation)
	{
		$this->Relation = $Relation;
	}

	public function getRelation()
	{
		return $this->Relation;
	}

	public function setVoice($voice)
	{
		$this->voice = $voice;
	}

	public function getVoice()
	{
		return $this->voice;
	}
}