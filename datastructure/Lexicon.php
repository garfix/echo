<?php

namespace agentecho\datastructure;

use agentecho\component\Matcher;

/**
 * @author Patrick van Bergen
 */
class Lexicon
{
	/** @var LexicalEntry[] */
	private $entries = array();

	/** @var array  */
	private $wordFormIndex = array();

	/**
	 * An index to find all words that match a given feature.
	 * Part of an example index:
	 *
	 *     [/head/syntax/category=a] => Array
	 *        (
	 *            [0] => a/determiner
	 *            [1] => an/determiner
	 *        )
	 *
	 *    [/head/agreement/person=1] => Array
	 *        (
	 *            [0] => am/verb
	 *            [1] => are/verb
	 *            [2] => i/pronoun
	 *        )
	 *
	 */
	private $matchIndex = array();

	/** @var array Index semantics by predicates */
	private $predicateIndex = array();

	public function addEntry(LexicalEntry $Entry)
	{
		$this->entries[] = $Entry;

		$this->indexEntry($Entry);
	}

	/**
	 * Returns the lexical entry for $word as a $partOfSpeech
	 *
	 * @param $word
	 * @param $partOfSpeech
	 * @return LexicalEntry|null
	 */
	public function getEntry($word, $partOfSpeech)
	{
		return isset($this->wordFormIndex[$word][$partOfSpeech]) ? $this->wordFormIndex[$word][$partOfSpeech] : null;
	}

	public function getEntries()
	{
		return $this->entries;
	}

	/**
	 * Is $word a given $partOfSpeech?
	 * @param $word
	 * @param $partOfSpeech
	 * @return bool
	 */
	public function isWordAPartOfSpeech($word, $partOfSpeech)
	{
		return isset($this->wordFormIndex[$word][$partOfSpeech]);
	}

	/**
	 * Is $word part of this lexicon?
	 *
	 * @param $word
	 * @return bool
	 */
	public function wordExists($word)
	{
		return isset($this->wordFormIndex[$word]);
	}

	public function __toString()
	{
		return implode("\n", $this->entries);
	}

	private function indexEntry(LexicalEntry $Entry)
	{
		// index word form
		$this->wordFormIndex[$Entry->getWordForm()][$Entry->getPartOfSpeech()] = $Entry;

		// index semantics
		foreach ($Entry->getSemantics()->getPredications() as $Predication) {
			$predicate = $Predication->getPredicate();
			$this->predicateIndex[$Entry->getPartOfSpeech()][$predicate][(string)$Entry] = $Entry;
		}
	}

	/**
	 * Returns the first lexical entry [word, partOfSpeech] found that matches a series of $Semantics relations.
	 *
	 * @param string $partOfSpeech
	 * @param PredicationList $Semantics
	 * @return array|bool
	 */
	public function getWordForSemantics($partOfSpeech, PredicationList $Semantics)
	{
		$predications = $Semantics->getPredications();

		if (empty($predications)) {

			// no semantic constraints: find the first entry with the part-of-speech
			foreach ($this->entries as $Entry) {
				if ($Entry->getPartOfSpeech() == $partOfSpeech) {
					return [$Entry->getWordForm(), $Entry->getPartOfSpeech()];
				}
			}

		} else {

			// for each of the $Semantics predications, find the lexical entries that have it
			/** @var LexicalEntry[] $entries */
			$entries = array();
			foreach ($predications as $Predication) {

				$predicate = $Predication->getPredicate();

				if (isset($this->predicateIndex[$partOfSpeech][$predicate])) {
					if (empty($entries)) {
						$entries = $this->predicateIndex[$partOfSpeech][$predicate];
					} else {
						$entries = array_intersect_key($entries, $this->predicateIndex[$partOfSpeech][$predicate]);
					}
				} else {
					// no entries have this predication
					return false;
				}
			}

			// check each of $Semantics predications against the predications of the lexical entries
			foreach ($entries as $Entry) {
				$success = true;
				foreach ($Semantics->getPredications() as $Predication) {
					$propertyBindings = array();
					$variableBindings = array();
					if (!Matcher::matchPredicationAgainstList($Predication, $Entry->getSemantics(), $propertyBindings, $variableBindings)) {
						$success = false;
						break;
					}
				}
				if ($success) {
					return [$Entry->getWordForm(), $Entry->getPartOfSpeech()];
				}
			}

		}

		return false;
	}
}
