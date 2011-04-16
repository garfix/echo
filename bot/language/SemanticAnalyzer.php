<?php

class SemanticAnalyzer
{
	/**
	 * Turns a syntax tree into a set of semantic representations.
	 *
	 * @param array $syntaxTree
	 * @param array $context A set of statements describing current situation
	 * @return array
	 */
	public function analyze(array $syntaxTree, array $workingMemory)
	{
		$clauses = array();

		$this->analyzeBranch($syntaxTree, $clauses, $workingMemory);
//r($clauses);
		return $clauses;
	}

	protected function analyzeBranch(array $syntaxBranch, array &$clauses, array $workingMemory)
	{
		$meaning = '?';
//r($syntaxBranch);
		$partOfSpeech = $syntaxBranch['part-of-speech'];
		$constituents = isset($syntaxBranch['constituents']) ? $syntaxBranch['constituents'] : array();
		$word = isset($syntaxBranch['word']) ? $syntaxBranch['word'] : null;

		if ($constituents) {

			// meaning is composed of the meaning of the constituents
			$partialMeaning = array();
			foreach ($constituents as $constituent) {
				$partialMeaning[$constituent['part-of-speech']] = $this->analyzeBranch($constituent, $clauses, $workingMemory);
			}
//r($partialMeaning);
			//$meaning = $partialMeaning;

			if (
				isset($partialMeaning['NP']) &&
				isset($partialMeaning['VP']['verb']) &&
				isset($partialMeaning['VP']['NP']) &&
				($partialMeaning['VP']['verb'] == 'be')
			) {
				$clauses[] = array($partialMeaning['NP'], 'name', $partialMeaning['VP']['NP']);
			}

			if (
				isset($partialMeaning['Wh-NP']) &&
				isset($partialMeaning['VP']['verb']) &&
				isset($partialMeaning['VP']['NP']) &&
				($partialMeaning['Wh-NP'] == '?variable')
			) {
				$clauses[] = array($partialMeaning['VP']['NP'], 'name', $partialMeaning['Wh-NP']);
			}

			if (count($partialMeaning) == 1) {
				$meaning = reset($partialMeaning);
			} else {
				$meaning = $partialMeaning;
			}

		} else {

			// syntactic leaf: this is where the meaning starts
			if (
				($partOfSpeech == 'pronoun') &&
	# english specific
				($word == 'i')
			) {
				$meaning = $workingMemory['context']['speaker'];
			}

			if (
				($partOfSpeech == 'proper-noun')
			) {
				$meaning = $word;
			}

			if (
				($partOfSpeech == 'wh-word')
			) {
				$meaning = '?variable';
			}

			if (
				($partOfSpeech == 'verb') &&
	# english specific
				($word == 'am')
			) {
				$meaning = 'be';
			}

		}

		return $meaning;
	}
}