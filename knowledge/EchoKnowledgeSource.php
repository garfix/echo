<?php

namespace agentecho\knowledge;

use \agentecho\phrasestructure\Sentence;
use agentecho\datastructure\PredicationList;

/**
 * @author Patrick van Bergen
 */
class EchoKnowledgeSource extends KnowledgeSource
{
	public function isProperNoun($identifier) {}

	public function answerQuestion(Sentence $Sentence) {}

    public function checkQuestion(Sentence $Sentence) {}

	public function answer(PredicationList $Question) {}

	/**
	 * Provided with a predication that contains open variables, this function returns a list of
	 * variable-binding sets that match the predication.
	 *
	 * @param $predicate
	 * @param array $boundVariables
	 * @return array An array of result sets. Each result set is an array of values.
	 */
	public function bind($predicate, array $arguments)
	{
		$variableSets = array();

		if ($predicate == 'diff_years') {

			list($fromDate, $toDate, $years) = $arguments;

			$From = new \DateTime($fromDate);
			$To = new \DateTime($toDate);
			$Period = $From->diff($To);
			$years = $Period->format('%y');

			$variableSets[] = array($fromDate, $toDate, $years);

		}

		return $variableSets;
	}
}
