<?php

require_once(__DIR__ . '/Grammar.php');
require_once(__DIR__ . '/SemanticAnalyzer.php');
require_once(__DIR__ . '/SentenceInterpretation.php');
require_once(__DIR__ . '/Microplanner.php');
require_once(__DIR__ . '/SurfaceRealiser.php');

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

		// input processing
		$this->Parser = new EarleyParser();
		$this->Analyzer = new SemanticAnalyzer();

		// output processing
		$this->Microplanner = new Microplanner();
		$this->SurfaceRealiser = new SurfaceRealiser();
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

		// continue to work with lower cased words
		$Sentence->lcWords = array_map('strtolower', $Sentence->words);

		// create one or more parse trees from this sentence
		$syntaxTrees = $this->Parser->parse($this, $Sentence->lcWords);

		$Sentence->interpretations = array();
		foreach ($syntaxTrees as $syntaxTree) {
			$Interpretation = new SentenceInterpretation();
			$Interpretation->syntaxTree = $syntaxTree;
			$Interpretation->structure = $this->getSentenceStructure($syntaxTree);
			$Interpretation->semantics = $this->Analyzer->analyze($syntaxTree, $workingMemory);

			$Sentence->interpretations[] = $Interpretation;
		}

		return !empty($Sentence->interpretations);
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
		$syntaxTree = $this->Microplanner->plan($Sentence->interpretations[0]->semantics);
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
		} elseif ($input == array('Aux', 'NP', 'VP', null)) {
			return "yes-no-question";
		} elseif ($input == array('Wh-NP', 'VP', null, null)) {
			return "wh-subject-question";
		} elseif ($input == array('Wh-NP', 'Aux', 'NP', 'VP')) {
			return "wh-non-subject-question";
		} elseif ($input == array('VP', null, null, null)) {
			return "imperative";
		} else {
			return null;
		}
	}

	/**
	 * Assigns the values $Sentence->input and $Sentence->words of $Phrase rom the value of $input
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
		if (isset($this->lexicon[$word])) {
			$parts = $this->lexicon[$word];
			if (is_array($parts)) {
				return in_array($partOfSpeech, $parts);
			} else {
				return $partOfSpeech == $parts;
			}
		} else {

			// all words can be proper nouns
			if ($partOfSpeech == 'proper-noun') {
				return true;
			}

		}

		return false;
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
	 * Returns true if the given syntactic category is a basic part-of-speech.
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
			'proper-noun',
			'wh-word'
		));
	}

	public function getSyntax()
	{
		$syntax = array(
			'S' => array(
				// S => NP VP [1]
				array(
					'antecedent' => 'S',
					'consequents' => array('NP', 'VP'),
				),
				// S => Wh-NP VP [1]
				array(
					'antecedent' => 'S',
					'consequents' => array('Wh-NP', 'VP'),
				),
				// S => VP NP [1]
				array(
					'antecedent' => 'S',
					'consequents' => array('VP'),
				),
				//				// S => CPwh [2]
//				array(
//					'antecedent' => 'S',
//					'consequents' => array('WH-phrase'),
//				),
//			),
//			'WH-phrase' => array(
//				// CPwh => AP V NP [2]
//				array(
//					'antecedent' => 'WH-phrase',
//					'consequents' => array('AP', 'verb', 'NP'),
//				),
			),
			'VP' => array(
				// VP => V [1]
				array(
					'antecedent' => 'VP',
					'consequents' => array('verb')
				),
				// VP => V [1]
				array(
					'antecedent' => 'VP',
					'consequents' => array('verb', 'NP')
				),
				//				// VP => V AP [1]
//				array(
//					'antecedent' => 'VP',
//					'consequents' => array('verb', 'AP'),
//				)
			),
			'Wh-NP' => array(
				// Wh-NP => Wh-word
				array(
					'antecedent' => 'Wh-NP',
					'consequents' => array('wh-word'),
				),
			),
			'NP' => array(
				// NP => Pronoun [1][2]
				array(
					'antecedent' => 'NP',
					'consequents' => array('pronoun'),
				),
				array(
					'antecedent' => 'NP',
					'consequents' => array('proper-noun'),
				),
				//				// NP => Numeral Noun [1]
//				array(
//					'antecedent' => 'NP',
//					'consequents' => array('numeral', 'noun'),
//				),
				// NP => Det Noun [1]
				array(
					'antecedent' => 'NP',
					'consequents' => array('determiner', 'noun'),
				),
			),
			'AP' => array(
//				// AP => NP Adj [1]
//				array(
//					'antecedent' => 'AP',
//					'consequents' => array('NP', 'adjective'),
//				),
//				// AP => ADV Adj [2]
//				array(
//					'antecedent' => 'AP',
//					'consequents' => array('adverb', 'adjective'),
//				)
			)
		);

		return $syntax;
	}
}