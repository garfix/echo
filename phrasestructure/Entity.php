<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\Determiner;

class Entity
{
	private $category = null;
	private $Determiner = null;

	public function setCategory($category)
	{
		$this->category = $category;
	}

	public function setDeterminer(Determiner $Determiner)
	{
		$this->Determiner = $Determiner;
	}
}