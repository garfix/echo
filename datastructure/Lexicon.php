<?php

namespace agentecho\datastructure;

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

	/**
	 * Returns the first word in the lexicon that matches $features.
	 *
	 * @param $partOfSpeech
	 * @param array $features
	 * @return string|null
	 */
	public function getWordForFeatures($partOfSpeech, array $features)
	{
		$flattenedFeatures = $this->flattenFeatures($partOfSpeech, $features);
		$resultWords = array();
		$new = true;

		foreach ($flattenedFeatures as $flattenedFeature) {
			if (isset($this->matchIndex[$flattenedFeature])) {
				if ($new) {
					$resultWords = $this->matchIndex[$flattenedFeature];
				} else {
					$resultWords = array_intersect($resultWords, $this->matchIndex[$flattenedFeature]);
				}
			}
			$new = false;
		}

		if (!empty($resultWords)) {
			$firstWord = array_shift($resultWords);
			$result = $firstWord;
		} else {
			$result = false;
		}

		// no features => first word
		if ($result === false && empty($flattenedFeatures)) {
			// find the first part-of-speech
			foreach ($this->wordFormIndex as $word => $partsOfSpeech) {
				if (isset($partsOfSpeech[$partOfSpeech])) {
					$result = $word;
					break;
				}
			}
		}

		return $result;
	}

	private function flattenFeatures($partOfSpeech, array $features)
	{
		$flattened = array();

		$this->indexFeatures2($flattened, $partOfSpeech, $features, '');

		return $flattened;
	}

	private function indexFeatures2(array &$flattened, $partOfSpeech, $features, $path)
	{
		if (is_array($features)) {
			foreach($features as $name => $value) {
				$newPath = $path . '/' . $name;
				$this->indexFeatures2($flattened, $partOfSpeech, $value, $newPath);
			}
		} else {

			if ($features) {

				$path .= '=' . $features;
				$flattened[] = $partOfSpeech . ':' . $path;

			}
		}
	}

	public function __toString()
	{
		return implode("\n", $this->entries);
	}

	private function indexEntry(LexicalEntry $Entry)
	{
		// index word form
		$this->wordFormIndex[$Entry->getWordForm()][$Entry->getPartOfSpeech()] = $Entry;

		// index features
		$this->indexFeatures($Entry->getWordForm(), $this->matchIndex, $Entry->getPartOfSpeech(), $Entry->getFeatures()->getTree(), '');
	}

	private function indexFeatures($word, array &$matchIndex, $partOfSpeech, $features, $path)
	{
		if (is_array($features)) {
			foreach($features as $name => $value) {
				$newPath = $path . '/' . $name;
				$this->indexFeatures($word, $matchIndex, $partOfSpeech, $value, $newPath);
			}
		} else {

			if ($features) {

				$path .= '=' . $features;
				$matchIndex[$partOfSpeech . ':' . $path][] = $word;

			}
		}
	}
}
