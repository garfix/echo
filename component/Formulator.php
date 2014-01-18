<?php

namespace agentecho\component;

use agentecho\component\events\EventSender;
use agentecho\component\parser\FormulatorRulesParser;
use agentecho\datastructure\Constant;
use agentecho\datastructure\FormulationRule;
use agentecho\datastructure\FormulationRules;
use agentecho\datastructure\Variable;
use agentecho\exception\FormulatorException;
use agentecho\grammar\Grammar;
use agentecho\datastructure\RelationList;

/**
 * This class turns a set of relations that for a question into a set of relations that form the answer.
 * The answer itself is provided by one of the knowledge providers of the knowledge manager,
 *
 * @author Patrick van Bergen
 */
class Formulator
{
	use EventSender;

	/** @var  string  */
	private $formulationRulesFile;

	/** @var FormulationRules */
	private $FormulationRules = null;

	public function __construct($formulationsFile)
	{
		$this->formulationRulesFile = $formulationsFile;
	}

	/**
	 * @param RelationList $Question
	 * @param array $answerBindings
	 * @throws FormulatorException
	 * @return RelationList|false
	 */
	public function formulate(RelationList $Question, array $answerBindings)
	{
		$FormulationRules = $this->getFormulationRules();

		// find a matching rule
		$variableBindings = array();
		$Rule = $this->findRule($FormulationRules, $Question, $variableBindings);

		if ($Rule) {

			// apply this rule to modify the $Question and create an answer
			$Answer = $this->createAnswerRelations($Question, $Rule, $answerBindings, $variableBindings);

			return $Answer;

		} else {
			throw new FormulatorException();
		}
	}

	/**
	 * @return FormulationRules
	 */
	private function getFormulationRules()
	{
		if ($this->FormulationRules === null) {

			$Parser = new FormulatorRulesParser();
			$formulationRules = file_get_contents($this->formulationRulesFile);
			$this->FormulationRules = $Parser->parse($formulationRules);
		}
		return $this->FormulationRules;
	}

	/**
	 * @param FormulationRules $FormulationRules
	 * @param RelationList $Relations
	 * @param array $variableBindings
	 * @return FormulationRule
	 */
	private function findRule(FormulationRules $FormulationRules, RelationList $Relations, array &$variableBindings)
	{
		$propertyBindings = array();

		foreach ($FormulationRules->getRules() as $Rule) {
			if (Matcher::matchRelationListAgainstRelationList($Rule->getCondition(), $Relations, $propertyBindings, $variableBindings)) {
				return $Rule;
			}
		}
	}

	/**
	 * @param RelationList $Question
	 * @param FormulationRule $Rule
	 * @param array $answerBindings
	 * @param array $ruleBindings
	 * @return RelationList
	 */
	private function createAnswerRelations(RelationList $Question, FormulationRule $Rule, array $answerBindings, array $ruleBindings)
	{
		$Answer = $Question->createClone();

		$bindings = $ruleBindings;

#note: we're presuming that all answers are in the first row of the answer bindings

		foreach ($ruleBindings as $name => $RuleBinding) {

			if ($RuleBinding instanceof Variable) {
				$variableName = $RuleBinding->getName();

				if (isset($answerBindings[0][$variableName])) {

					$bindingValue = $answerBindings[0][$variableName];
					$bindings[$name] = new Constant($bindingValue);
				}
			}
		}

		// add relations
		$AnswerAppend = Binder::bindRelationsVariables($Rule->getAddList(), $bindings);
		$Answer->appendRelations($AnswerAppend->getRelations());

		return $Answer;
	}
}
