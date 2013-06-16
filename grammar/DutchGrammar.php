<?php

namespace agentecho\grammar;

class DutchGrammar extends SimpleGrammar
{
	public function __construct()
	{
		parent::__construct();
		$this->loadLexicon(__DIR__ . '/../resources/dutch.lexicon');
		$this->loadGenerationGrammar(__DIR__ . '/../resources/dutch.generation.grammar');
	}

	public function getLanguage()
	{
		return 'dutch';
	}

	public function getLanguageCode()
	{
		return 'nl';
	}

	public function unglue($word)
	{
		// $word ends with 's or '
		if (preg_match('/(^[^\']+)(\'s?)$/', $word, $matches)) {
			// split the 's part (and turn ' into 's)
			return array($matches[1], "'s");
		}

		// default: no action
		return array($word);
	}
}
