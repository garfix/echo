<?php

namespace agentecho\test\helpers;

use \agentecho\knowledge\KnowledgeSource;
use \agentecho\phrasestructure\Sentence;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSourceBase extends KnowledgeSource
{
#todo I don't want these functions here

	public function isProperNoun($identifier) {}
	public function answerQuestion(Sentence $Sentence) {}
    public function checkQuestion(Sentence $Sentence) {}

	public function bind($predicate, array $arguments) {}

	/**
	 * Searches through $data to find all rows that match $arguments.
	 * @param array $data
	 * @param $arguments
	 * @return array
	 */
	protected function bindPredicate(array $data, $predicate, $arguments)
	{
		if (!isset($data[$predicate])) {

			return array();

		} else {

			// find all predications that match the predicate
			$predications = $data[$predicate];

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