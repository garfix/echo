<?php

namespace agentecho\component;

use agentecho\datastructure\Sentence;
use \agentecho\exception\LexicalItemException;
use \agentecho\grammar\Grammar;

/**
 * "Lexical analysis is the process of converting a sequence of characters into a sequence of tokens."
 *
 * http://en.wikipedia.org/wiki/Lexical_analysis
 */
class Lexer
{
	const END_OF_LINE = '** EOL **';
	const INDIGNATION = '** INDIGNATION **';
	const ELLIPSIS = '*** ELLIPSIS ***';
	const UNKNOWN_TERMINATOR = '*** UNKNOWN TERMINATOR ***';

	const LONGEST_PROPER_NOUN = 7;

	/**
	 * Analyses a raw $string and places the result in $Sentence.
	 *
	 * @param $string
	 * @param $Sentence
	 * @throws LexicalItemException
	 */
	public function analyze($string, Sentence $Sentence, Grammar $Grammar)
	{
		// turns $input into $Sentence->words
		$this->splitIntoWords($string, $Sentence);

		// continue to work with words as they occur in the lexicon
		$this->makeLexicalItems($Sentence, $Grammar);
	}

	/**
	 * Assigns the values $Sentence->input and $Sentence->words of $Phrase from the value of $input
	 * @param string $input
	 * @param Sentence $Sentence
	 */
	private function splitIntoWords($input, Sentence $Sentence)
	{
		$terminator = null;

		$index = 0;
		$words = array();
		while (($word = $this->getNextWord($input, $index)) != self::END_OF_LINE) {

			// sentence terminators
			if (in_array($word, array('.', '!', '?', self::INDIGNATION, self::UNKNOWN_TERMINATOR))) {
				$terminator = $word;
				break;
			}

			// skip comma's for now
			if ($word == ',') {
				continue;
			}

			$words[] = $word;
		}

		$Sentence->words = $words;
		$Sentence->surfaceText = substr($input, 0, $index);
		$Sentence->terminator = $terminator;
	}

	/**
	 * Turns words into "lexical entries" (words that can be used by the parser).
	 * This means:
	 * 1) words are put into lowercase
	 * 2) unknown words are grouped together (so that 'john carpenter' becomes a single entry)
	 *
	 * Words are looked up (in this order)
	 * - in the current conversation context
	 * - in the grammar's lexicon
	 * - in the knowledge base
	 *
	 * todo coumpound words
	 *
	 * @param Sentence $Sentence
	 * @throws LexicalItemException
	 */
	private function makeLexicalItems(Sentence $Sentence, Grammar $Grammar)
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

				$involvedWords = $this->findLongestProperNoun($Sentence, array_slice($words, $i, self::LONGEST_PROPER_NOUN), $Grammar);

				if ($involvedWords === false) {

					$E = new LexicalItemException('Word not found: ' . $word);
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

	private function findLongestProperNoun(Sentence $Sentence, $words, Grammar $Grammar)
	{
		while (count($words) > 0) {

			$properNoun = implode(' ', $words);
			if ($Sentence->getConversation()->isProperNoun($properNoun)) {
				return $words;
			}

			if ($Grammar->isProperNoun($words)) {
				return $words;
			}

			// remove last word
			array_pop($words);
		}

		return false;
	}

	private function getNextWord($string, &$index)
	{
		$word = '';
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
			$word .= $c;
			$index++;
		}

		// parse comma's, points, etc
		if ($word === '') {
			while ($index < $length) {
				$c = $string[$index];
				if (strpos('.,?!', $c) === false) {
					break;
				}
				$word .= $c;
				$index++;
			}

			// turn combinations into tokens
			$len = strlen($word);
			if ($len > 1) {
		 		if (substr_count($word, '.') == $len) {
		 			$word = self::ELLIPSIS;
		 		} elseif (substr_count($word, ',') == $len) {
		 			$word = ',';
		 		} else {
					$apoCount = substr_count($word, '!');
					$questionCount = substr_count($word, '?');
					if ($apoCount > 1 || $questionCount > 1 || ($apoCount && $questionCount)) {
						$word = self::INDIGNATION;
					} else {
						$word = self::UNKNOWN_TERMINATOR;
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

		return $word;
	}
}