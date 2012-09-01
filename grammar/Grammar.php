<?php

namespace agentecho\grammar;

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
	 * Returns all grammar rules for parsing.
	 * @return array
	 */
	public function getParseRules();

	/**
	 * Returns the lexicon.
	 * @return array
	 */
	public function getLexicon();

	/**
	 * Returns all grammar rules for generation.
	 * @return array
	 */
	public function getGenerationRules();

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
	 * Returns a word that matches the specified features.
	 * @param $partOfSpeech
	 * @param array $features
	 * @return string|false
	 */
	public function getWordForFeatures($partOfSpeech, array $features);

	/**
	 * Returns the features of a word (a tree).
	 * @return array
	 */
	public function getFeaturesForWord($word, $partOfSpeech);

	/**
	 * Returns true if $word is a word in the lexicon.
	 * @param $word
	 * @return bool
	 */
	public function wordExists($word);

	/**
	 * Returns true if $words is a proper noun according to the rules of the grammar.
	 * @param $string
	 * @return bool
	 */
	public function isProperNoun($words);

	/**
	 * Split up words that contain different parts-of-speech
	 * and should therefore be treated as separate lexical items
	 * @param $word
	 * @return array
	 */
	public function unglue($word);
}
