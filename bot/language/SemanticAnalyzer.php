<?php

class SemanticAnalyzer
{
	/**
	 * Turns a syntax tree into a set of semantic representations.
	 * For each of the language specific parts, the grammar is used.
	 *
	 * @param array $syntaxTree
	 * @param array $context A set of statements describing current situation
	 * @return array A phrase structure
	 */
	public function analyze(Grammar $Grammar, array $syntaxTree, array $workingMemory)
	{
		$phraseStructure = $this->analyzeBranch($Grammar, $syntaxTree, $workingMemory);

		return $phraseStructure;
	}

	protected function analyzeBranch(Grammar $Grammar, array $syntaxBranch, array $workingMemory)
	{
		$constituents = isset($syntaxBranch['constituents']) ? $syntaxBranch['constituents'] : array();
		$word = isset($syntaxBranch['word']) ? $syntaxBranch['word'] : null;
		$partOfSpeech = isset($syntaxBranch['part-of-speech']) ? $syntaxBranch['part-of-speech'] : null;

		$constituentPartsOfSpeech = array();
		$constituentStructures = array();
		foreach ($constituents as $constituent) {
			$constituentStructures[] = $this->analyzeBranch($Grammar, $constituent, $workingMemory);
			$constituentPartsOfSpeech[] = $constituent['part-of-speech'];
		}

		// meaning association is language dependent, so it is left to the grammar
		if ($word) {
			$phraseStructure = $Grammar->analyzeWord($partOfSpeech, $word);
		} else {
			$phraseStructure = $Grammar->analyzeBranch($partOfSpeech, $constituentPartsOfSpeech, $constituentStructures);
		}

		return $phraseStructure;
	}
}