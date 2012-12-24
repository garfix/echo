<?php

namespace agentecho\test\helpers;

use \agentecho\knowledge\KnowledgeSource;
use \agentecho\datastructure\Predication;
use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\Variable;
use agentecho\datastructure\Constant;

/**
 * @author Patrick van Bergen
 */
class TestKnowledgeSourceBase extends KnowledgeSource
{
#todo I don't want these functions here

	public function isProperNoun($identifier) {}
	public function answerQuestion(Sentence $Sentence) {}
    public function checkQuestion(Sentence $Sentence) {}

	public function bind(Predication $Predication) {}

	protected function bindPredicate(array $data, $arguments)
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
				} elseif ($Argument instanceof Constant) {

					$Constant = $Argument;
					$name = $Constant->getName();

					if ($name != $row[$index]) {
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
