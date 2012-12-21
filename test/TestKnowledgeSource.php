<?php

namespace agentecho\test;

use \agentecho\knowledge\KnowledgeSource;
use \agentecho\datastructure\Predication;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSource extends KnowledgeSource
{
#todo I don't want these functions here

	public function isProperNoun($identifier) {}
	public function answerQuestion(Sentence $Sentence) {}
    public function checkQuestion(Sentence $Sentence) {}

	public function bind(Predication $Predication)
	{
		$predicate = $Predication->getPredicate();
		$arguments = $Predication->getArguments();

		if ($predicate == 'son') {

			$sons = array(
				array('sjoerd', 'kees'),
				array('jan', 'piet'),
				array('piet', 'otto'),
				array('peter', 'sjoerd'),
			);

			return $this->bindPredicate($sons, $arguments);
		}

		return array();
	}

	private function bindPredicate(array $data, $arguments)
	{
		$bindings = array();

		foreach ($data as $row) {

			$binding = array();
			$error = false;

			foreach ($arguments as $index => $Argument) {

				if ($Argument instanceof Variable) {
					$Variable = $Argument;
					$value = $Variable->getValue();
					$name = $Variable->getName();
					if ($value === null) {
						$binding[$name] = $row[$index];
					} elseif ($value != $row[$index]) {
						$error = true;
					}
				}
			}

			if (!$error) {
				$bindings[] = $binding;
			}
		}

		return $bindings;
	}
}
