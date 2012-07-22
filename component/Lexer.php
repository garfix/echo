<?php

namespace agentecho\component;

use agentecho\datastructure\SentenceContext;
use \agentecho\exception\LexicalItemException;
use \agentecho\grammar\Grammar;

/**
 * "Lexical analysis is the process of converting a sequence of characters into a sequence of tokens."
 *
 * http://en.wikipedia.org/wiki/Lexical_analysis
 */
class Lexer
{
	const END_OF_LINE = '** EOL';
	const INDIGNATION = '** INDIGNATION';
	const ELLIPSIS = '*** ELLIPSIS';
	const UNKNOWN_TERMINATOR = '*** UNKNOWN TERMINATOR';

	/** The maximum number of words that a proper noun may take */
	const LONGEST_PROPER_NOUN = 7;

	/**
	 * Analyses a raw $string and places the result in $Sentence.
	 *
	 * @param $string
	 * @param $Sentence
	 * @throws LexicalItemException
	 */
	public function analyze($string, SentenceContext $Sentence, Grammar $Grammar, array $properNounIdentifiers)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($string, $Sentence);

		// split words that should be treated as separate lexical items
		// since these glued-together words have different parts-of-speech
		$Sentence->words = $this->unglue($Sentence->words, $Grammar);

		// make words lowercase
		// glue together words that should form a single lexical item
#todo: split lowercasing and gluing
		$this->glue($Sentence, $Grammar, $properNounIdentifiers);
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
		while (($token = $this->getNextToken($input, $index)) != self::END_OF_LINE) {

			// sentence terminators
			if (in_array($token, array('.', '!', '?', self::INDIGNATION, self::UNKNOWN_TERMINATOR))) {
				$terminator = $token;
				break;
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
	private function glue(SentenceContext $Sentence, Grammar $Grammar, array $properNounIdentifiers)
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

				$involvedWords = $this->findLongestProperNoun($Sentence, array_slice($words, $i, self::LONGEST_PROPER_NOUN), $Grammar, $properNounIdentifiers);

				if ($involvedWords === false) {

					$E = new LexicalItemException();
					$E->setWord($word);
					throw $E;

				} else {
					$properNoun = implode(' ', $involvedWords);
					$lexicalItems[] = $properNoun;
					$i += count($involvedWords) - 1;
				}
			}
		}

		$Sentence->lexicalItems = $lexicalItems;
		return true;
	}

	private function findLongestProperNoun(SentenceContext $Sentence, $words, Grammar $Grammar, array $properNounIdentifiers)
	{
		while (count($words) > 0) {

			$properNoun = implode(' ', $words);

			foreach ($properNounIdentifiers as $Identifier) {
				if ($Identifier->isProperNoun($properNoun)) {
					return $words;
				}
			}

			if ($Grammar->isProperNoun($words)) {
				return $words;
			}

			// remove last word
			array_pop($words);
		}

		return false;
	}

	private function getNextToken($string, &$index)
	{
		$token = '';
		$length = strlen($string);

		if ($index == $length) {
			return self::END_OF_LINE;
		}

		// parse the actual characters
		while ($index < $length) {
			$c = $string[$index];
			if (strpos('.,?! ', $c) !== false) {
				break;
			}
			$token .= $c;
			$index++;
		}

		// parse comma's, points, etc
		if ($token === '') {
			while ($index < $length) {
				$c = $string[$index];
				if (strpos('.,?!', $c) === false) {
					break;
				}
				$token .= $c;
				$index++;
			}

			// turn combinations into tokens
			$len = strlen($token);
			if ($len > 1) {
		 		if (substr_count($token, '.') == $len) {
		 			$token = self::ELLIPSIS;
		 		} elseif (substr_count($token, ',') == $len) {
		 			$token = ',';
		 		} else {
					$apoCount = substr_count($token, '!');
					$questionCount = substr_count($token, '?');
					if ($apoCount > 1 || $questionCount > 1 || ($apoCount && $questionCount)) {
						$token = self::INDIGNATION;
					} else {
						$token = self::UNKNOWN_TERMINATOR;
					}
		 		}
			}
		}

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