<?php

namespace agentecho\test\helpers;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSource2 extends TestKnowledgeSourceBase
{
	public function bind($predicate, array $arguments)
	{
		$predicates = array(
			'sibling' => array(
				array('arie', 'kobus'),
			),
			'ghi' => array(
				array('letter', 'word')
			)
		);

		return $this->bindPredicate($predicates, $predicate, $arguments);
	}
}
