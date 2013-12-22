<?php

namespace agentecho\component;

use agentecho\datastructure\SentenceInformation;
use agentecho\exception\LexicalItemException;
use agentecho\grammar\Grammar;

/**
 * "Lexical analysis is the process of converting a sequence of characters into a sequence of tokens."
 *
 * http://en.wikipedia.org/wiki/Lexical_analysis
 */
class Lexer
{
	/**
	 * Analyses a raw $string and places the result in $Sentence.
	 *
	 * @param $string
	 * @param \agentecho\datastructure\SentenceInformation $Sentence
	 * @param \agentecho\grammar\Grammar $Grammar
	 */
	public function analyze($string, SentenceInformation $Sentence, Grammar $Grammar)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($string, $Sentence);

		// split words that should be treated as separate lexical items
		// since these glued-together words have different parts-of-speech
		$Sentence->setWords($this->unglue($Sentence->getWords(), $Grammar));

		// make words lowercase
		// glue together words that should form a single lexical item
#todo: split lowercasing and gluing
		$this->glue($Sentence, $Grammar);

		$this->recognize($Grammar, $Sentence->getLexicalItems());
	}

	/**
	 * Assigns the values $Sentence->input and $Sentence->words of $Phrase from the value of $input
	 * @param string $input This input can contain more than one natural language sentence.
	 * @param SentenceInformation $Sentence
	 */
	private function splitIntoWords($input, SentenceInformation $Sentence)
	{
		$terminator = null;

		$index = 0;
		$words = array();
		$isTerminator = false;
		while (($token = $this->getNextToken($input, $index, $isTerminator)) && !$isTerminator) {

			// sentence terminators
			if ($isTerminator) {
				$terminator = $token;
				break;
			}

			// quotes
			if (preg_match('/^"[^"]*"$/', $token, $matches)) {
				$token = substr($token, 1, strlen($token) - 2);
			}

			// skip comma's for now
			if ($token == ',') {
				continue;
			}

			$words[] = $token;
		}

		$Sentence->setWords($words);
		$Sentence->setSurfaceText(substr($input, 0, $index));
		$Sentence->setTerminator($terminator);
	}

	/**
	 * Split up words that contain different parts-of-speech
	 * and should therefore be treated as separate lexical items
	 *
	 * @param array $words An array of words
	 * @param Grammar $Grammar
	 * @return array Unglued words
	 */
	private function unglue(array $words, Grammar $Grammar)
	{
		$ungluedWords = array();

		foreach ($words as $word) {

			$ungluedWords = array_merge($ungluedWords, $Grammar->unglue($word));

		}

		return $ungluedWords;
	}

	/**
	 * Turns words into "lexical items" (words that can be used by the parser).
	 * This means:
	 * 1) words are put into lowercase
	 * 2) unknown words are grouped together (so that 'john carpenter' becomes a single item)
	 *
	 * Words are looked up (in this order)
	 * - in the current conversation context
	 * - in the grammar's lexicon
	 * - in the knowledge base
	 *
	 * todo coumpound words
	 *
	 * @param SentenceInformation $Sentence
	 * @param Grammar $Grammar
	 * @return bool
	 */
	private function glue(SentenceInformation $Sentence, Grammar $Grammar)
	{
		$lexicalItems = array();
		$words = $Sentence->getWords();
		$count = count($words);

		for ($i = 0; $i < $count; $i++) {

			$word = $words[$i];
			$lcWord = strtolower($word);

			// word is recognized?
			if ($Grammar->wordExists($lcWord)) {

				$lexicalItems[] = $lcWord;

			} else {

				$lexicalItems[] = $word;

			}
		}

		$Sentence->setLexicalItems($lexicalItems);
		return true;
	}

	/**
	 * Check if all $lexicalItems are known by the $Grammar
	 *
	 * @param Grammar $Grammar
	 * @param array $lexicalItems
	 * @throws \agentecho\exception\LexicalItemException
	 */
	private function recognize(Grammar $Grammar, array $lexicalItems)
	{
		foreach ($lexicalItems as $lexicalItem) {
			// check if known word
			if (!$Grammar->wordExists($lexicalItem)) {
				// check if numeral
				if (!is_numeric($lexicalItem)) {
					// check if proper noun
					if (!preg_match('/^[A-Z]/', $lexicalItem)) {
						// neither of these
						throw new LexicalItemException($lexicalItem);
					}
				}
			}
		}
	}

	private function getNextToken($string, &$index, &$isTerminator)
	{
		$length = strlen($string);
		if ($index == $length) {
			$isTerminator = true;
			return '';
		}

		$char = substr($string, $index, 1);

		switch ($char) {

			case '"':
				preg_match('/("[^"]*")/', $string, $matches, 0, $index);
				$token = $matches[1];
				break;
			case ',':
				$token = $char;
				break;
			case '.':
			case '!':
			case '?':
				preg_match('/([.?!]+)/', $string, $matches, 0, $index);
				$token = $matches[1];
				$isTerminator = true;
				break;
			default:
				preg_match('/([^\s",.!?]+)/', $string, $matches, 0, $index);
				$token = $matches[1];
				break;
		}

		$index += strlen($token);

		// strip whitespace
		while ($index < $length) {
			$c = $string[$index];
			if ($c == ' ') {
				$index++;
			} else {
				break;
			}
		}

		return $token;
	}
}