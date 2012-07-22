<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\component\Lexer;
use \agentecho\component\Conversation;
use \agentecho\datastructure\SentenceContext;
use \agentecho\exception\ParseException;
use \agentecho\phrasestructure\Sentence;

class Parser
{
	/** @var array $grammars A list of grammars that are used to parse a sentence */
	private $grammars = array();

	/** @var Grammar $CurrentGrammar The grammar that will be tried first,
	 * and the grammar that was last used to successfully parse a sentence */
	private $CurrentGrammar = null;

	/** @var array $properNounIdentifiers A list of ProperNounIdentifier objects that are used to determine if a piece of text is a proper noun */
	private $properNounIdentifiers = array();

	public function setGrammars(array $grammars)
	{
		$this->grammars = $grammars;
	}

	public function setCurrentGrammar(Grammar $Grammar)
	{
		$this->CurrentGrammar = $Grammar;
	}

	/**
	 * @return Grammar The grammar that was last used to successfully parse a sentence.
	 */
	public function getCurrentGrammar()
	{
		return $this->CurrentGrammar;
	}

	public function setProperNounIdentifiers()
	{
		$this->properNounIdentifiers = array();
	}

	//public function parseSentenceGivenMultipleGrammars($input)
	public function parse($input)
	{
		$sentences = array();

		if (trim($input) == '') {
			return $sentences;
		}

		// create an array of grammars in which the current one is in the front
		if ($this->CurrentGrammar) {
			$grammars = array($this->CurrentGrammar);
		}
		foreach ($this->grammars as $Grammar) {
			if ($Grammar != $this->CurrentGrammar) {
				$grammars[] = $Grammar;
			}
		}

		$Exception = null;

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			$Sentence = new SentenceContext();

			try {
				$this->parseSentence($input, $Sentence, $Grammar);

				$sentences[] = $Sentence;
				$Sentence->setLanguage($Grammar->getLanguage());

				// update current language
				$this->CurrentGrammar = $Grammar;

				// now parse the rest of the input, if there is one
				// this code works either in ltr and rtl languages (not that i tried ;)
				$restInput = str_replace($Sentence->surfaceText, '', $input);
//				return array_merge($sentences, $this->parseSentenceGivenMultipleGrammars($restInput, $Conversation, $CurrentGrammar, $grammars));
				return array_merge($sentences, $this->parse($restInput));

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
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @return SentenceContext
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parseFirstLine($input)
	{
		$sentences = $this->parse($input);
		return $sentences ? $sentences[0] : false;
	}

	/**
	 * This function turns a line of text into structured meaning.
	 *
	 * @param string $text Raw input.
	 * @param array $context The roles that are currently active.
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	private function parseSentence($input, SentenceContext $Sentence, Grammar $Grammar)
	{
		// analyze words
		$Lexer = new Lexer();
		$Lexer->analyze($input, $Sentence, $Grammar, $this->properNounIdentifiers);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->lexicalItems);
		$Sentence->setPhraseSpecification($result['tree']);

		if (!$result['success']) {

			$E = new ParseException();
			$E->setLexicalItems($Sentence->lexicalItems, $result['lastParsedIndex'] - 1);

			throw $E;
		}

		$phraseSpecification = $Sentence->getPhraseSpecification();
		$Sentence->RootObject = $this->buildObjectStructure($phraseSpecification['features']['head']);
	}

	/**
	 * This function turns a phrase specification into an object structure.
	 * @param $phraseSpecification
	 * @return Entity
	 */
	private function buildObjectStructure(array $phraseSpecification)
	{
		if (isset($phraseSpecification['sentenceType'])) {
			$E = new Sentence();
		} elseif (isset($phraseSpecification['type'])) {
			$className = '\\agentecho\\phrasestructure\\' . ucfirst($phraseSpecification['type']);
			$E = new $className();
		}

		$attributeNames = array_keys($E->getAttributes());

		foreach ($phraseSpecification as $name => $value) {

			if (in_array($name, $attributeNames)) {

				$func = 'set' . ucfirst($name);

				$E->$func($value);

			} elseif (in_array(ucfirst($name), $attributeNames)) {

				$func = 'set' . $name;

				$Entity = $this->buildObjectStructure($value);
				$E->$func($Entity);

			}

		}

		return $E;
	}
}