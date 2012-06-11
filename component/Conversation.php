<?php

namespace agentecho\component;

use \agentecho\AgentEcho;
use \agentecho\Settings;
use \agentecho\grammar\Grammar;
use \agentecho\datastructure\Sentence;
use \agentecho\datastructure\SentenceBuilder;
use \agentecho\exception\ConfigurationException;
use \agentecho\exception\ParseException;

class Conversation
{
	/** @var Local memory store for the roles in the conversation */
	private $context = array();

	/** @var \AgentEcho The agent having the conversation */
	private $Echo;

	/** @var Start parsing in the last used grammar */
	private $CurrentGrammar = null;

	/**
	 * @param \agentecho\AgentEcho $Echo
	 * @throws ConfigurationException
	 */
	public function __construct(AgentEcho $Echo)
	{
		$this->Echo = $Echo;

		// set current grammar
		$availableGrammars = $Echo->getAvailableGrammars();

		if (empty($availableGrammars)) {
			throw new ConfigurationException(ConfigurationException::NO_GRAMMAR);
		}

		$Grammar = reset($availableGrammars);
		$this->setCurrentGrammar($Grammar);
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	public function isProperNoun($identifier)
	{
		// is $identifier a proper noun in the context?

		// is $identifier a proper noun in one of the knowledge sources?
		$success = $this->Echo->getKnowledgeManager()->isProperNoun($identifier);

		return $success;
	}

	/**
	 * The raw input is parsed into a syntactic / semantic structure.
	 * and these can only be corrected if the most likely grammatical class is known.
	 * The input may be from any of the known languages. While parsing we detect which one.
	 *
	 * @param string $input This input may consist of several sentences, if they are properly separated.
	 * @return array an array of Sentence objects
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parse($input)
	{
		$sentences = array();
		$availableGrammars = $this->Echo->getAvailableGrammars();

		if (trim($input) == '') {
			return $sentences;
		}

		// create an array of grammars in which the current one is in the front
		$grammars = array($this->CurrentGrammar);
		foreach ($availableGrammars as $Grammar) {
			if ($Grammar != $this->CurrentGrammar) {
				$grammars[] = $Grammar;
			}
		}

		$Exception = null;

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			$Sentence = new Sentence($this);

			try {

				$this->parseSentence($input, $Sentence, $Grammar);

				$sentences[] = $Sentence;
				$Sentence->language = $Grammar->getLanguage();

				// update current language
				$this->CurrentGrammar = $Grammar;

				// now parse the rest of the input, if there is one
				// this code works either in ltr and rtl languages (not that i tried ;)
				$restInput = str_replace($Sentence->surfaceText, '', $input);
				return array_merge($sentences, $this->parse($restInput, $this->context));

			} catch (\Exception $E) {

				// save the first exception
				if (!$Exception) {
					$Exception = $E;
				}

			}
		}

		// all grammars failed; throw the first exception
		throw $Exception;

		return $sentences;
	}


	/**
	 * This function turns a line of text into structured meaning.
	 *
	 * @param string $text Raw input.
	 * @param array $context The roles that are currently active.
	 * @return bool Succesful parse?
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	private function parseSentence($input, Sentence $Sentence, Grammar $Grammar)
	{
		// analyze words
		$Grammar->analyze($input, $Sentence);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->lexicalItems);
		$Sentence->phraseSpecification = $result['tree'];
//r($result);
		if (!$result['success']) {

			$E = new ParseException();
			$E->setLexicalItems($Sentence->lexicalItems, $result['lastParsedIndex'] - 1);

			throw $E;
		}

		$Sentence->RootObject = $this->buildObjectStructure($Sentence->phraseSpecification);

		return $result['success'];
	}

	/**
	 * This function turns a phrase specification into an object structure.
	 * @param $phraseSpecification
	 * @return
	 */
	private function buildObjectStructure($phraseSpecification)
	{

	}

	/**
	 * Turns an array of meaning representations into a sentence, in the current language.
	 *
	 * "We can characterize the input to a single invocation of an NLG system as a four-tuple <k, c, u, d>
	 * where k is the KNOWLEDGE SOURCE, c is the COMMUNICATIVE GOAL, u is the USER MODEL, and
	 * d is the DISCOURSE HISTORY" - Building natural language systems (p. 43)
	 *
	 * @param array $semantics - (part of the) COMMUNICATIVE GOAL
	 * @param $context - the DISCOURSE HISTORY
	 * @return string A human readable sentence, or false if an error occurred
	 */
	public function generate(array $phraseSpecification, $context)
	{
		$Sentence = new Sentence($this);
		$Sentence->phraseSpecification = $phraseSpecification;

		return $this->CurrentGrammar->generate($Sentence);
	}

	/**
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @return Sentence
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parseFirstLine($input)
	{
		$sentences = $this->parse($input);
		return $sentences ? $sentences[0] : false;
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

		try {

			$Sentence = $this->parseFirstLine($question);
			$features = $Sentence->phraseSpecification['features'];
//r($features);
			$id = 0;

			if (Settings::$addIds) {
				self::addIds($features, $id);
			}

			$head = $features['head'];
			$sem = $head['sem'];

			if (isset($head['sentenceType'])) {
				$sentenceType = $head['sentenceType'];

				// turn the question into an answer
				$features['head']['sentenceType'] = 'declarative';

				if ($sentenceType == 'yes-no-question') {

					// since this is a yes-no question, check the statement
					$result = $this->Echo->getKnowledgeManager()->check($sem, $sentenceType);

					if ($result) {
						$answer = 'Yes.';

						if (!$result) {
							$features['head']['negate'] = true;
						}
						$s = $this->generate($features, array());
						if ($s) {
							$answer .= ' ' . $s;
						}

					} else {
						$answer = 'No.';
					}

				} elseif ($sentenceType == 'wh-question') {

					$answer = $this->Echo->getKnowledgeManager()->answerQuestionAboutObject($sem, $sentenceType);

					// incorporate the answer in the original question
					if ($answer !== false) {

						#todo: this should be made more generic
//r($features);
						if (isset($features['head']['sem']['arg2']['determiner']['question'])) {
							unset($features['head']['sem']['arg2']['determiner']['question']);
							$features['head']['sem']['arg2']['determiner']['type'] = $answer;
//r($features);
							$sentence = $this->generate($features, array());
							if ($sentence) {
								$answer = $sentence;
							}
						}

					}

				} elseif ($sentenceType == 'imperative') {

					#todo Imperatives are not always questions
					$isQuestion = true;

					if ($isQuestion) {

//						r($sem);
						$answer = $this->Echo->getKnowledgeManager()->answerQuestionAboutObject($sem, $sentenceType);

						$values = array();

						foreach ($answer as $name) {
							$values[] = array(
								'name' => $name
							);
						}

						$features = array();
						$features['head']['sem'] = SentenceBuilder::buildConjunction($values);

//						r($features);

						$sentence = $this->generate($features, array());
						if ($sentence) {
							$answer = $sentence;
						}
					}

				} else {
					$answer = 'ok.';
				}
			}
		} catch (\Exception $E) {

			$answer = (string)$E;

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
}