<?php

namespace agentecho\test\helpers;

use \agentecho\datastructure\Predication;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSource2 extends TestKnowledgeSourceBase
{
	public function bind(Predication $Predication)
	{
		$predicate = $Predication->getPredicate();
		$arguments = $Predication->getArguments();

		$predicates = array(
			'sibling' => array(
				array('arie', 'kobus'),
			),
			'ghi' => array(
				array('letter', 'word')
			)
		);

		if (isset($predicates[$predicate])) {
			return $this->bindPredicate($predicates[$predicate], $arguments);
		} else {
			return array();
		}
	}

}
