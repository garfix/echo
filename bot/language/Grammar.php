<?php

interface Grammar
{
	public function parse($text, $Sentence, $workingMemory);

	/**
	 * Returns all grammar rules with $constituent as their antecedent.
	 * @return array
	 */
	public function getRulesForConstituent($constituent);

	/**
	 * Returns true if $word belongs to the $partOfSpeech category (like 'verb', 'noun').
	 * @return bool
	 */
	public function isWordAPartOfSpeech($word, $partOfSpeech);

	/**
	 * Returns true if the given syntactic category is a non-abstract part-of-speech (like 'verb', 'noun') and false if it is abstract (like 'VP' or 'NP').
	 * @return bool
	 */
	public function isPartOfSpeech($constituent);

	/**
	 * Returns the features of a word (a tree).
	 * @return array
	 */
	public function getFeaturesForWord($word, $partOfSpeech);

	public function generate(Sentence $Sentence);
}

?>
