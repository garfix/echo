<?php

namespace agentecho\grammar;

use \agentecho\component\Microplanner;
use \agentecho\component\Lexer;
use \agentecho\component\EarleyParser;
use \agentecho\datastructure\Sentence;
use \agentecho\datastructure\LabeledDAG;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar implements Grammar
{
	protected $parseRules = null;
	protected $generationRules = null;
	protected $lexicon = null;
	protected $Microplanner = null;
	protected $Lexer = null;

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->parseRules = $this->getParseRules();
		$this->generationRules = $this->getGenerationRules();

		// input processing
		$this->Lexer = new Lexer();

		// output processing
		$this->Microplanner = new Microplanner();
	}

	public function wordExists($word)
	{
		return isset($this->lexicon[$word]);
	}

	/**
	 * Returns true if $words for a proper noun.
	 * @param $words
	 * @return bool
	 */
	public function isProperNoun($words)
	{
		// Sjaak
		// Sjaak Zwart
		// Sjaak (de|van|van de|van der) Zwart
		$exp = '/^([A-Z][a-z]+)( (de |van |van de |van der )?[A-Z][a-z]+)?$/';

		return preg_match($exp, implode(' ', $words));
	}

	/**
	 * Analyses a raw $string and places the result in $Sentence.
	 */
	public function analyze($input, Sentence $Sentence)
	{
		return $this->Lexer->analyze($input, $Sentence, $this);
	}

	public function unglue($word)
	{
		return array($word);
	}

	/**
	 * This function turns structured meaning into a line of text.
	 *
	 * @param Sentence $Sentence A sentence that contains a speech act, and meaning.
	 * @return string|false Either a sentence in natural language, or false, in case of failure
	 */
	public function generate(Sentence $Sentence)
	{
		// turn the intention of the sentence into a syntactic structure
		$lexicalItems = $this->Microplanner->plan($Sentence->phraseSpecification, $this);
		if (!$lexicalItems) {
			return false;
		}

		$Sentence->lexicalItems = $lexicalItems;

# todo: split items into words
$words = $lexicalItems;

		$Sentence->words = $words;

		$Sentence->surfaceText = $this->createSurfaceText($Sentence);

		return $Sentence->surfaceText;
	}

	private function createSurfaceText($Sentence)
	{
		$words = $Sentence->words;

		$words[0] = ucfirst($words[0]);

		return implode(' ', $words) . '.';
	}

	public function getRulesForAntecedent($antecedent)
	{
		if (isset($this->parseRules[$antecedent])) {
			return $this->parseRules[$antecedent];
		} else {
			return array();
		}
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

		if (isset($this->lexicon[$word])) {

			if (isset($this->lexicon[$word][$partOfSpeech])) {
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
			'whword', // WH-word that may not be followed by an NP
			'whwordNP', // WH-word that may be followed by an NP
			'aux',
			'auxBe', // am, are, is, ...
			'auxDo', // do, does, did, ...
			'preposition',
			'passivisationPreposition',
			'possessiveMarker' // 's (see http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html)
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
					'agreement' => array('number' => 's', 'person' => 1),
					'sem' => array('name' => $word)
				)
			);
		}
	}

	public function getWordForFeatures($partOfSpeech, array $features)
	{
		$word = false;

		if ($partOfSpeech == 'propernoun') {
			if (isset($features['head']['sem']['name'])) {
				$word = $features['head']['sem']['name'];
			}
		} elseif ($partOfSpeech == 'aux') {
			$word = $this->getWord($partOfSpeech, $features);
		} elseif ($partOfSpeech == 'passivisationPreposition') {
			$word = $this->getWord($partOfSpeech, $features);
		} elseif ($partOfSpeech == 'determiner') {
			if (is_numeric($features['head']['sem']['determiner']['type'])) {
				$word = $features['head']['sem']['determiner']['type'];
			} else {
				$word = $this->getWord($partOfSpeech, $features);
			}
		} elseif ($partOfSpeech == 'noun') {
			$word = $this->getWord($partOfSpeech, $features);
		} elseif ($partOfSpeech == 'preposition') {
			$word = $this->getWord($partOfSpeech, $features);
		} elseif ($partOfSpeech == 'verb') {
			$word = $this->getWord($partOfSpeech, $features);
		}

		return $word;
	}

	/*
	 * TODO: SLOW IMPLEMENTATION
	 */
	private function getWord($partOfSpeech, $features)
	{
		$predicate = isset($features['head']['sem']['predicate']) ? $features['head']['sem']['predicate'] : null;
		$tense = isset($features['head']['tense']) ? $features['head']['tense'] : null;
		$determiner = isset($features['head']['sem']['determiner']) ? $features['head']['sem']['determiner'] : null;
		$type = isset($features['head']['sem']['type']) ? $features['head']['sem']['type'] : null;
		$isa = isset($features['head']['sem']['category']) ? $features['head']['sem']['category'] : null;

		foreach ($this->lexicon as $word => $data) {

			// check if the word belongs to this part of speech
			if (!isset($data[$partOfSpeech])) {
				continue;
			}

			if ($isa) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['category'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['category'] != $isa) {
					continue;
				}
			}

			if ($predicate) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['predicate'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['predicate'] != $predicate) {
					continue;
				}
			}

			if ($tense) {
				if (!isset($data[$partOfSpeech]['features']['head']['tense'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['tense'] != $tense) {
					continue;
				}
			}

			if ($determiner) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['type'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['type'] != $determiner['type']) {
					continue;
				}
			}

			if ($type) {
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['type'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['type'] != $type) {
					continue;
				}
			}

			return $word;

		}

		return false;
	}

	/**
	 * Returns the first rule that have $antecedent and that match $features.
	 * @param $antecedent
	 * @param LabeledDAG $FeatureDAG
	 */
	public function getRuleForDAG($antecedent, LabeledDAG $FeatureDAG)
	{
		foreach ($this->generationRules[$antecedent] as $generationRule) {

			$pattern = array($antecedent . '@0' => $generationRule['condition']);

			if ($FeatureDAG->match($pattern)) {

				$rawRule = $generationRule['rule'];
				$Dag = EarleyParser::createLabeledDag($rawRule, false);
				$UnifiedDag = $Dag->unify($FeatureDAG);

				if ($UnifiedDag) {
					return array($rawRule, $UnifiedDag);
				}
			}
		}

		return false;
	}

	protected function getParseRules()
	{
		// Find parse rules:
		//
		// http://nlp.stanford.edu:8080/parser/index.jsp

		return array(
			'S' => array(

				// passive declarative
				// The car was driven by John
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'aux', 'features' => array('head-1' => null)),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem' => array('predicate' => null, 'arg1' => '?sem-2', 'arg2' => '?sem-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
				),

				// active declarative

				// John drives
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'active'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem' => array('arg1' => '?sem')))),
				),
				// John was driving
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem'))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem' => array('arg1' => '?sem')))),
				),

				// imperative

				// Drive! / Book that flight.
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'imperative'))),
					array('cat' => 'VP', 'features' => array('head-1' => null)),
				),

				// non-subject questions

				// Who Is John? / How many children had Lord Byron?
				// present tense
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question', 'voice' => 'active'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg1' => '?sem-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
				),

				// How many children did John have?
				// NP delivers arg1
#todo alleen-engels constructie!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question', 'voice' => 'active'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxDo', 'features' => array('head-1' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg1' => '?sem-2')))),
				),
				// Where was John born?
				// NP delivers arg2
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question', 'voice' => 'active'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxBe', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg2' => '?sem-2')))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem' => array('arg2' => '?sem-1')))),
				),

				// Was the car driven by John?
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'passive'))),
					array('cat' => 'aux'),
					array('cat' => 'NP', 'features' => array('head-2' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem' => array('predicate' => null, 'arg1' => '?sem-2', 'arg2' => '?sem-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head-3' => array('sem' => '?sem-2'))),
				),

				// Was John a fool?
				// The verb is '*be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active'))),
					array('cat' => 'aux', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('arg1' => '?sem-1', 'arg2' => '?sem-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP', 'features' => array('head' => '?head')),
					array('cat' => 'verb', 'features' => array('head' => '?head')),
				),
				// book that flight! / sees the book
				// verb is the head constituent (head-1)
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem' => array('arg2' => '?sem')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
				// driven by John
				// verb is the head constituent (head-1)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem' => array('modifier' => '?sem')))),
					array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			'WhNP' => array(
				// where, who
				array(
					array('cat' => 'WhNP', 'features' => array('head' => '?head')),
					array('cat' => 'whword', 'features' => array('head' => '?head')),
				),
				// which car, how many children
				array(
					array('cat' => 'WhNP', 'features' => array('head-1' => null)),
					array('cat' => 'whwordNP', 'features' => array('head-1' => array('variables' => array('role' => '?sem')))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			'NP' => array(
				// John
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem' => array('id' => 1)))),
					array('cat' => 'propernoun', 'features' => array('head-1' => null)),
				),
				// he
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem' => array('id' => 1)))),
					array('cat' => 'pronoun', 'features' => array('head-1' => null)),
				),
				// the car
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('id' => 1)))),
					array('cat' => 'DP', 'features' => array('head' => array('sem' => '?sem-2'))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('determiner' => '?sem-2')))),
				),
				// (large) car (in the lot)
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('id' => 1)))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => null))),
				),
			),
			// For N-bar, see 'The structure of modern english' - Brinton (2000) - p. 175
			'NBar' => array(
				// car
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('id' => 1)))),
					array('cat' => 'noun', 'features' => array('head-1' => array('sem-1' => null))),
				),
				// car in the lot
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('id' => 1)))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('modifier' => '?sem-2')))),
					array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem-2'))),
				),
			),
			// Prepositional Phrase
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP', 'features' => array('head-1' => null)),
					array('cat' => 'preposition', 'features' => array('head-1' => array('sem' => array('type' => null, 'object' => '?sem')))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			// Determiner Phrase
			// See 'The structure of modern english' - Brinton (2000) - p. 170
			'DP' => array(
				// the
				array(
					array('cat' => 'DP', 'features' => array('head' => '?head')),
					array('cat' => 'determiner', 'features' => array('head' => '?head')),
				),
				// Byron's
				array(
					array('cat' => 'DP', 'features' => array('head-1' => null)),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
					array('cat' => 'possessiveMarker', 'features' => array('head-1' => array('sem-1' => array('type' => null, 'object' => '?sem')))),
				),
			)
		);
	}

	protected function getGenerationRules()
	{
		// de volgorde van deze regels wijkt waarschijnlijk af van de syntax regels hierboven;
		// de volgorde van deze regels is namelijk die van meest restrictief naar minst restrictief
		// de volgorde van de regels hierboven is die van aflopende trefkans

		// merk op dat de sem juist niet gedeeld wordt met de head van de rule; ze worden juist gescheiden

		// de 'rule's zijn nodig om te bepalen hoe de phrase specification verdeeld wordt over de syntactisch regel

		return array(
			'S' => array(
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate' => '?pred', 'arg1' => '?sem-1', 'arg2' => '?sem-2')))),
						array('cat' => 'NP', 'features' => array('head' => array('tense-1' => null, 'sem' => '?sem-2'))),
						array('cat' => 'aux', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate' => '*be')))),
						array('cat' => 'VP', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'passivisationPreposition', 'features' => array()),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-1'))),
					)
				),
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate' => '?pred', 'arg1' => '?sem-1', 'arg2' => '?sem-2')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'tense-1' => null, 'sem' => '?sem-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'tense-1' => null, 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
					),
				),
			),
			'NP' => array(
				array(
					'condition' => array('head' => array('sem' => array('category' => null, 'modifier' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'modifier' => '?sem', 'determiner' => '?det')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'determiner' => '?det')))),
						array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem')))
					)
				),
				array(
					'condition' => array('head' =>array('sem' =>  array('category' => null, 'determiner' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'determiner' => '?det')))),
						array('cat' => 'determiner', 'features' => array('head' => array('sem' => array('determiner' => '?det')))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('category' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('name' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('name' => '?name')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('sem' => array('name' => '?name')))),
					)
				),
			),
			'VP' => array(
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null, 'category' => null, ''))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array())
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred')))),
					)
				),
			),
			'PP' => array(
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'PP', 'features' => array('head' => array('sem' => array('type' => '?type', 'object' => '?obj')))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('type' => '?type')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?obj'))),
					)
				),
			),
		);
	}
}