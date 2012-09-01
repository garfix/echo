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
	 *     [/head/sem/category=a] => Array
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

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->parseRules = $this->getParseRules();
		$this->generationRules = $this->getGenerationRules();

		$this->indexLexicon();
	}

	public function getLexicon()
	{
		return $this->lexicon;
	}

	public function wordExists($word)
	{
		return isset($this->lexicon[$word]);
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
			'whword', // WH-word that may not be followed by an NP (e.g. who)
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
	 * Returns the features for a word.
	 * @return array
	 */
	public function getFeaturesForWord($word, $partOfSpeech)
	{
		if (isset($this->lexicon[$word][$partOfSpeech])) {
			if (isset($this->lexicon[$word][$partOfSpeech]['features'])) {
				return $this->lexicon[$word][$partOfSpeech]['features'];
			} else {
				return array();
			}
		} else {
			// presume proper noun
			return array(
				'head' => array(
					'agreement' => array('number' => 'singular', 'person' => 1),
					'sem' => array('name' => $word)
				)
			);
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
			if (isset($features['head']['sem']['name'])) {
				$word = $features['head']['sem']['name'];
			}
		} elseif ($partOfSpeech == 'determiner') {
			if (is_numeric($features['head']['sem']['category'])) {
				$word = $features['head']['sem']['category'];
			} else {
				$word = $this->getWord($partOfSpeech, $features);
			}
		} elseif ($partOfSpeech == 'numeral') {
			if (is_numeric($features['head']['sem']['value'])) {
				$word = $features['head']['sem']['value'];
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
			foreach ($this->lexicon as $word => $partsOfSpeech) {
				if (isset($partsOfSpeech[$partOfSpeech])) {
					$result = $word;
					break;
				}
			}
		}

		return $result;
	}

	private function indexLexicon()
	{
		$this->matchIndex = array();

		foreach ($this->lexicon as $word => $partsOfSpeech) {
			foreach ($partsOfSpeech as $partOfSpeech => $data) {

				if (isset($data['features'])) {
					$this->indexFeatures($word, $this->matchIndex, $partOfSpeech, $data['features'], '');
				}
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
}