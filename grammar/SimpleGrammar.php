<?php

namespace agentecho\grammar;

use \agentecho\component\Microplanner;
use \agentecho\component\EarleyParser;
use \agentecho\datastructure\Sentence;
use \agentecho\datastructure\LabeledDAG;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar implements Grammar
{
	const END_OF_LINE = '** EOL **';
	const INDIGNATION = '** INDIGNATION **';
	const ELLIPSIS = '*** ELLIPSIS ***';
	const UNKNOWN_TERMINATOR = '*** UNKNOWN TERMINATOR ***';

	const LONGEST_PROPER_NOUN = 7;

	protected $parseRules = null;
	protected $generationRules = null;
	protected $lexicon = null;
	protected $Microplanner = null;
	protected $SurfaceRealiser = null;

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->parseRules = $this->getParseRules();
		$this->generationRules = $this->getGenerationRules();

		// output processing
		$this->Microplanner = new Microplanner();
	}

	/**
	 * This function turns a line of text into structured meaning.
	 *
	 * @param string $text Raw input.
	 * @param array $context The roles that are currently active.
	 * @return bool Succesful parse?
	 */
	public function parse($input, Sentence $Sentence)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($input, $Sentence);

		// continue to work with words as they occur in the lexicon
		$success = $this->makeLexicalItems($Sentence);
		if (!$success) {
			return false;
		}

		// create a sentence specification from this lexical items
		$result = EarleyParser::getFirstTree($this, $Sentence->lexicalItems);
		$Sentence->phraseSpecification = $result['tree'];

		if (!$result['success']) {
			$Sentence->errorMessage = $result['errorMessage'];
		}

		return $result['success'];
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

//		// create the output text from the syntactic structure
//		$output = $this->SurfaceRealiser->realise($phraseSpecification);
//		$Sentence->surfaceText = $output;
//
//		return $output;
	}

	private function createSurfaceText($Sentence)
	{
		$words = $Sentence->words;

		$words[0] = ucfirst($words[0]);

		return implode(' ', $words) . '.';
	}

	/**
	 * Assigns the values $Sentence->input and $Sentence->words of $Phrase from the value of $input
	 * @param string $input
	 * @param Sentence $Sentence
	 */
	private function splitIntoWords($input, $Sentence)
	{
		$terminator = null;

		$index = 0;
		$words = array();
		while (($word = $this->getNextWord($input, $index)) != self::END_OF_LINE) {

			// sentence terminators
			if (in_array($word, array('.', '!', '?', self::INDIGNATION, self::UNKNOWN_TERMINATOR))) {
				$terminator = $word;
				break;
			}

			// skip comma's for now
			if ($word == ',') {
				continue;
			}

			$words[] = $word;
		}

		$Sentence->words = $words;
		$Sentence->surfaceText = substr($input, 0, $index);
		$Sentence->terminator = $terminator;
	}

	/**
	 * Turns words into "lexical entries" (words that can be used by the parser).
	 * This means:
	 * 1) words are put into lowercase
	 * 2) unknown words are grouped together (so that 'john carpenter' becomes a single entry)
	 *
	 * todo coumpound words
	 *
	 * @param Sentence $Sentence
	 */
	private function makeLexicalItems(Sentence $Sentence)
	{
		$lexicalItems = array();
		$words = $Sentence->words;
		$count = count($Sentence->words);

		for ($i = 0; $i < $count; $i++) {

			$word = $words[$i];

			// lowercase
			$lcWord = strtolower($word);

			// word is recognized?
			if (isset($this->lexicon[$lcWord])) {
				$lexicalItems[] = $lcWord;
			} else {
				$involvedWords = $this->findLongestProperNoun($Sentence, array_slice($words, $i, self::LONGEST_PROPER_NOUN));
				if ($involvedWords === false) {
					return false;
				} else {
					$properNoun = implode(' ', $involvedWords);
					$lexicalItems[] = $properNoun;
					$i += count($involvedWords) - 1;
				}
			}
		}

		$Sentence->lexicalItems = $lexicalItems;
		return true;
	}

	private function findLongestProperNoun(Sentence $Sentence, $words)
	{
		while (count($words) > 0) {

			$properNoun = implode(' ', $words);
			if ($Sentence->getConversation()->isProperNoun($properNoun)) {
				return $words;
			}

			// remove last word
			array_pop($words);
		}

		return false;
	}

//	private function makeLexicalItems(Sentence $Sentence)
//	{
//		$lexicalItems = array();
//		$count = count($Sentence->words);
//		$store = '';
//
//		for ($i = 0; $i < $count; $i++) {
//
//			// lowercase
//			$word = $Sentence->words[$i];
//			$lcWord = strtolower($word);
//
//			// word is recognized?
//			if (isset($this->lexicon[$lcWord])) {
//				// pending store?
//				if ($store != '') {
//					$lexicalItems[] = $store;
//					$store = '';
//				}
//				$lexicalItems[] = $lcWord;
//			} else {
//
//				// glue together with previously unidentified words
//				if ($store == '') {
//					$store = $word;
//				} else {
//					$store .= ' ' . $word;
//				}
//			}
//		}
//
//		// pending store?
//		if ($store != '') {
//			$lexicalItems[] = $store;
//		}
//
//		$Sentence->lexicalItems = $lexicalItems;
//	}

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

	private function getNextWord($string, &$index)
	{
		$word = '';
		$length = strlen($string);

		if ($index == $length) {
			return self::END_OF_LINE;
		}

		// parse the actual characters
		while ($index < $length) {
			$c = $string[$index];
			if (strpos('.,?! ', $c) !== false) {
				break;
			}
			$word .= $c;
			$index++;
		}

		// parse comma's, points, etc
		if ($word === '') {
			while ($index < $length) {
				$c = $string[$index];
				if (strpos('.,?!', $c) === false) {
					break;
				}
				$word .= $c;
				$index++;
			}

			// turn combinations into tokens
			$len = strlen($word);
			if ($len > 1) {
		 		if (substr_count($word, '.') == $len) {
		 			$word = self::ELLIPSIS;
		 		} elseif (substr_count($word, ',') == $len) {
		 			$word = ',';
		 		} else {
					$apoCount = substr_count($word, '!');
					$questionCount = substr_count($word, '?');
					if ($apoCount > 1 || $questionCount > 1 || ($apoCount && $questionCount)) {
						$word = self::INDIGNATION;
					} else {
						$word = self::UNKNOWN_TERMINATOR;
					}
		 		}
			}
		}

		// strip whitespace
		while ($index < $length) {
			$c = $string[$index];
			if ($c == ' ') {
				$index++;
			} else {
				break;
			}
		}

		return $word;
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
			if (is_numeric($features['head']['sem']['determiner'])) {
				$word = $features['head']['sem']['determiner'];
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
				if (!isset($data[$partOfSpeech]['features']['head']['sem']['determiner'])) {
					continue;
				}
				if ($data[$partOfSpeech]['features']['head']['sem']['determiner'] != $determiner) {
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
		return array(
			'S' => array(

				// passive declarative
				// The car was driven by John
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive'))),
					array('cat' => 'NP', 'features' => array('head-2' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'aux', 'features' => array('head-1' => null)),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('predicate' => null, 'arg1{sem-2}' => null, 'arg2{sem-1}' => null)))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head-3' => array('sem-2' => null))),
				),

				// active declarative

				// John drives
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb (subject{sem-1})
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'active'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('arg1{sem-1}' => null)))),
				),
				// John was driving
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('arg1{sem-1}' => null)))),
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
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem-1' => array('arg1{sem-2}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
				),

				// How many children did John have?
				// NP delivers arg1
#todo alleen-engels constructie!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question', 'voice' => 'active'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxDo', 'features' => array('head-1' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem-1' => array('arg1{sem-2}' => null)))),
				),
				// Where was John born?
				// NP delivers arg2
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question', 'voice' => 'active'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxBe', 'features' => array('head' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem-1' => array('arg2{sem-2}' => null)))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb (object{sem-1})
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('arg2{sem-1}' => null)))),
				),

				// Was the car driven by John?
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'passive'))),
					array('cat' => 'aux'),
					array('cat' => 'NP', 'features' => array('head-2' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('predicate' => null, 'arg1{sem-2}' => null, 'arg2{sem-1}' => null)))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head-3' => array('sem-2' => null))),
				),

				// Was John a fool?
				// The verb is '*be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active'))),
					array('cat' => 'aux', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('arg1{sem-1}' => null, 'arg2{sem-2}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => null)),
				),
				// book that flight! / sees the book
				// verb is the head constituent (head-1)
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb (object{sem-1})
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem' => array('arg2{sem-2}' => null)), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('sem-2' => null))),
				),
				// driven by John
				// verb is the head constituent (head-1)
				// NP forms the object of verb (object{sem-1})
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem' => array('modifier{sem-2}' => null)))),
					array('cat' => 'PP', 'features' => array('head' => array('sem-2' => null))),
				),
			),
			'WhNP' => array(
				// where, who
				array(
					array('cat' => 'WhNP', 'features' => array('head-1' => null)),
					array('cat' => 'whword', 'features' => array('head-1' => null)),
				),
				// which car, how many children
				array(
					array('cat' => 'WhNP', 'features' => array('head-1' => null)),
					array('cat' => 'whwordNP', 'features' => array('head-1' => array('variables' => array('role{sem-1}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('sem-1' => null))),
				),
			),
			'NP' => array(
				// children
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem' => array('id' => 1)))),
					array('cat' => 'noun', 'features' => array('head-1' => null)),
				),
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
					array('cat' => 'determiner', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'noun', 'features' => array('head-1' => array('sem-1' => null))),
				),
				// the car in the lot
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('id' => 1)))),
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('modifier{sem-2}' => null)))),
					array('cat' => 'PP', 'features' => array('head' => array('sem-2' => null))),
				),
			),
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP', 'features' => array('head-1' => null)),
					array('cat' => 'preposition', 'features' => array('head-1' => array('sem' => array('type' => null, 'object{sem-1}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('sem-1' => null))),
				),
			),
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
						array('cat' => 'S', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null, 'arg1-1' => null, 'arg2-1' => null)))),
						array('cat' => 'NP', 'features' => array('head' => array('tense-1' => null, 'sem{arg2-1}' => null))),
						array('cat' => 'aux', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate' => '*be')))),
						array('cat' => 'VP', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null)))),
						array('cat' => 'passivisationPreposition', 'features' => array()),
						array('cat' => 'NP', 'features' => array('head' => array('sem{arg1-1}' => null))),
					)
				),
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null, 'arg1-1' => null, 'arg2-1' => null)))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'tense-1' => null, 'sem{arg1-1}' => null))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'tense-1' => null, 'sem' => array('predicate-1' => null)))),
						array('cat' => 'NP', 'features' => array('head' => array('sem{arg2-1}' => null))),
					),
				),
			),
			'NP' => array(
				array(
					'condition' => array('head' => array('sem' => array('category' => null, 'modifier' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category-1' => null, 'modifier-1' => null, 'determiner-1' => null)))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category-1' => null, 'determiner-1' => null)))),
						array('cat' => 'PP', 'features' => array('head' => array('sem{modifier-1}' => null)))
					)
				),
				array(
					'condition' => array('head' =>array('sem' =>  array('category' => null, 'determiner' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category-1' => null, 'determiner-1' => null)))),
						array('cat' => 'determiner', 'features' => array('head' => array('sem' => array('determiner-1' => null)))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category-1' => null)))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('category' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category-1' => null)))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category-1' => null)))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('name' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('name-1' => null)))),
						array('cat' => 'propernoun', 'features' => array('head' => array('sem' => array('name-1' => null)))),
					)
				),
			),
			'VP' => array(
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null, 'category' => null, ''))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null)))),
						array('cat' => 'verb', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null)))),
						array('cat' => 'NP', 'features' => array())
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null)))),
						array('cat' => 'verb', 'features' => array('head' => array('tense-1' => null, 'sem' => array('predicate-1' => null)))),
					)
				),
			),
			'PP' => array(
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'PP', 'features' => array('head' => array('sem' => array('type-1' => null, 'object-1' => null)))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('type-1' => null)))),
						array('cat' => 'NP', 'features' => array('head' => array('sem{object-1}' => null))),
					)
				),
			),
		);
	}
}