<?php

require_once __DIR__ . '/language/LanguageProcessor.php';
require_once __DIR__ . '/language/DiscoursePlanner.php';

class ChatbotEcho
{
	/** There is only one agent */
	private static $instance = null;

	/** Data structures */
	private $declarativeMemory = array();
	private $workingMemory = array();

	/** Modules */
	private $LanguageProcessor;
	private $discoursePlanners = array();

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

	/**
	 * Parses $input into a series of Sentences
	 *
	 * @param string $input
	 * @return array An array of Sentence
	 */
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
	 * Starts a new conversation with a given user.
	 * A dialog manager is created for this conversation.
	 */
	public function startConversation($userId)
	{
		$this->addToWorkingMemory('context', 'speaker', $userId);
		$this->createDiscoursePlanner($userId);
	}

	/**
	 * Within the current conversation with the user, $input is processed and output is returned.
	 *
	 * @param string $userId
	 * @param string $input Human readable input
	 * @return string Human readable output
	 */
	public function interact($userId, $input)
	{
		if (!isset($this->discoursePlanners[$userId])) {
			$this->startConversation($userId);
		}

		return $this->discoursePlanners[$userId]->interact($input);
	}

	/**
	 * Ends and removes the conversation with the user.
	 *
	 * @param string $userId
	 */
	public function stopConversation($userId)
	{
		unset($this->discoursePlanners[$userId]);
	}

	private function createDiscoursePlanner($userId)
	{
		$this->discoursePlanners[$userId] = new DiscoursePlanner($userId, $this->LanguageProcessor, $this->workingMemory);
	}

	public function addToWorkingMemory($subject, $predicate, $object)
	{
		$this->workingMemory[$subject][$predicate] = $object;
	}


	/**
	 * Low-level: enters $statment into declarative memory.
	 *
	 * @param array $statement three parameters: subject, predicate, object
	 */
	public function tell(array $statement)
	{
		$this->declarativeMemory[] = $statement;
	}

	/**
	 * Low-level: queries declarative memory for $pattern
	 *
	 * @param $pattern
	 * @return mixed|null
	 */
	public function ask(array $triple)
	{
		$answer = null;

		$index = -1;
		foreach ($triple as $index => $word) {
			if ($triple[$index][0] == '?') {
				break;
			}
		}

		foreach ($this->declarativeMemory as $statement) {
			foreach ($statement as $i => $part) {
				if ($triple[$i][0] == '?') {
					$answer = $part;
				} else {
					if ($triple[$i] != $part) {
						continue;
					}
				}
			}
			return $answer;
		}

		return null;
	}

	/**
	 * High-level: reply to the human readable $question with a human readable sentence
	 *
	 * @param string $question
	 * @return string The response
	 */
	public function answer($question)
	{
		$sentences = $this->parse($question);
		if ($sentences) {

			$Sentence = $sentences[0];

			if ($Sentence->interpretations[0]->structure == 'wh-subject-question') {

				// this is a question
				$semantics = $Sentence->interpretations[0]->semantics;

				// try to answer it
				foreach ($semantics as $triple) {
					$answer = $this->ask($triple);
					if ($answer) {
						$answerTriple = $this->bind($triple, $answer);

						$semantics = array($answerTriple);

						// generate a reply sentence
						return $this->LanguageProcessor->generate($semantics, $this->workingMemory);
					}
				}

				return "I don't know the answer";
			}
		}
		return "I don't understand";
	}

	/**
	 * Replaces a variable in a triple with a value
	 *
	 * @param array $unboundTriple
	 * @param string $answer
	 */
	private function bind($unboundTriple, $answer)
	{
		$boundTriple = array();

		foreach ($unboundTriple as $index => $word) {
			if ($word[0] == '?') {
				$boundTriple[] = $answer;
			} else {
				$boundTriple[] = $word;
			}
		}

		return $boundTriple;
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
