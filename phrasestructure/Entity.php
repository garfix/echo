<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Determiner;
use \agentecho\phrasestructure\Preposition;
use \agentecho\exception\SentenceException;

class Entity extends PhraseStructure implements EntityStructure
{
	const SINGULAR = 'singular';
	const PLURAL = 'plural';

	protected $data = array(
		'category' => null,
		'name' => null,
		'Determiner' => null,
		'Preposition' => null,
		'number' => self::SINGULAR,
		'question' => null
	);

	public function setCategory($category)
	{
		$this->data['category'] = $category;
	}

	public function getCategory()
	{
		return $this->data['category'];
	}

	public function setDeterminer(Determiner $Determiner)
	{
		$this->data['Determiner'] = $Determiner;
	}

	/**
	 * @return Determiner
	 */
	public function getDeterminer()
    {
        return $this->data['Determiner'];
    }

	public function setPreposition(Preposition $Preposition)
	{
		$this->data['Preposition'] = $Preposition;
	}

    public function getPreposition()
    {
        return $this->data['Preposition'];
    }

	public function setName($name)
	{
		$this->data['name'] = $name;
	}

	public function getName()
	{
		return $this->data['name'];
	}

	public function setNumber($number)
	{
		if (!in_array($number, array(self::SINGULAR, self::PLURAL))) {
			throw new SentenceException('Invalid number given');
		}

		$this->data['number'] = $number;
	}

	public function getNumber()
	{
		return $this->data['numbers'];
	}

	/**
	 * Mark this determiner as an unknown that needs to be resolved (a question).
	 */
	public function setQuestion()
	{
		$this->data['question'] = true;
	}

	public function isQuestion()
	{
	    return $this->data['question'];
	}
}