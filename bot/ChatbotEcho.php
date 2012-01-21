<?php

require_once __DIR__ . '/ChatbotSettings.php';
require_once __DIR__ . '/language/LanguageProcessor.php';

class ChatbotEcho
{
	/** There is only one agent */
	private static $instance = null;

	/** Data structures */
	private $declarativeMemory = array();
	private $workingMemory = array();

	/** Modules */
	private $LanguageProcessor;

	private function __construct()
	{
		$this->LanguageProcessor = new LanguageProcessor();
	}

	/**
	 * @return ChatbotEcho
	 */
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
	 * @return array An array of Sentences
	 */
	public function parse($input)
	{
		$sentences = $this->LanguageProcessor->parse($input, $this->workingMemory);

		return $sentences;
	}

	/**
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @return Sentence
	 */
	public function parseFirstLine($input)
	{
		$sentences = $this->parse($input);
		return $sentences ? $sentences[0] : false;
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

		$Sentence = $this->parseFirstLine($question);
		if ($Sentence) {

			$tree = $Sentence->syntaxTree['features']['head'];
//			$phraseStructure = $Sentence->phraseStructure;
			$phraseStructure = $tree['sem'];
//r($tree);

			//if (isset($phraseStructure['act'])) {
			//	$act = $phraseStructure['act'];
			if (isset($tree['sentenceType'])) {
				$act = $tree['sentenceType'];

				$phraseStructure['act'] = $act;

				$id = 0;

				self::addIds($phraseStructure, $id);
//r($act);
				if ($act == 'yes-no-question') {

					// since this is a yes-no question, check the statement
					$result = $this->check($phraseStructure);

					if ($result) {
						$answer = 'Yes.';
					} else {
						$answer = 'No.';
					}

				} elseif ($act == 'wh-non-subject-question') {

					$answer = $this->answerQuestionAboutObject($phraseStructure);

				}
			}
		}

		return $answer;
	}

	private static function addIds(&$structure, &$id)
	{
		$structure['id'] = ++$id;
		foreach ($structure as &$value) {
			if (is_array(($value))) {
				self::addIds($value, $id);
			}
		}
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
}

function r($string, $return = false)
{
	return print_r($string, $return);
}
