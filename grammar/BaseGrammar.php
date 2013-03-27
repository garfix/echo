<?php

namespace agentecho\grammar;

use \agentecho\exception\ProductionException;

/**
 * This class contains some basic grammar functions.
 */
abstract class BaseGrammar implements Grammar
{
	protected $parseRules = null;
	protected $generationRules = null;
	protected $lexicon = null;

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
	protected $matchIndex = null;

	protected $wordIndex = null;

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->parseRules = $this->getParseRules();
		$this->generationRules = $this->getGenerationRules();

		$this->indexLexiconFeatures();
		$this->indexLexiconWords();
	}

	protected abstract function getLexicon();

	public function wordExists($word)
	{
		return isset($this->wordIndex[$word]);
	}

	/**
	 * Returns true if the given syntactic category is a non-abstract part-of-speech.
	 *
	 * @param string $category
	 * @return bool
	 */
	public function isPartOfSpeech($category)
	{
		return in_array($category, array(
			'adjective',
			'adverb',
			'conjunction',
			'determiner',
			'noun',
			'pronoun',
			'numeral',
			'verb',
			'propernoun',
#			'whword', // WH-word that may not be followed by an NP (e.g. who)
			'whAdverb', // adverb with a wh-word function: when, where, how ( http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html )
			'whwordNP', // WH-word that may be followed by an NP (e.g. which, what)
			'aux',
			'auxBe', // am, are, is, ...
			'auxDo', // do, does, did, ...
			'auxPsv', // "was" made, "werd" gemaakt
			'preposition',
			'passivisationPreposition',
			'possessiveMarker', // 's (see http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html)
			'punctuationMark',
		));
	}

	/**
	 * Returns true if $word belongs to the $partOfSpeech category.
	 *
	 * @param string $word
	 * @param string $partOfSpeech
	 * @return bool
	 */
	public function isWordAPartOfSpeech($word, $partOfSpeech)
	{
		$result = false;

		if (isset($this->wordIndex[$word])) {

			if (isset($this->wordIndex[$word][$partOfSpeech])) {
				$result = true;
			}

		} else {

			// all words can be proper nouns
			if ($partOfSpeech == 'propernoun') {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Returns the features for a word.
	 * @return array
	 */
	public function getFeaturesForWord($word, $partOfSpeech)
	{
		if (isset($this->wordIndex[$word][$partOfSpeech])) {
			if (isset($this->wordIndex[$word][$partOfSpeech]['features'])) {
				return $this->wordIndex[$word][$partOfSpeech]['features'];
			} else {
				return array();
			}
		} else {

			// presume proper noun
			return array(
				'head' => array(
					'agreement' => array('number' => 'singular', 'person' => 1),
					'syntax' => array(
						'name' => $word,
					),
//					'semantics' => 'name(this.object, "' . $word . '")'
				)
			);
		}
	}

	public function getSemanticsForWord($word, $partOfSpeech)
	{
		if (isset($this->wordIndex[$word][$partOfSpeech]['semantics'])) {
			return $this->wordIndex[$word][$partOfSpeech]['semantics'];
		} elseif ($partOfSpeech == 'propernoun') {
			// presume proper noun
			return 'name(this.object, "' . $word . '")';
		} else {
			return null;
		}
	}

	/**
	 * @param $partOfSpeech
	 * @param array $features
	 * @return bool|int|string
	 * @throws \agentecho\exception\ProductionException
	 */
	public function getWordForFeatures($partOfSpeech, array $features)
	{
		$word = false;

		if ($partOfSpeech == 'propernoun') {
			if (isset($features['head']['syntax']['name'])) {
				$word = $features['head']['syntax']['name'];
			}
		} elseif ($partOfSpeech == 'determiner') {
			if (is_numeric($features['head']['syntax']['category'])) {
				$word = $features['head']['syntax']['category'];
			} else {
				$word = $this->getWord($partOfSpeech, $features);
			}
		} elseif ($partOfSpeech == 'numeral') {
			if (is_numeric($features['head']['syntax']['value'])) {
				$word = $features['head']['syntax']['value'];
			} else {
				$word = $this->getWord($partOfSpeech, $features);
			}
		} else {
			$word = $this->getWord($partOfSpeech, $features);
			if (!$word) {
r($features);
				$E = new ProductionException(ProductionException::TYPE_WORD_NOT_FOUND_FOR_PARTOFSPEECH);
				$E->setValue($partOfSpeech);
				throw $E;
			}
		}

		return $word;
	}

	private function getWord($partOfSpeech, $features)
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
			foreach ($this->wordIndex as $word => $partsOfSpeech) {
				if (isset($partsOfSpeech[$partOfSpeech])) {
					$result = $word;
					break;
				}
			}
		}

		return $result;
	}

	private function indexLexiconFeatures()
	{
		$this->matchIndex = array();

		foreach ($this->lexicon as $entry) {
			if (isset($entry['features'])) {
				$this->indexFeatures($entry['form'], $this->matchIndex, $entry['part-of-speech'], $entry['features'], '');
			}
		}
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

	private function indexLexiconWords()
	{
		$this->wordIndex = array();

		foreach ($this->lexicon as $entry) {
			$this->wordIndex[$entry['form']][$entry['part-of-speech']] = $entry;
		}
	}
}