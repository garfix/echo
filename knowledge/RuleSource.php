<?php

namespace agentecho\knowledge;

/**
 * @author Patrick van Bergen
 */
interface RuleSource
{
	public function getRulesByPredicate($predicate, $cardinality);
}
