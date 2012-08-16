<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Relation;
use \agentecho\exception\SentenceException;

/**
 * A syntactic-semantic construction that results from a parse process and
 * serves as input for a production process.
 */
class Sentence extends PhraseStructure
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

	const MODIFIER_YES = 'yes';

	const PASSIVE = 'passive';
	const ACTIVE = 'active';

	protected $data = array(
		'sentenceType' => self::DECLARATIVE,
		'Relation' => null,
		'voice' => self::ACTIVE,
		'modifier' => null,
	);

	/**
	 * Set this type's sentence. Use one of the class constants.
	 * @param string $type
	 */
	public function setSentenceType($type)
	{
		if (!in_array($type, array(self::DECLARATIVE, self::IMPERATIVE, self::INTERROGATIVE_WH, self::INTERROGATIVE_YES_NO, self::EXCLAMATORY))) {
			throw new SentenceException('Invalid type given');
		}

		$this->data['sentenceType'] = $type;
	}

	public function getSentenceType()
	{
		return $this->data['sentenceType'];
	}

	public function setRelation(Relation $Relation)
	{
		$this->data['Relation'] = $Relation;
	}

	/**
	 * @return Relation
	 */
	public function getRelation()
	{
		return $this->data['Relation'];
	}

	public function setVoice($voice)
	{
		$this->data['voice'] = $voice;
	}

	public function getVoice()
	{
		return $this->data['voice'];
	}
}