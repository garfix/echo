<?php

namespace agentecho\grammar;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar extends BaseGrammar
{
	public function __construct()
	{
		parent::__construct();

		$this->loadParseGrammar(__DIR__ . '/../resources/simple.parse.grammar');
		$this->loadGenerationGrammar(__DIR__ . '/../resources/simple.generation.grammar');
	}

	public function unglue($word)
	{
		return array($word);
	}
}