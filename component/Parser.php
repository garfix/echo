<?php

namespace agentecho\component;

use \agentecho\grammar\Grammar;
use \agentecho\datastructure\SentenceContext;
use \agentecho\exception\ParseException;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\phrasestructure\Relation;
use \agentecho\phrasestructure\Determiner;

class Parser
{
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
			$type = $phraseSpecification['sentenceType'];
			$E->setType($type);

			if (isset($phraseSpecification['sem'])) {
				$E->setRelation($this->buildObjectStructure($phraseSpecification['sem']));
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

				default:

					$E = null;

			}
		}

		return $E;
	}
}