<?php

namespace agentecho\component;

/**
 * A wrapper around a set of GoalClauses, loaded from file.
 *
 * @author Patrick van Bergen
 */
class RuleBase
{
	private $rules;

	private $index;

	public function __construct($fileName)
	{
		$this->rules = $this->parseRules($fileName);

		$this->indexRules();
	}

	private function parseRules($fileName)
	{
		$rules = array();

		$Parser = new SemanticStructureParser();
		$lines = file($fileName);

		foreach ($lines as $line) {
			$rules[] = $Parser->parse($line);
		}
		return $rules;
	}

	private function indexRules()
	{
		$index = array();

		foreach ($this->rules as $GoalClause) {
			$Goal = $GoalClause->getGoal();
			$predicate = $Goal->getPredicate();
			$argumentCount = count($Goal->getArguments());
			$index[$predicate][$argumentCount][] = $GoalClause;
		}

		$this->index = $index;
	}

	public function getRules()
	{
		return $this->rules;
	}

	public function getRulesByPredicate($predicate, $cardinality)
	{
		if (isset($this->index[$predicate][$cardinality])) {
			return $this->index[$predicate][$cardinality];
		} else {
			return array();
		}
	}
}
