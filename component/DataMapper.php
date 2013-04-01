<?php

namespace agentecho\component;

use agentecho\component\SemanticStructureParser;
use agentecho\datastructure\DataMapping;
use agentecho\datastructure\Map;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\component\Utils;
use agentecho\datastructure\FunctionApplication;
use agentecho\exception\DataMappingFailedException;

/**
 * This class maps logical predications to database relations, based on a declarative representation found in a map-file.
 *
 * @author Patrick van Bergen
 */
class DataMapper
{
	private $map = array();

	private $allowUnprocessedPredications = false;

	private $iterate = false;

	public function __construct($mapFile)
	{
		$string = file_get_contents($mapFile);
		$Parser = new SemanticStructureParser();

		$Term = $Parser->parse($string);
		if ($Term instanceof Map) {
			$this->map = $Term->getMappings();
		}
	}

	public function setAllowUnprocessedPredications($allow = true)
	{
		$this->allowUnprocessedPredications = $allow;
	}

	public function setIterate($iterate = true)
	{
		$this->iterate = $iterate;
	}

	/**
	 * Maps all predications in $Predications to their relation counterparts (also predications) in $mapFile.
	 * One or more predications may be mapped to zero or more relations.
	 * A single predication may be used in multiple mappings.
	 *
	 * If not all predications could be mapped, the function returns false.
	 *
	 * @param PredicationList $Predications
	 * @return PredicationList|false
	 */
	public function mapPredications(PredicationList $Predications)
	{
		$newPredications = $this->performMapping($Predications);

		if ($this->iterate) {

			// iterate until stable

			$same = false;

			while (!$same) {

				$iteratedPredications = $this->performMapping($newPredications);

				$same = ($iteratedPredications == $newPredications);

				$newPredications = $iteratedPredications;

			}

		}

		return $newPredications;
	}

	private function performMapping(PredicationList $Predications)
	{
		/** @var $newPredications Store the newly created predications */
		$newPredications = array();
		/** @var $usedPredications Remember which predications are used (an array of indexes) */
		$usedPredications = array();

		/** @var $usedVariables A name => name list of all used variables in $Predications */
		$usedVariables = $Predications->getVariableNames();

		// go through each mapping
		/** @var DataMapping $Mapping */
		foreach ($this->map as $Mapping) {

			$prePredications = $Mapping->getPreList()->getPredications();

			/** @var A zero-based indexed array that keeps track of all predications that match each precondition */
			$matchingPredicationsPerPrecondition = array_fill(0, count($prePredications), array());

			// go through all preconditions of the mapping
			foreach ($prePredications as $conditionIndex => $PrePredication) {


				// check this precondition with all predications
				/** @var Predication $PrePredication */
				foreach ($Predications->getPredications() as $predicationIndex => $Predication) {

					if (($result = $PrePredication->match($Predication)) !== false) {

						$matchingPredicationsPerPrecondition[$conditionIndex][] = array(
							'result' => $result,
							'predicationIndex' => $predicationIndex
						);
					}
				}
			}

			// create all permutations for each of the precondition matches
			$permutations = Utils::createPermutations($matchingPredicationsPerPrecondition);

			foreach ($permutations as $row) {

				// create a combined argument map and mark predications as used
				$argumentMap = array();
				$mergeSuccess = true;
				foreach ($row as $singleArgumentMap) {
					//$argumentMap += $singleArgumentMap['result'];

					$argumentMap = $this->merge($argumentMap, $singleArgumentMap['result']);
					if ($argumentMap === false) {
						$mergeSuccess = false;
						break;
					}

#todo this is too premature; the merge may still fail; place this behind the loop, for each of the results
					$usedPredications[$singleArgumentMap['predicationIndex']] = true;
				}

				if (!$mergeSuccess) {
					continue;
				}

				// create new predications filled in with the values in argument map
				foreach ($Mapping->getPostList()->getPredications() as $PostPredication) {
					$NewPredication = $PostPredication->createClone();

					// rename the variables in the new predication
					foreach ($NewPredication->getArguments() as $Argument) {

						$this->replaceVariables($Argument, $argumentMap, $usedVariables);



					}

					$newPredications[] = $NewPredication;
				}
			}
		}

		// which predications were not involved in the mapping?
		$missingPredications = array_diff_key($Predications->getPredications(), $usedPredications);

		if ($this->allowUnprocessedPredications) {

			// add the unused predications to the new ones

			$newPredications = array_merge($missingPredications, $newPredications);

		} else {

			// check if all predications are used

			if (count($usedPredications) != count($Predications->getPredications())) {

				throw new DataMappingFailedException(implode(', ', $missingPredications));
			}

		}

		$uniquePredications = array_unique($newPredications);

		$NewPredicationList = new PredicationList();
#todo: many true() results are combined into one; is this a problem? can it happen to other relations?
		$NewPredicationList->setPredications($uniquePredications);
		return $NewPredicationList;
	}

	private function replaceVariables($Term, array &$argumentMap, array &$usedVariables)
	{
		if ($Term instanceof Variable) {
			$varName = $Term->getName();

			// does the variable occur in the map?
			if (isset($argumentMap[$varName])) {
				// yes, use it
				$newName = $argumentMap[$varName]->getName();
			} else {
				// no, create a new variable
				$newName = PredicationUtils::createUnusedVariableName($usedVariables);
				// make sure this variable will not be used again in this predicationlist
				$usedVariables[$newName] = $newName;
				// use this same variable when used in this mapping rule
				$argumentMap[$varName] = new Variable($newName);
			}
			$Term->setName($newName);

		} elseif ($Term instanceof FunctionApplication) {

			foreach ($Term->getArguments() as $Argument) {
				$this->replaceVariables($Argument, $argumentMap, $usedVariables);
			}

		}
	}

	private function merge($oldValues, $newValues)
	{
		$merge = $oldValues;

		foreach ($newValues as $key => $value) {
			if (isset($oldValues[$key])) {

				if ($oldValues[$key] != $value) {
					// conflicting values
					return false;
				}

			} else {
				$merge[$key] = $value;
			}
		}

		return $merge;
	}
}
