<?php

require_once __DIR__ . '/ChatbotSettings.php';
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
		$answer = '';

		$sentences = $this->parse($question);
		foreach ($sentences as $Sentence) {

			$phraseStructure = $Sentence->interpretations[0]->phraseStructure;
//r($phraseStructure['act']);
			if (isset($phraseStructure['act'])) {
				$act = $phraseStructure['act'];

				if ($act == 'yes-no-question') {

					// since this is a yes-no question, check the statement
					$result = $this->check($phraseStructure);

					if ($result) {
						$answer .= 'Yes.';
					} else {
						$answer .= 'No.';
					}

				} elseif ($act == 'question-about-object') {

					$answer .= $this->answerQuestionAboutObject($phraseStructure);

				}
			}
		}

		return $answer;
	}

	private function check($phraseStructure)
	{
		require_once(__DIR__ . '/knowledge_source/DBPedia.php');

		$DBPedia = new DBPedia();
		return $DBPedia->check($phraseStructure);
	}

	private function answerQuestionAboutObject($phraseStructure)
	{
		require_once(__DIR__ . '/knowledge_source/DBPedia.php');

		$DBPedia = new DBPedia();
		return $DBPedia->answerQuestionAboutObject($phraseStructure);
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
