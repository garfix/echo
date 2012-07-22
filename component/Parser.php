<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\component\Conversation;
use \agentecho\datastructure\SentenceContext;
use \agentecho\exception\ParseException;
use \agentecho\phrasestructure\Sentence;

class Parser
{
	public function parseSentenceGivenMultipleGrammars($input, Conversation $Conversation, Grammar &$CurrentGrammar, array $availableGrammars)
	{
		$sentences = array();

		if (trim($input) == '') {
			return $sentences;
		}

		// create an array of grammars in which the current one is in the front
		$grammars = array($CurrentGrammar);
		foreach ($availableGrammars as $Grammar) {
			if ($Grammar != $CurrentGrammar) {
				$grammars[] = $Grammar;
			}
		}

		$Exception = null;

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			$Sentence = new SentenceContext($Conversation);

			try {
				$this->parseSentence($input, $Sentence, $Grammar);

				$sentences[] = $Sentence;
				$Sentence->language = $Grammar->getLanguage();

				// update current language
				$CurrentGrammar = $Grammar;

				// now parse the rest of the input, if there is one
				// this code works either in ltr and rtl languages (not that i tried ;)
				$restInput = str_replace($Sentence->surfaceText, '', $input);
				return array_merge($sentences, $this->parseSentenceGivenMultipleGrammars($restInput, $Conversation, $CurrentGrammar, $grammars));

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
	 * @throws LexicalItemException
	 * @throws ParseException
	 */
	public function parseSentence($input, SentenceContext $Sentence, Grammar $Grammar)
	{
		// analyze words
		$Grammar->analyze($input, $Sentence);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->lexicalItems);
		$Sentence->phraseSpecification = $result['tree'];

		if (!$result['success']) {

			$E = new ParseException();
			$E->setLexicalItems($Sentence->lexicalItems, $result['lastParsedIndex'] - 1);

			throw $E;
		}
//r($Sentence->phraseSpecification['features']['head']);
//
//r($this->buildObjectStructure($Sentence->phraseSpecification['features']['head']));
//r($this->buildObjectStructure2($Sentence->phraseSpecification['features']['head']));
//exit;

//		$Sentence->RootObject =
		$Sentence->RootObject = $this->buildObjectStructure($Sentence->phraseSpecification['features']['head']);
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