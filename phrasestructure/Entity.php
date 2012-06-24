<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Determiner;
use \agentecho\exception\SentenceException;

class Entity extends PhraseStructure
{
	const SINGULAR = 'singular';
	const PLURAL = 'plural';

	protected $data = array(
		'category' => null,
		'name' => null,
		'Determiner' => null,
		'number' => self::SINGULAR
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

    public function getDeterminer()
    {
        return $this->data['Determiner'];
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
}