<?php

namespace agentecho\grammar;

use agentecho\datastructure\GenerationRule;
use agentecho\datastructure\RelationList;

/**
 * A grammar should describe the rules of a language.
 * It contains the smallest set of callbacks required to implement the language-specific elements of a language.
 */
interface Grammar
{
	/**
	 * Returns the lowercase name of the language of this grammar.
	 * @return string
	 */
	public function getLanguage();

	/**
	 * Returns the iso-639-1 language code of the language.
	 * @return string
	 */
	public function getLanguageCode();

	/**
	 * Returns the semantics of a word.
	 * @param $word
	 * @param $partOfSpeech
	 * @return mixed
	 */
	public function	getSemanticsForWord($word, $partOfSpeech);


	/**
	 * Returns all parse rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[ParseRule]
	 */
	public function getParseRulesForAntecedent($antecedent);

	/**
	 * Returns all generation rules with a given  $antecedent
	 * @param $antecedent
	 * @return GenerationRule[]
	 */
	public function getGenerationRulesForAntecedent($antecedent);

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
	 * Returns a word, given its semantics.
	 *
	 * @param string $partOfSpeech
	 * @param RelationList $Semantics
	 * @return mixed An array of [word, partOfSpeech], or false;
	 */
	public function getWordForSemantics($partOfSpeech, RelationList $Semantics);

	/**
	 * Returns true if $word is a word in the lexicon.
	 * @param $word
	 * @return bool
	 */
	public function wordExists($word);

	/**
	 * Split up words that contain different parts-of-speech
	 * and should therefore be treated as separate lexical items
	 * @param $word
	 * @return array
	 */
	public function unglue($word);
}
