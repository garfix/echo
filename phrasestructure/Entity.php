<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Determiner;

class Entity implements PhraseStructure
{
	const SINGULAR = 'singular';
	const PLURAL = 'plural';

	private $category = null;
	private $name = null;
	private $Determiner = null;
	private $number = self::SINGULAR;

	public function setCategory($category)
	{
		$this->category = $category;
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function setDeterminer(Determiner $Determiner)
	{
		$this->Determiner = $Determiner;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setNumber($number)
	{
		if (!in_array($number, array(self::SINGULAR, self::PLURAL))) {
			throw new SentenceException('Invalid number given');
		}

		$this->number = $number;
	}
}