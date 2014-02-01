<?php

namespace agentecho\component;

use agentecho\component\events\EventSender;
use agentecho\component\parser\FormulatorRulesParser;
use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\FormulationRule;
use agentecho\datastructure\FormulationRules;
use agentecho\datastructure\FunctionApplication;
use agentecho\datastructure\RelationTemplate;
use agentecho\datastructure\Variable;
use agentecho\exception\FormulatorException;
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
		// combine the rule bindings with the answer bindings
		$bindings = $ruleBindings;
		foreach ($ruleBindings as $name => $RuleBinding) {

			if ($RuleBinding instanceof Variable) {
				$variableName = $RuleBinding->getName();

				foreach ($answerBindings as $answerBinding) {

					if (isset($answerBinding[$variableName])) {

						$bindingValue = $answerBinding[$variableName];
						$val = (is_numeric($bindingValue) ? new Atom($bindingValue) : new Constant($bindingValue));

						if ($Rule->getType() == FormulationRule::TYPE_MULTIPLE) {
							if (!isset($bindings[$name]) || !is_array($bindings[$name])) {
								$bindings[$name] = array();
							}
							$bindings[$name][] = $val;
						} else {
							$bindings[$name] = $val;
						}
					}
				}
			}
		}

		// get the relations of rule and bind them to the new variables
		$AnswerRelations = Binder::bindRelationsVariables($Rule->getProduction(), $bindings);

		// expand the relation templates
		$AnswerRelations = $this->expandRelationTemplates($AnswerRelations, $bindings, $Question);

		// execute the functions in the new relations
		$AnswerRelations = $this->executeFunctions($AnswerRelations, $bindings);

		return $AnswerRelations;
	}

	/**
	 * Expands a new list of $Relations, but with with all relation template expanded.
	 *
	 * @param RelationList $Relations
	 * @param array $bindings
	 * @param \agentecho\datastructure\RelationList $Question
	 * @return RelationList
	 */
	private function expandRelationTemplates(RelationList $Relations, array $bindings, RelationList $Question)
	{
		$NewList = new RelationList();

		foreach ($Relations->getRelations() as $Relation) {

			if ($Relation instanceof RelationTemplate) {

				$NewInstances = $this->applyTemplate($Relation, $bindings, $Question);
				$NewList->appendRelationList($NewInstances);

			} else {
				$NewList->addRelation($Relation);
			}

		}

		return $NewList;
	}

	/**
	 * Performs the $Template's function to its arguments,
	 * and returns a list of relations.
	 *
	 * @param RelationTemplate $Template
	 * @param array $bindings
	 * @param RelationList $Question
	 * @return RelationList
	 */
	private function applyTemplate(RelationTemplate $Template, array $bindings, RelationList $Question)
	{
		/** @var FunctionApplication $FunctionApplication */
		$FunctionApplication = $Template->getFirstArgument();

		$arguments = array();
		foreach ($FunctionApplication->getArguments() as $FormalParameter) {
			$parameterName = $FormalParameter->getName();
# ok?
			if (isset($bindings[$parameterName])) {
				$arguments[$parameterName] = $bindings[$parameterName];
			} else {
				$arguments[$parameterName] = $FormalParameter;
			}
		}

		$Invoker = new FunctionInvoker();
		$Relations = $Invoker->applyTemplateFunctionApplication($FunctionApplication, $arguments, $Question);

		return $Relations;
	}

	/**
	 * Executes all functions in each of $Relation's arguments,
	 * and returns these same relations.
	 *
	 * @param RelationList $Relations
	 * @param array $bindings
	 * @return RelationList
	 */
	private function executeFunctions(RelationList $Relations, array $bindings)
	{
		foreach ($Relations->getRelations() as $Relation) {
			foreach ($Relation->getArguments() as $i => $Argument) {
				if ($Argument instanceof FunctionApplication) {

					$Invoker = new FunctionInvoker();
					$NewArgument = $Invoker->applyFunctionApplication($Argument, $bindings);
					$Relation->setArgument($i, $NewArgument);
				}
			}
		}

		return $Relations;
	}
}
