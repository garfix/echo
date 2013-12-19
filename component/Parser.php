<?php

namespace agentecho\component;

use agentecho\exception\NoSemanticsAtTopLevelException;
use agentecho\grammar\Grammar;
use agentecho\datastructure\SentenceContext;
use agentecho\exception\ParseException;

/**
 * This class parses a surface text into a phrase specification.
 */
class Parser
{
	use EventSender;

	/** @var array $grammars A list of grammars that are used to parse a sentence */
	private $grammars = array();

	/** @var Grammar $CurrentGrammar The grammar that will be tried first,
	 * and the grammar that was last used to successfully parse a sentence */
	private $CurrentGrammar = null;

	public function addGrammar(Grammar $Grammar)
	{
		$grammars = $this->grammars;
		$grammars[] = $Grammar;

		$this->setGrammars($grammars);
	}

	public function setGrammars(array $grammars)
	{
		$this->grammars = $grammars;
		if (count($this->grammars) == 1) {
			$this->CurrentGrammar = reset($grammars);
		}
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

	public function parse($input)
	{
		$sentences = array();

		$this->send(new Event($input));

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
	 * @param $input
	 * @param \agentecho\datastructure\SentenceContext $Sentence
	 * @param \agentecho\grammar\Grammar $Grammar
	 * @throws \agentecho\exception\ParseException
	 * @throws \agentecho\exception\NoSemanticsAtTopLevelException
	 * @internal param string $text Raw input.
	 * @internal param array $context The roles that are currently active.
	 */
	private function parseSentence($input, SentenceContext $Sentence, Grammar $Grammar)
	{
		// analyze words
		$Lexer = new Lexer();
		$Lexer->analyze($input, $Sentence, $Grammar);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->lexicalItems);
		$Sentence->setPhraseSpecification($result['tree']);
		$Sentence->setSemantics($result['tree']['semantics']);

		if (!$result['success']) {

			throw new ParseException(implode(' ', array_slice($Sentence->lexicalItems, $result['lastParsedIndex'] - 1, 4)));
		}

		if ($result['tree']['semantics'] === null) {
			throw new NoSemanticsAtTopLevelException();
		}
	}
}