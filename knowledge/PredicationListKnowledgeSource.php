<?php

namespace agentecho\knowledge;

use agentecho\datastructure\PredicationList;
use \agentecho\phrasestructure\Sentence;
use agentecho\datastructure\Variable;
use agentecho\datastructure\Constant;

/**
 * @author Patrick van Bergen
 */
class PredicationListKnowledgeSource extends KnowledgeSource
{
	private $data;

	public function __construct(PredicationList $PredicationList)
	{
		$data = array();

		foreach ($PredicationList->getPredications() as $Predication) {
			$predicate = $Predication->getPredicate();
			$arguments = array();

			foreach ($Predication->getArguments() as $Argument) {
				if ($Argument instanceof Variable) {
					$arguments[] = $Argument->getValue();
				} elseif ($Argument instanceof Constant) {
					$arguments[] = $Argument->getName();
				} else {
					$arguments[] = $Argument;
				}
			}

			$data[$predicate][] = $arguments;
		}

		$this->data = $data;
	}

#todo I don't want these functions here

	public function isProperNoun($identifier) {}
	public function answerQuestion(Sentence $Sentence) {}
    public function checkQuestion(Sentence $Sentence) {}
	public function answer(PredicationList $Question) {}

	public function bind($predicate, array $arguments)
	{
		if (!isset($this->data[$predicate])) {

			return array();

		} else {

			// find all predications that match the predicate
			$predications = $this->data[$predicate];

			// within these predications find all of those that match the arguments
			$resultSets = array();
			foreach ($predications as $row) {

				$error = false;

				// go through all given arguments
				foreach ($arguments as $index => $value) {

					// a value of null matches anything
					if ($value !== null) {

						// if an argument was bound, it must match the field at the same position
						if ($value != $row[$index]) {
							$error = true;
						}
					}
				}

				if (!$error) {
					$resultSets[] = $row;
				}
			}

			return $resultSets;
		}
	}
}
