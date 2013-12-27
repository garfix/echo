<?php

namespace agentecho\component;

use agentecho\component\parser\MapParser;
use agentecho\datastructure\Constant;
use agentecho\datastructure\Map;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;
use agentecho\datastructure\FunctionApplication;
use agentecho\exception\DataMappingFailedException;

/**
 * This class maps logical relations to database relations, based on a declarative representation found in a map-file.
 *
 * @author Patrick van Bergen
 */
class DataMapper
{
	/** @var Map */
	private $Map = array();

	public function __construct($mapFile)
	{
		$string = file_get_contents($mapFile);
		$Parser = new MapParser();
		$this->Map = $Parser->parse($string);
	}

	/**
	 * Maps all relations in $Relations to their relation counterparts (also relations) in $mapFile.
	 * One or more relations may be mapped to zero or more relations.
	 * A single relation may be used in multiple mappings.
	 *
	 * If not all relations could be mapped, the function returns false.
	 *
	 * @param RelationList $Relations
	 * @param bool $iterate
	 * @param bool $allowUnprocessedRelations
	 * @return RelationList|false
	 */
	public function mapRelations(RelationList $Relations, $iterate = false, $allowUnprocessedRelations = false)
	{
		$newRelations = $this->performMapping($Relations, $allowUnprocessedRelations);

		if ($iterate) {

			// iterate until stable

			$same = false;

			while (!$same) {

				$iteratedRelations = $this->performMapping($newRelations, $allowUnprocessedRelations);

				$same = ($iteratedRelations == $newRelations);

				$newRelations = $iteratedRelations;

			}

		}

		return $newRelations;
	}

	private function performMapping(RelationList $Relations, $allowUnprocessedRelations)
	{
		/** @var $newRelations Store the newly created relations */
		$newRelations = array();
		/** @var $usedRelations Remember which relations are used (an array of indexes) */
		$usedRelations = array();

		/** @var $usedVariables A name => name list of all used variables in $Relations */
		$usedVariables = $Relations->getVariableNames();

		// go through each mapping
		foreach ($this->Map->getMappings() as $Mapping) {

			$preRelations = $Mapping->getPreList()->getRelations();

			/** @var array A zero-based indexed array that keeps track of all relations that match each precondition */
			$matchingRelationsPerPrecondition = array_fill(0, count($preRelations), array());

			// go through all preconditions of the mapping
			foreach ($preRelations as $conditionIndex => $PreRelation) {

				// check this precondition with all relations
				foreach ($Relations->getRelations() as $relationIndex => $Relation) {

					if (($result = $PreRelation->match($Relation)) !== false) {

						$matchingRelationsPerPrecondition[$conditionIndex][] = array(
							'result' => $result,
							'relationIndex' => $relationIndex
						);
					}
				}
			}

			// create all permutations for each of the precondition matches
			$permutations = Utils::createPermutations($matchingRelationsPerPrecondition);

			foreach ($permutations as $row) {

				// create a combined argument map and mark relations as used
				$argumentMap = array();
				$mergeSuccess = true;
				foreach ($row as $singleArgumentMap) {

					$argumentMap = $this->merge($argumentMap, $singleArgumentMap['result']);
					if ($argumentMap === false) {
						$mergeSuccess = false;
						break;
					}
				}

				if ($mergeSuccess) {

					// all pre-relations are marked as used
					foreach ($row as $singleArgumentMap) {
						$usedRelations[$singleArgumentMap['relationIndex']] = true;
					}

					// create new relations filled in with the values in argument map
					foreach ($Mapping->getPostList()->getRelations() as $PostRelation) {
						$NewRelation = $PostRelation->createClone();

						// replace the variables in the new relation
						$this->replaceVariables($NewRelation, $argumentMap, $usedVariables);

						$newRelations[] = $NewRelation;
					}
				}
			}
		}

		// which relations were not involved in the mapping?
		$missingRelations = array_diff_key($Relations->getRelations(), $usedRelations);

		if ($allowUnprocessedRelations) {

			// add the unused relations to the new ones

			$newRelations = array_merge($missingRelations, $newRelations);

		} else {

			// check if all relations are used

			if (count($usedRelations) != count($Relations->getRelations())) {

				throw new DataMappingFailedException(implode(', ', $missingRelations));
			}

		}

		$uniqueRelations = array_unique($newRelations);

		$NewRelationList = new RelationList();
		$NewRelationList->setRelations($uniqueRelations);
		return $NewRelationList;
	}

	private function replaceVariables($Term, array &$argumentMap, array &$usedVariables)
	{
		$newArguments = array();

		foreach ($Term->getArguments() as $Argument) {

			if ($Argument instanceof Variable) {
				$varName = $Argument->getName();

				// does the variable occur in the map?
				if (isset($argumentMap[$varName])) {
					// yes, use it
					if ($argumentMap[$varName] instanceof Constant) {
						$NewArgument = $argumentMap[$varName]->createClone();
					} else {
						$newName = $argumentMap[$varName]->getName();
						$Argument->setName($newName);
						$NewArgument = $Argument;
					}
				} else {
					// no, create a new variable
					$newName = RelationUtils::createUnusedVariableName($usedVariables);
					// make sure this variable will not be used again in this relationlist
					$usedVariables[$newName] = $newName;
					// use this same variable when used in this mapping rule
					$argumentMap[$varName] = new Variable($newName);
					$Argument->setName($newName);
					$NewArgument = $Argument;
				}


			} elseif ($Argument instanceof FunctionApplication) {

				$this->replaceVariables($Argument, $argumentMap, $usedVariables);
				$NewArgument = $Argument;

			} else {

				// Atom

				$NewArgument = $Argument;

			}

			$newArguments[] = $NewArgument;
		}

		$Term->setArguments($newArguments);
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
