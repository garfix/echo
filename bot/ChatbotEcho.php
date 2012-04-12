<?php

require_once __DIR__ . '/ChatbotSettings.php';
require_once __DIR__ . '/language/LanguageProcessor.php';

/**
 * Basic principles:
 * - Easy to learn and use
 * - Fast
 */
class ChatbotEcho
{
	/** There is only one agent */
	private static $instance = null;

	/** Data structures */
	private $declarativeMemory = array();
	private $workingMemory = array();
	private $knowledgeSources = array();

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

	public function addKnowledgeSource(KnowledgeSource $KnowledgeSource)
	{
		$this->knowledgeSources[] = $KnowledgeSource;
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

			$features = $Sentence->syntaxTree['features'];

			$id = 0;

			self::addIds($features, $id);


			$tree = $features['head'];
//			$phraseStructure = $Sentence->phraseStructure;
			$phraseStructure = $tree['sem'];
//r($phraseStructure);

			//if (isset($phraseStructure['act'])) {
			//	$act = $phraseStructure['act'];
			if (isset($tree['sentenceType'])) {
				$act = $tree['sentenceType'];

				$phraseStructure['act'] = $act;

				if ($act == 'yes-no-question') {

					// since this is a yes-no question, check the statement
					$result = $this->check($phraseStructure);

					$features['head']['sentenceType'] = 'declarative';
					if ($result) {
						$answer = 'Yes.';

						if (!$result) {
							$features['head']['negate'] = true;
						}
						$s = $this->LanguageProcessor->generate($features, array());
						if ($s) {
							$answer .= ' ' . $s;
						}

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
		if (!isset($structure['id'])) {
			$structure['id'] = ++$id;
		} else {
			foreach ($structure['id'] as $k => $v) {
				$id = $k;
				break;
			}
			$structure['id'] = $id;
		}
		foreach ($structure as &$value) {
			if (is_array(($value))) {
				self::addIds($value, $id);
			}
		}
	}


	private function check($phraseStructure)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->check($phraseStructure);
			if ($result !== false) {
				return $result;
			}
		}

		return false;
	}

	private function answerQuestionAboutObject($phraseStructure)
	{
		foreach ($this->knowledgeSources as $KnowledgeSource) {
			$result = $KnowledgeSource->answerQuestionAboutObject($phraseStructure);
			if ($result !== false) {
				return $result;
			}
		}

		return false;
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
