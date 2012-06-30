<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\component\Conversation;
use \agentecho\datastructure\SentenceContext;
use \agentecho\exception\ParseException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Determiner;
use \agentecho\phrasestructure\Preposition;

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
		$Sentence->RootObject = $this->buildObjectStructure($Sentence->phraseSpecification['features']['head']);
	}

	/**
	 * This function turns a phrase specification into an object structure.
	 * @param $phraseSpecification
	 * @return Entity
	 */
	private function buildObjectStructure(array $phraseSpecification)
	{
#todo: geef sentence ook gewoon een 'type'
		if (isset($phraseSpecification['sentenceType'])) {
			$E = new Sentence();
			$type = $phraseSpecification['sentenceType'];
			$E->setType($type);

			if (isset($phraseSpecification['sem'])) {
				$E->setRelation($this->buildObjectStructure($phraseSpecification['sem']));
			}

			if (isset($phraseSpecification['voice'])) {
				$E->setVoice($phraseSpecification['voice']);
			}
		}

		if (isset($phraseSpecification['type'])) {
			switch ($phraseSpecification['type']) {
				case 'relation':

					$E = new Relation();
					$E->setPredicate($phraseSpecification['predicate']);

					$arguments = array();
					for ($i = 1; $i < 5; $i++) {
						if (isset($phraseSpecification['arg' . $i])) {
							$arguments[$i] = $this->buildObjectStructure($phraseSpecification['arg' . $i]);
						}
					}
					$E->setArguments($arguments);

					if (isset($phraseSpecification['modifier'])) {
						$E->setPreposition($this->buildObjectStructure($phraseSpecification['modifier']));
					}

					if (isset($phraseSpecification['tense'])) {
						$E->setTense($phraseSpecification['tense']);
					}
					break;

				case 'entity':
					$E = new Entity();

					if (isset($phraseSpecification['category'])) {
						$E->setCategory($phraseSpecification['category']);
					}

					if (isset($phraseSpecification['determiner'])) {
						$E->setDeterminer($this->buildObjectStructure($phraseSpecification['determiner']));
					}

					if (isset($phraseSpecification['name'])) {
						$E->setName($phraseSpecification['name']);
					}
//r($phraseSpecification);exit;
					if (isset($phraseSpecification['modifier'])) {
						$E->setPreposition($this->buildObjectStructure($phraseSpecification['modifier']));
					}

					if (isset($phraseSpecification['question'])) {
                        $E->setQuestion();
                    }

					break;

				case 'determiner':
					$E = new Determiner();
					$E->setCategory($phraseSpecification['category']);

					if (isset($phraseSpecification['question'])) {
                        $E->setQuestion();
                    }
                    if (isset($phraseSpecification['object'])) {
                        $E->setObject($this->buildObjectStructure($phraseSpecification['object']));
                    }

					break;

				case 'modifier':
					$E = new Preposition();
//r($phraseSpecification);exit;
					$E->setCategory($phraseSpecification['category']);
					if (isset($phraseSpecification['object'])) {
                        $E->setObject($this->buildObjectStructure($phraseSpecification['object']));
                    }

					break;

				default:

					$E = null;

			}
		}

		return $E;
	}
}