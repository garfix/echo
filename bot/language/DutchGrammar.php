<?php

require_once(__DIR__ . '/SimpleGrammar.php');

class DutchGrammar extends SimpleGrammar
{
	public function getLexicon()
	{
		return array(
			'ik' => 'pronoun',
			'ben' => 'verb',
			'een' => 'article', 'het' => 'article', 'de' => 'article',
			'die' => 'determiner',
			'boek' => 'verb',
			'vlucht' => 'noun'
		);
	}
}