<?php

namespace agentecho\component;

use agentecho\datastructure\GenerationRule;

/**
 * When generating a sentence, this object keeps track of all rule / binding combinations
 * that have been used before.
 *
 * It also checks if a given set of bindings has been used before
 * This is done to prevent infinite recursion caused by performing the same rules again and again
 * (i.e NBar => NBar PP)
 *
 * Class RulesApplied
 * @package agentecho\component
 */
class RulesApplied implements BindingChecker
{
	/** @var  GenerationRule */
	private $GenerationRule;

	private $list = array();

	public function addAppliedRule(GenerationRule $Rule, array &$propertyBindings, array &$variableBindings)
	{
		$rule = (string)$Rule->getProduction();

		$this->list[$rule][] = array(
			'variableBindings' => $variableBindings,
			'propertyBindings' => $propertyBindings,
		);
	}

	public function setGenerationRule(GenerationRule $GenerationRule)
	{
		$this->GenerationRule = $GenerationRule;
	}

	public function check(array &$propertyBindings, array &$variableBindings)
	{
		$success = true;

		$rule = (string)$this->GenerationRule->getProduction();

		if (isset($this->list[$rule])) {

			foreach ($this->list[$rule] as $entry) {
				if ($entry['variableBindings'] == $variableBindings && $entry['propertyBindings'] == $propertyBindings) {
					$success = false;
					break;
				}
			}
		}

		return $success;
	}
}