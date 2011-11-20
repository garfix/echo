<?php

require_once(__DIR__ . '/Grammar.php');
require_once(__DIR__ . '/SemanticAnalyzer.php');
require_once(__DIR__ . '/SentenceInterpretation.php');
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
	protected $Parser = null;
	protected $Microplanner = null;
	protected $SurfaceRealiser = null;

	public function __construct()
	{
		// structure
		$this->lexicon = $this->getLexicon();
		$this->syntax = $this->getSyntax();
		$this->word2phraseStructure = $this->getWord2PhraseStructure();

		// input processing
		$this->Parser = new EarleyParser();
		$this->Analyzer = new SemanticAnalyzer();

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
		$syntaxTrees = $this->Parser->parse($this, $Sentence->lexicalEntries);

		$Sentence->interpretations = array();
		foreach ($syntaxTrees as $syntaxTree) {
			$Interpretation = new SentenceInterpretation();
			$Interpretation->syntaxTree = $syntaxTree;
			$Interpretation->structure = $this->getSentenceStructure($syntaxTree);
			$Interpretation->phraseStructure = $this->Analyzer->analyze($this, $syntaxTree, $workingMemory);

			$Sentence->interpretations[] = $Interpretation;
		}

		return !empty($Sentence->interpretations);
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
	 * Creates a phrase structure based on a word of a given part-of-speech.
	 *
	 * @param array $partOfSpeech
	 * @param array $word
	 * @return array A phrase structure
	 */
	public function analyzeWord($partOfSpeech, $word)
	{
		$structure = array();

		if ($partOfSpeech == 'propernoun') {
			$structure['id'] = self::getUniqueId();
			$structure['type'] = 'object';
			$structure['name'] = $word;
		} elseif ($partOfSpeech == 'preposition') {
			$structure = $this->word2phraseStructure($word, $partOfSpeech);
			#$structure['predicate'] = $this->word2phraseStructure($word, $partOfSpeech);
		} elseif ($partOfSpeech == 'noun') {
			$structure =  $this->word2phraseStructure($word, $partOfSpeech);
			$structure['type'] = 'object';
			$structure['id'] = self::getUniqueId();
		} elseif ($partOfSpeech == 'pronoun') {
			$structure =  $this->word2phraseStructure($word, $partOfSpeech);
			$structure['type'] = 'object';
			$structure['id'] = self::getUniqueId();
		} elseif ($partOfSpeech == 'verb') {
			$structure = $this->word2phraseStructure($word, $partOfSpeech);
			#todo: past tense (e.g. influenced)
		} elseif ($partOfSpeech == 'aux') {
			$structure['mode'] = 'passive'; # is not always the case (i.e. did ... have)
			$structure['lex'] = $word;
		} elseif ($partOfSpeech == 'determiner') {
			$structure = $this->word2phraseStructure($word, $partOfSpeech);
		} elseif ($partOfSpeech == 'whword') {
			$structure = $this->word2phraseStructure($word, $partOfSpeech);
			$structure['type'] = 'object';
			$structure['id'] = self::getUniqueId();
		} else {
			trigger_error('Part-of-speech not recognized: ' . $partOfSpeech);
		}

		if (ChatbotSettings::$debugAnalyzer) {
			if ($structure) {
				r($structure);
			}
		}

		return $structure;
	}

	/**
	 * Creates a phrase structure based on a set of parts-of-speech and the child phrase structures
	 * that correspond with these parts of speech.
	 *
	 * @param array $partsOfSpeech
	 * @param array $constituentStructures
	 * @return array A phrase structure
	 */
	public function analyzeBranch($partOfSpeech, $constituentPartsOfSpeech, $constituentStructures)
	{
		$structure = array();

		$rule = array_merge(array($partOfSpeech), $constituentPartsOfSpeech);

		switch ($rule) {
			case array('S', 'aux', 'NP', 'VP'):
				list($aux, $NP, $VP) = $constituentStructures;
				$structure = $VP;
				$structure['type'] = 'clause';
# leid de rol af uit het werkwoord!
				$structure['participants']['*patient'] = $NP;
				$structure['mode'] = $aux['mode'];
				$structure['act'] = 'yes-no-question';
				break;
			case array('S', 'aux', 'NP', 'NP'):
				list($aux, $NP, $NP2) = $constituentStructures;
$structure['type'] = 'clause';
$structure['predicate'] = '*be';
$structure['participants']['*theme'] = $NP;
$structure['participants']['*patient'] = $NP2;
$structure['mode'] = $aux['mode'];
$structure['act'] = 'yes-no-question';
				break;
			case array('S', 'Wh-NP', 'aux', 'NP', 'VP'):
				list($WhNP, $aux, $NP, $VP) = $constituentStructures;
				$structure = $VP;
				$structure['type'] = 'clause';
				$structure['act'] = 'question-about-object';
				$structure['mode'] = $aux['mode'];
				$structure['participants']['*actor'] = $NP;

				$question = $WhNP['question'];
				$participants = array('*location', '*time');
				if (in_array($question, $participants)) {
					$structure['participants'][$question] = $WhNP;
				} else {
					$structure['participants']['*patient'] = $WhNP;
				}

				break;
			case array('S', 'Wh-NP', 'VP'):
				list($WhNP, $VP) = $constituentStructures;
				$structure = $VP;
				$structure['type'] = 'clause';
				$structure['participants']['*patient'] = $WhNP;
				$structure['act'] = 'question-about-object';
				break;
			case array('S', 'VP'):
				list($VP) = $constituentStructures;
				$structure = $VP;
				break;
			case array('S', 'NP', 'VP'):
				list($NP, $VP) = $constituentStructures;
				$structure = $VP;
				$structure['type'] = 'clause';
				$structure['participants']['*actor'] = $NP;
				break;
			case array('NP', 'propernoun'):
				list($proper) = $constituentStructures;
				$structure = $proper;
				break;
			case array('NP', 'pronoun'):
				list($pronoun) = $constituentStructures;
				$structure = $pronoun;
				break;
			case array('NP', 'noun'):
				list($noun) = $constituentStructures;
				$structure = $noun;
				break;
			case array('NP', 'propernoun', 'NP'):
				list($proper, $NP) = $constituentStructures;
				$structure = $NP;
				$structure['name'] = $proper['name'] . ' ' . $structure['name'];
				break;
			case array('NP', 'NP', 'PP'):
				list($NP, $PP) = $constituentStructures;
				$structure = $NP;
				$modifier = $PP;
				unset($modifier['preposition']);
				$structure['modifiers'][$PP['preposition']] = $modifier;
				break;
			case array('NP', 'determiner', 'noun'):
				list($det, $noun) = $constituentStructures;
				$structure = $noun;
				$structure['determiner'] = $det['determiner'];
				break;
			case array('VP', 'verb'):
				list($verb) = $constituentStructures;
				$structure = $verb;
				break;
			case array('VP', 'verb', 'PP'):
				list($verb, $PP) = $constituentStructures;
				$structure = $verb;
				$participant = $PP;
				unset($participant['preposition']);
				$structure['participants'][$PP['preposition']] = $participant;
				break;
			case array('VP', 'verb', 'NP'):
				list($verb, $NP) = $constituentStructures;

				if ($verb['predicate'] == '*be') {
					if (isset($NP['name']) || isset($NP['referring-expression'])) {
						// identification
						$structure['predicate'] = '*identify';
						// I made up this participant for lack of suitable match in p. 270 of 'The structure of modern english'
						$structure['participants']['*identity'] = $NP;
					} else {
						// class-membership or class-inclusion
						# todo
					}
				} else {
					$structure = $verb;
					$structure['participants']['*actor'] = $NP;
				}
				break;
			case array('PP', 'preposition', 'NP'):
				list($preposition, $NP) = $constituentStructures;
				$structure = $NP;
				$structure['preposition'] = $preposition['preposition'];
				break;
			case array('Wh-NP', 'whword'):
				list($whWord) = $constituentStructures;
				$structure = $whWord;
				break;
			case array('Wh-NP', 'whword', 'NP'):
				list($whWord, $NP) = $constituentStructures;
				$structure = array_merge($NP, $whWord);
				break;
			default:
				trigger_error('Unknown rule: ' . print_r($rule, true));
#exit;
				break;
		}

		if (ChatbotSettings::$debugAnalyzer) {
			if ($structure) {
				r($structure);
			}
		}

		return $structure;
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
		$syntaxTree = $this->Microplanner->plan($Sentence->interpretations[0]->phraseStructure);
		if (!$syntaxTree) {
			return false;
		}
		$Sentence->interpretations[0]->syntaxTree = $syntaxTree;

		// create the output text from the syntactic structure
		$output = $this->SurfaceRealiser->realise($syntaxTree);
		$Sentence->surfaceText = $output;

		return $output;
	}

	/**
	 * An implementation of Ch. 9.3 of "Speech and Language Processing (p.332)"
	 */
	public function getSentenceStructure($syntaxTree)
	{
		$c1 = isset($syntaxTree['constituents'][0]) ? $syntaxTree['constituents'][0]['part-of-speech'] : null;
		$c2 = isset($syntaxTree['constituents'][1]) ? $syntaxTree['constituents'][1]['part-of-speech'] : null;
		$c3 = isset($syntaxTree['constituents'][2]) ? $syntaxTree['constituents'][2]['part-of-speech'] : null;
		$c4 = isset($syntaxTree['constituents'][3]) ? $syntaxTree['constituents'][3]['part-of-speech'] : null;
		$input = array($c1, $c2, $c3, $c4);

		if ($input == array('NP', 'VP', null, null)) {
			return "declarative";
		} elseif ($input == array('aux', 'NP', 'VP', null)) {
			return "yes-no-question";
		} elseif ($input == array('Wh-NP', 'VP', null, null)) {
			return "wh-subject-question";
		} elseif ($input == array('Wh-NP', 'aux', 'NP', 'VP')) {
			return "wh-non-subject-question";
		} elseif ($input == array('VP', null, null, null)) {
			return "imperative";
		} else {
			return null;
		}
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
	public function getGrammarRulesForConstituent($constituent)
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

#		if (isset($this->lexicon[$word])) {
		if (isset($this->lexicon[$word])) {
			if (isset($this->lexicon[$word][$partOfSpeech])) {
$result = true;
			}
//			$parts = $this->lexicon[$word];
//			if (is_array($parts)) {
//				$result = in_array($partOfSpeech, $parts);
//			} else {
//				$result = $partOfSpeech == $parts;
//			}
		} else {

			// all words can be proper nouns
			if ($partOfSpeech == 'propernoun') {
				$result = true;
			}

		}
//echo "$word/$partOfSpeech/$result\n";

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
			'whword',
			'aux',
			'preposition',
		));
	}

	/**
	 * Creates a syntax rule, including especially, features, for a word.
	 * @return array
	 */
	public function getLabeledDagForWord($word, $partOfSpeech)
	{
		if (isset($this->lexicon[$word][$partOfSpeech])) {
			return new LabeledDAG(array($partOfSpeech => $this->lexicon[$word][$partOfSpeech]['features']));
		} else {
			// presume proper noun
			return new LabeledDAG(
				array($partOfSpeech => array('head' => array('agreement' => array('number' => 's', 'person' => 1))))
			);
		}
	}

	public function getSyntax()
	{
/*
        alternative:

		$syntax[] = array(
			array('cat' => 'S', 'features' => array('head-1')),
			array('cat' => 'NP', 'features' => array('head', 'agreement-2')),
			array('cat' => 'VP', 'features' => array('head-1', 'agreement-2')),
		);
*/


# which rules are really necessary?

# NB: regels met twee dezelfde consequents mogen gewoon niet meer: herschrijf maar naar wat anders

		$syntax = array(
			'S' => array(
				// John drives
				array(
					'antecedent' => 'S',
					'consequents' => array('NP', 'VP'),
					'features' => array(
						'antecedent' => array('head-1' => null),
						'consequents' => array(
							array('head' => array('agreement-2' => null)),
							array('head-1' => array('agreement-2' => null)),
						)
					)
				),



				array(
					'antecedent' => 'S',
					'consequents' => array('Wh-NP', 'VP'),
				),
				array(
					'antecedent' => 'S',
					'consequents' => array('VP'),
				),
				// Was John driving?
				array(
					'antecedent' => 'S',
					'consequents' => array('aux', 'NP', 'VP'),
				),
				// Was John a fool?
				array(
					'antecedent' => 'S',
					'consequents' => array('aux', 'NP', 'NP'),
				),
				// Why was John driving?
				array(
					'antecedent' => 'S',
					'consequents' => array('Wh-NP', 'aux', 'NP', 'VP'),
				),
//				array(
//					'antecedent' => 'S',
//					'consequents' => array('WH-phrase'),
//				),
//			),
//			'WH-phrase' => array(
//				array(
//					'antecedent' => 'WH-phrase',
//					'consequents' => array('AP', 'verb', 'NP'),
//				),
			),
			'VP' => array(
				array(
					'antecedent' => 'VP',
					'consequents' => array('verb'),
				),
				array(
					'antecedent' => 'VP',
					'consequents' => array('verb', 'NP'),
					'features' => array(
						'antecedent' => array('head-1' => null),
						'consequents' => array(
							array('head-1' => array('agreement' => null)),
							array(),
#todo: also needs a match
						)
					)
				),
				array(
					'antecedent' => 'VP',
					'consequents' => array('verb', 'PP')
				),
//				array(
//					'antecedent' => 'VP',
//					'consequents' => array('verb', 'AP'),
//				)
			),
			'Wh-NP' => array(
				array(
					'antecedent' => 'Wh-NP',
					'consequents' => array('whword'),
				),
				array(
					'antecedent' => 'Wh-NP',
					'consequents' => array('whword', 'NP'),
				),
			),
			'NP' => array(
				// he
				array(
					'antecedent' => 'NP',
					'consequents' => array('pronoun'),
					'features' => array(
						'antecedent' => array('head-1' => null),
						'consequents' => array(
							array('head-1' => null),
						)
					)
				),
				// children
				array(
					'antecedent' => 'NP',
					'consequents' => array('noun'),
				),
				// John
				array(
#					'antecedent' => 'NP',
#					'consequents' => array('propernoun'),
					'antecedent' => 'NP',
					'consequents' => array('propernoun'),
					'features' => array(
						'antecedent' => array('head-1' => null),
						'consequents' => array(
							array('head-1' => array('agreement' => null)),
						)
					)
				),
				// John de Vries
				array(
					'antecedent' => 'NP',
					'consequents' => array('propernoun', 'NP'),
				),
//				array(
//					'antecedent' => 'NP',
//					'consequents' => array('numeral', 'noun'),
//				),
				// the car
				array(
					'antecedent' => 'NP',
					'consequents' => array('determiner', 'noun'),
				),
				// the car in the lot
				array(
					'antecedent' => 'NP',
					'consequents' => array('NP', 'PP'),
				),
			),
			'PP' => array(
				// in the lot
				array(
					'antecedent' => 'PP',
					'consequents' => array('preposition', 'NP')
				),
			),
//			'AP' => array(
//				array(
//					'antecedent' => 'AP',
//					'consequents' => array('NP', 'adjective'),
//				),
//				array(
//					'antecedent' => 'AP',
//					'consequents' => array('adverb', 'adjective'),
//				)
//			)
		);

		return $syntax;
	}
}