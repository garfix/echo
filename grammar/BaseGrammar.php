<?php

namespace agentecho\grammar;

use agentecho\component\parser\GenerationRulesParser;
use agentecho\component\parser\ParseRulesParser;
use agentecho\datastructure\ParseRules;
use agentecho\datastructure\GenerationRules;
use \agentecho\exception\ProductionException;

/**
 * This class contains some basic grammar functions.
 */
abstract class BaseGrammar implements Grammar
{
	/** @var ParseRules */
	protected $ParseRules = null;

	/** @var array An antecedent-based index of parse rules */
	protected $parseRuleIndex = array();

	/** @var GenerationRules */
	protected $GenerationRules = null;

	/** @var array An antecedent-based index of generation rules */
	protected $generationRuleIndex = array();

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

		$this->indexLexiconFeatures();
		$this->indexLexiconWords();
	}

	protected function loadParseGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new ParseRulesParser();
		$Rules = $Parser->parse($text);
		if ($this->ParseRules) {
			$this->ParseRules->append($Rules);
		} else {
			$this->ParseRules = $Rules;
		}

		$this->indexParseRules($Rules);
	}

	protected function loadGenerationGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new GenerationRulesParser();
		$Rules = $Parser->parse($text);
		if ($this->GenerationRules) {
			$this->GenerationRules->append($Rules);
		} else {
			$this->GenerationRules = $Rules;
		}

		$this->indexGenerationRules($Rules);
	}

	private function indexParseRules(ParseRules $ParseRules)
	{
		foreach ($ParseRules->getRules() as $ParseRule) {
			$Production = $ParseRule->getProduction();
			$antecedent = $Production->getAntecedentCategory();
			$this->parseRuleIndex[$antecedent][] = $ParseRule;
		}
	}

	private function indexGenerationRules(GenerationRules $GenerationRules)
	{
		foreach ($GenerationRules->getRules() as $GenerationRule) {
			$Production = $GenerationRule->getProduction();
			$antecedent = $Production->getAntecedentCategory();
			$this->generationRuleIndex[$antecedent][] = $GenerationRule;
		}
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
			'degreeAdverb',
			'determiner',
			'noun',
			'pronoun',
			'numeral',
			'verb',
			'propernoun',
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
			'insertion'
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

				// and start with a capital
				if (preg_match('/^[A-Z]/', $word)) {
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns all parse rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[ParseRule]
	 */
	public function getParseRulesForAntecedent($antecedent)
	{
		if (isset($this->parseRuleIndex[$antecedent])) {
			$rules = $this->parseRuleIndex[$antecedent];
		} else {
			$rules = array();
		}

		return $rules;
	}

	/**
	 * Returns all generation rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[GenerationRule]
	 */
	public function getGenerationRulesForAntecedent($antecedent)
	{
		if (isset($this->generationRuleIndex[$antecedent])) {
			$rules = $this->generationRuleIndex[$antecedent];
		} else {
			$rules = array();
		}

		return $rules;
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
				)
			);
		}
	}

	# todo: replace this function with a semantic rule?
	public function getSemanticsForWord($word, $partOfSpeech)
	{
		if (isset($this->wordIndex[$word][$partOfSpeech]['semantics'])) {
			return $this->wordIndex[$word][$partOfSpeech]['semantics'];
		} elseif ($partOfSpeech == 'propernoun') {
			// presume proper noun
			return '';
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