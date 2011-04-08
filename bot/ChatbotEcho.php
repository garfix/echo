<?php

require_once __DIR__ . '/language/LanguageProcessor.php';

class ChatbotEcho
{
	static $instance = null;

	private $statements = array();
	private $LanguageProcessor;
	private $workingMemory = array();

	public $lastestStructure = null;

	private $knownWords = array(
		'i', 'am', 'who'
	);

	private function __construct()
	{
		$this->LanguageProcessor = new LanguageProcessor();
	}

	public static function getInstance()
	{
		if (self::$instance == null) {
			self::$instance = new ChatbotEcho();
		}
		return self::$instance;
	}

	public function parse($input)
	{
		$sentences = $this->LanguageProcessor->parse($input, $this->workingMemory);

		return $sentences;
	}

//		$strategies = array(
//			'strategyCommands',
//			'simpleQuestions',
//			'understandSimpleStatements',
//			'answerSimpleQuestions'
//		);

//		// people make many spelling errors
//		// try to correct for these
//
//		// note: i am sam => i am am (!)
//		$words = explode(' ', $input);
//		$newWords = array();
//		foreach ($words as $word) {
//			if (array_search($word, $this->knownWords) === false) {
//				foreach ($this->knownWords as $knownWord) {
//					if (levenshtein($word, $knownWord) < 2) {
//						$word = $knownWord;
//						break;
//					}
//				}
//			}
//			$newWords[] = $word;
//		}
//
//		$input = implode(' ', $newWords);
//
//		$score = $highScore = 0;
//		$bestResponse = 'ok';
//
//		foreach ($strategies as $strategy) {
//			$response = $this->$strategy($input, $score);
//			if ($response && $score > $highScore) {
//				$highScore = $score;
//				$bestResponse = $response;
//			}
//		}
//
//		return $bestResponse;




	/**
	 *
	 * @param mixed $statement three parameters: subject, predicate, object
	 */
	public function tell($statement)
	{
		$this->statements[] = func_get_args();
	}

	public function addToWorkingMemory($subject, $predicate, $object)
	{
		$this->workingMemory[$subject][$predicate] = $object;
	}

	public function ask($pattern)
	{
		$pattern = func_get_args();
		$answer = null;

		$count = count($pattern);
		$index = -1;
		foreach ($pattern as $index => $word) {
			if ($pattern[$index][0] == '?') {
				break;
			}
		}

		foreach ($this->statements as $statement) {
			if (count($statement) != $count) {
				continue;
			}
			foreach ($statement as $i => $part) {
				if ($pattern[$i][0] == '?') {
					$answer = $part;
				} else {
					if ($pattern[$i] != $part) {
						continue;
					}
				}
			}
			return $answer;
		}

		return null;
	}

	public function answer($question)
	{
		$sentences = $this->parse($question);
		if ($sentences) {

			$Sentence = $sentences[0];

			if ($Sentence->interpretations[0]->structure == 'wh-subject-question') {

				// this is a question
				$semantics = $Sentence->interpretations[0]->semantics;

				// try to answer it

				// turn semantics into triples
//				r($semantics);

				// generate a sentence from triples
				return $this->LanguageProcessor->generate($semantics, $this->workingMemory);
			}
		} else {
			return "I don't understand";
		}
	}

	public function understandSimpleStatements($input, &$score)
	{
		$score = 3;

		if (preg_match('/i am (\w*)/i', $input, $matches)) {
			$this->store('name', 'DISCUSSION_PARTNER', $matches[1]);

			return 'Hi, I\'m Echo';
		}

		return null;
	}

	public function answerSimpleQuestions($input, &$score)
	{
		$score = 3;
		$lcInput = strtolower($input);

		if ($lcInput == 'who am i?') {
			if ($answer = $this->retrieve('name', 'DISCUSSION_PARTNER', '?')) {
				return 'You are ' . $answer;
			}
		}
	}

	public function simpleQuestions($input, &$score)
	{
		$score = 2;
		$lcInput = strtolower($input);

		if ($lcInput == 'what is your name?') {
			return "Echo";
		}
		if ($lcInput == 'wat is je naam?') {
			return "Echo";
		}
		if ($lcInput == 'wat is jouw naam?') {
			return "Echo";
		}
		if ($lcInput == 'what is your name?') {
			return "Echo";
		}
		if ($lcInput == 'who are you?') {
			return "Echo";
		}

		return null;
	}

	public function strategyCommands($input, &$score)
	{
		$score = 1;
		$lcInput = strtolower($input);

		if ($lcInput == 'quit' || $lcInput == 'exit') {
			return 'Bye';
		}
		return null;
	}
}

function r($string, $return = false)
{
	return print_r($string, $return);
}
