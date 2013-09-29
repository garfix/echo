<?php

namespace agentecho\component;

use agentecho\datastructure\SentenceContext;
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
	 * @param $Sentence
	 * @throws LexicalItemException
	 */
	public function analyze($string, SentenceContext $Sentence, Grammar $Grammar)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($string, $Sentence);

		// split words that should be treated as separate lexical items
		// since these glued-together words have different parts-of-speech
		$Sentence->words = $this->unglue($Sentence->words, $Grammar);

		// make words lowercase
		// glue together words that should form a single lexical item
#todo: split lowercasing and gluing
		$this->glue($Sentence, $Grammar);
	}

	/**
	 * Assigns the values $Sentence->input and $Sentence->words of $Phrase from the value of $input
	 * @param string $input This input can contain more than one natural language sentence.
	 * @param SentenceContext $Sentence
	 */
	private function splitIntoWords($input, SentenceContext $Sentence)
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

		$Sentence->words = $words;
		$Sentence->surfaceText = substr($input, 0, $index);
		$Sentence->terminator = $terminator;
	}

	/**
	 * Split up words that contain different parts-of-speech
	 * and should therefore be treated as separate lexical items
	 *
	 * @param array $words An array of words
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
	 * @param SentenceContext $Sentence
	 * @throws LexicalItemException
	 */
	private function glue(SentenceContext $Sentence, Grammar $Grammar)
	{
		$lexicalItems = array();
		$words = $Sentence->words;
		$count = count($Sentence->words);

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

		$Sentence->lexicalItems = $lexicalItems;
		return true;
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