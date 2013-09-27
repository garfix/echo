<?php

namespace agentecho\grammar;

use agentecho\component\parser\GenerationRulesParser;
use agentecho\component\parser\LexiconParser;
use agentecho\component\parser\ParseRulesParser;
use agentecho\datastructure\Constant;
use agentecho\datastructure\LabeledDAG;
use agentecho\datastructure\ParseRules;
use agentecho\datastructure\GenerationRules;
use agentecho\datastructure\Predication;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Property;
use agentecho\datastructure\Atom;
use agentecho\exception\ProductionException;
use agentecho\datastructure\Lexicon;
use agentecho\exception\WordNotFoundException;

/**
 * This class contains some basic grammar functions.
 */
abstract class BaseGrammar implements Grammar
{
	/** @var ParseRules */
	protected $ParseRules = null;

	/** @var GenerationRules */
	protected $GenerationRules = null;

	/** @var Lexicon $Lexicon */
	protected $Lexicon = null;

	public function __construct()
	{
		$this->Lexicon = new Lexicon();
		$this->ParseRules = new ParseRules();
		$this->GenerationRules = new GenerationRules();
	}

	protected function loadParseGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new ParseRulesParser();
		$Rules = $Parser->parse($text);
		foreach ($Rules->getRules() as $Rule) {
			$this->ParseRules->addRule($Rule);
		}
	}

	protected function loadGenerationGrammar($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new GenerationRulesParser();
		$Rules = $Parser->parse($text);
		foreach ($Rules->getRules() as $Rule) {
			$this->GenerationRules->addRule($Rule);
		}
	}

	protected function loadLexicon($filePath)
	{
		$text = file_get_contents($filePath);
		$Parser = new LexiconParser();
		$Lexicon = $Parser->parse($text);
		foreach ($Lexicon->getEntries() as $Entry) {
			$this->Lexicon->addEntry($Entry);
		}
	}

	public function wordExists($word)
	{
		return $this->Lexicon->wordExists($word);
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
			'copula',
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

		if ($this->Lexicon->isWordAPartOfSpeech($word, $partOfSpeech)) {

			$result = true;

		} elseif (is_numeric($word)) {

			if ($partOfSpeech == 'numeral') {

				return true;

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
		return $this->ParseRules->getRulesForAntecedent($antecedent);
	}

	/**
	 * Returns all generation rules with a given  $antecedent
	 * @param $antecedent
	 * @return array[GenerationRule]
	 */
	public function getGenerationRulesForAntecedent($antecedent)
	{
		return $this->GenerationRules->getRulesForAntecedent($antecedent);
	}

	/**
	 * Returns the features for a word, starting with $partOfSpeech as a new root.
	 * @return LabeledDAG
	 */
	public function getFeaturesForWord($word, $partOfSpeech)
	{
		$Entry = $this->Lexicon->getEntry($word, $partOfSpeech);
		if ($Entry) {
			return $Entry->getPrefixedFeatures();
		} else {
			return new LabeledDAG(array($partOfSpeech => array('head' => array('syntax' => array('name' => $word)))));
		}
	}

	/**
	 * Returns the semantics of a word.
	 * @param $word
	 * @param $partOfSpeech
	 * @return PredicationList|null
	 */
	public function getSemanticsForWord($word, $partOfSpeech)
	{
		$Entry = $this->Lexicon->getEntry($word, $partOfSpeech);
		if ($Entry) {
			return $Entry->getSemantics();
		} elseif ($partOfSpeech == 'propernoun') {
			return new PredicationList();
		} elseif ($partOfSpeech == 'numeral') {
			$List = new PredicationList();

			$Prop = new Property();
			$Prop->setName('object');
			$Prop->setObject(new Atom('this'));

			$Pred = new Predication();
			$Pred->setPredicate('determiner');
			$Pred->setArguments(array($Prop, new Constant($word)));

			$List->setPredications(array($Pred));
			return $List;
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
				$word = $this->Lexicon->getWordForFeatures($partOfSpeech, $features);
			}
		} elseif ($partOfSpeech == 'numeral') {
			if (is_numeric($features['head']['syntax']['value'])) {
				$word = $features['head']['syntax']['value'];
			} else {
				$word = $this->Lexicon->getWordForFeatures($partOfSpeech, $features);
			}
		} else {
			$word = $this->Lexicon->getWordForFeatures($partOfSpeech, $features);
			if (!$word) {
				throw new WordNotFoundException($partOfSpeech);
			}
		}

		return $word;
	}
}