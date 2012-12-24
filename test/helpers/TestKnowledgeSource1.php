<?php

namespace agentecho\test\helpers;

use \agentecho\datastructure\Predication;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSource1 extends TestKnowledgeSourceBase
{
	public function bind(Predication $Predication)
	{
		$predicate = $Predication->getPredicate();
		$arguments = $Predication->getArguments();

		$predicates = array(
			'son' => array(
				array('sjoerd', 'kees'),
				array('jan', 'piet'),
				array('piet', 'otto'),
				array('peter', 'sjoerd'),
			),
			'male' => array(
				array('kobus'),
			),
			'child' => array(
				array('marie', 'johanna'),
			),
			'female' => array(
				array('johanna'),
			),
			'parent' => array(
				array('anna', 'roberta')
			),
			'sister' => array(
				array('anna', 'johanna')
			)
		);

		if (isset($predicates[$predicate])) {
			return $this->bindPredicate($predicates[$predicate], $arguments);
		} else {
			return array();
		}
	}

}
