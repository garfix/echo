<?php

require_once(__DIR__ . '/Grammar.php');
require_once(__DIR__ . '/Microplanner.php');
require_once(__DIR__ . '/SurfaceRealiser.php');
require_once(__DIR__ . '/EarleyParser.php');

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
class SimpleGrammar implements Grammar
{
	const END_OF_LINE = '** EOL **';
	const INDIGNATION = '** INDIGNATION **';
	const ELLIPSIS = '*** ELLIPSIS ***';
	const UNKNOWN_TERMINATOR = '*** UNKNOWN TERMINATOR ***';

	/** @var array An array of grammar rules, ordered by antecedent */
	protected $syntax = null;
	protected $lexicon = null;
	protected $Microplanner = null;
	protected $SurfaceRealiser = null;

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->syntax = $this->getSyntax();
//		$this->word2phraseStructure = $this->getWord2PhraseStructure();

		// output processing
		$this->Microplanner = new Microplanner();
		$this->SurfaceRealiser = new SurfaceRealiser();
	}

	private static function getUniqueId()
	{
		static $id = 0;

		return ++$id;
	}

	/**
	 * This function turns a line of text into structured meaning.
	 *
	 * @param string $text Raw input.
	 * @param array $context The roles that are currently active.
	 * @return bool Succesful parse?
	 */
	public function parse($input, $Sentence, $workingMemory)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($input, $Sentence);

		// continue to work with words as they occur in the lexicon
		$this->makeLexicalEntries($Sentence);

		// create one or more parse trees from this sentence
		$Sentence->syntaxTree = EarleyParser::getFirstTree($this, $Sentence->lexicalEntries);

		return !empty($Sentence->syntaxTree);
	}

	protected function word2phraseStructure($word, $partOfSpeech)
	{
		if (isset($this->word2phraseStructure[$partOfSpeech][$word])) {
			return $this->word2phraseStructure[$partOfSpeech][$word];
		} else {
			trigger_error('Word meaning not known: ' . $word . ' (' . $partOfSpeech . ')');
			return null;
		}
	}

	public function getFeatures($word, $partOfSpeech)
	{
		if (isset($this->lexicon[$word][$partOfSpeech]['features'])) {
			return $this->lexicon[$word][$partOfSpeech]['features'];
		} else {
			return array();
		}
	}

	/**
	 * This function turns structured meaning into a line of text.
	 *
	 * @param Sentence $Sentence A sentence that contains a speech act, and meaning.
	 * @return mixed Either a sentence in natural language, or false, in case of failure
	 */
	public function generate(Sentence $Sentence)
	{
		// turn the intention of the sentence into a syntactic structure
		$syntaxTree = $this->Microplanner->plan($Sentence->phraseStructure, $this);
		if (!$syntaxTree) {
			return false;
		}
		$Sentence->syntaxTree = $syntaxTree;

		// create the output text from the syntactic structure
		$output = $this->SurfaceRealiser->realise($syntaxTree);
		$Sentence->surfaceText = $output;

		return $output;
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
	 * @param Sentence $Sentence
	 */
	private function makeLexicalEntries($Sentence)
	{
		$lexicalEntries = array();
		$count = count($Sentence->words);
		$store = '';

		for ($i = 0; $i < $count; $i++) {

			// lowercase
			$lcWord = strtolower($Sentence->words[$i]);

			// word is recognized?
			if (isset($this->lexicon[$lcWord])) {
				// pending store?
				if ($store != '') {
					$lexicalEntries[] = $store;
					$store = '';
				}
				$lexicalEntries[] = $lcWord;
			} else {
				// glue together with previously unidentified words
				if ($store == '') {
					$store = $lcWord;
				} else {
					$store .= ' ' . $lcWord;
				}
			}
		}

		// pending store?
		if ($store != '') {
			$lexicalEntries[] = $store;
		}

		$Sentence->lexicalEntries = $lexicalEntries;
	}

	/**
	 * Returns all grammar rules with $constituent as their antecedent.
	 *
	 * @param string $constituent
	 * @return array A grammar rule.
	 */
	public function getRulesForConstituent($constituent)
	{
		if (isset($this->syntax[$constituent])) {
			return $this->syntax[$constituent];
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
			'preposition',
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

	/**
	 * Returns the rules that have $antecedent and that match $features.
	 * @param $antecedent
	 * @param $features
	 */
	public function getRulesForFeatures($antecedent, $features)
	{
		$FeatureDag = new LabeledDAG(array(
			"$antecedent@0" => $features
		));

		$matches = array();
		foreach ($this->syntax[$antecedent] as $rule) {

			$Dag = EarleyParser::createLabeledDag($rule);
			$UnifiedDag = $Dag->unify($FeatureDag);

			if ($UnifiedDag) {
				$matches[] = array($rule, $UnifiedDag);
			}
		}
//echo count($matches);exit;
		return $matches;
	}

	public function getSyntax()
	{
		return array(
			'S' => array(

				// declarative

				// John drives
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb (subject{sem-1})
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'active'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('param1{sem-1}' => null)))),
				),
				// John was driving
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('param1{sem-1}' => null)))),
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
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem-1' => array('param1{sem-2}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
				),
				// How many miles was John driving?
				// progressive
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// WhNP semantics is passed directly to the VP (sem-1)
				// NP forms the subject of VP's verb (subject{sem-2})
#todo alleen-engels constructie!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('progressive' => 1, 'agreement-2' => null, 'sem-1' => array('param1{sem-2}' => null)))),
				),
				// Where was John born?
				// perfect tense
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-non-subject-question'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('progressive' => 0, 'agreement-2' => null, 'sem-1' => array('param2{sem-2}' => null)))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb (object{sem-1})
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement-2' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('param2{sem-1}' => null)))),
				),
				// Was John a fool?
				// The verb is '*be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question'))),
					array('cat' => 'aux', 'features' => array('head-1' => array('agreement-2' => null, 'sem' => array('param1{sem-1}' => null, 'param2{sem-2}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => null, 'arguments' => 0)),
				),
				// book that flight! / sees the book
				// verb is the head constituent (head-1)
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb (object{sem-1})
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem' => array('param2{sem-2}' => null)), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('sem-2' => null))),
				),
				// driven by John
				// verb is the head constituent (head-1)
				// NP forms the object of verb (object{sem-1})
				array(
					array('cat' => 'VP', 'features' => array('head-1' => null)),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem-2' => null))),
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
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => null))),
					array('cat' => 'PP', 'features' => array('head' => array('sem-1' => null))),
				),
			),
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP', 'features' => array('head-1' => null)),
					array('cat' => 'preposition', 'features' => array('head-1' => array('sem' => null, 'variables' => array('prep{sem-1}' => null)))),
					array('cat' => 'NP', 'features' => array('head' => array('sem-1' => null))),
				),
			),
		);
	}
}
