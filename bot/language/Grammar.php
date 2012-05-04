<?php

interface Grammar
{
	/**
	 * Returns the name of the language of this grammar.
	 * @return string
	 */
	public function getLanguage();

	/**
	 * This function turns a line of text into a Sentence.
	 *
	 * @param string $text Raw input.
	 * @param Sentence The sentence to create
	 * @return bool Succesful parse?
	 */
	public function parse($text, $Sentence);

	/**
	 * Returns a surface representation for a given sentence,
	 * @param Sentence $Sentence
	 * @return string
	 */
	public function generate(Sentence $Sentence);

	/**
	 * Returns all grammar rules with $antecedent as their antecedent.
	 * @return array
	 */
	public function getRulesForAntecedent($antecedent);

	/**
	 * Returns the first rule that have $antecedent and that match $features.
	 *
	 * Actually it returns an array of two components:
	 * 1) the 'rule' part of a generation rule
	 * 2) a unification of $DAG and the DAG created by the 'rule' part of the generation rule
	 *
	 * @param $antecedent
	 * @param LabeledDAG $DAG
	 * @return bool|array
	 */
	public function getRuleForDAG($antecedent, LabeledDAG $DAG);

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

	/**
	 * Returns a word that matches the given specification.
	 * @param $partOfSpeech
	 * @param $features
	 * @return string
	 */
	public function getWordForFeatures($partOfSpeech, array $features);
}
