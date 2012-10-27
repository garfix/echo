<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Clause;
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
#todo: draai deze om:
		'Clause' => null,
		'voice' => self::ACTIVE,
		// i.e. "when she died"
		'RelativeClause' => null
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

	public function setClause(Clause $Clause)
	{
		$this->data['Clause'] = $Clause;
	}

	/**
	 * @return Clause
	 */
	public function getClause()
	{
		return $this->data['Clause'];
	}

	public function setVoice($voice)
	{
		$this->data['voice'] = $voice;
	}

	public function getVoice()
	{
		return $this->data['voice'];
	}

	public function setRelativeClause(RelativeClause $Clause)
	{
		$this->data['RelativeClause'] = $Clause;
	}

    public function getRelativeClause()
    {
        return $this->data['RelativeClause'];
    }
}