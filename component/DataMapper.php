<?php

namespace agentecho\component;

use agentecho\component\SemanticStructureParser;
use agentecho\datastructure\DataMapping;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;
use agentecho\component\Utils;

/**
 * This class maps logical predications to database relations, based on a declarative representation found in a map-file.
 *
 * @author Patrick van Bergen
 */
class DataMapper
{
	private $map = array();

	public function __construct($mapFile)
	{
		$lines = file($mapFile);
		$Parser = new SemanticStructureParser();

		foreach ($lines as $line) {

			$Term = $Parser->parse($line);

			if ($Term instanceof DataMapping) {
				$this->map[] = $Term;
			}
		}
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
		/** @var $newPredications Store the newly created predications */
		$newPredications = array();
		/** @var $usedPredications Remember which predications are used (an array of indexes) */
		$usedPredications = array();

		// go through each mapping
		/** @var DataMapping $Mapping */
		foreach ($this->map as $Mapping) {

			/** @var A zero-based indexed array that keeps track of all predications that match each precondition */
			$matchingPredicationsPerPrecondition = array();

			// go through all preconditions of the mapping
			foreach ($Mapping->getPreList()->getPredications() as $conditionIndex => $PrePredication) {

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
				foreach ($row as $singleArgumentMap) {
					$argumentMap += $singleArgumentMap['result'];
					$usedPredications[$singleArgumentMap['predicationIndex']] = true;
				}

				// create new predications filled in with the values in argument map
				foreach ($Mapping->getPostList()->getPredications() as $PostPredication) {
					$NewPredication = $PostPredication->createClone();

					// rename the variables in the new predication
					foreach ($NewPredication->getArguments() as $Argument) {
						if ($Argument instanceof Variable) {
							$varName = $Argument->getName();
							if (isset($argumentMap[$varName])) {
								$newName = $argumentMap[$varName]->getName();
							} else {
								$newName = PredicationUtils::createUnusedVariableName($argumentMap);
							}
							$Argument->setName($newName);
						}
					}

					$newPredications[] = $NewPredication;
				}
			}
		}

		// check if all predications are used
		if (count($usedPredications) != count($Predications->getPredications())) {
			return false;
		}

		$NewPredicationList = new PredicationList();
		$NewPredicationList->setPredications($newPredications);
		return $NewPredicationList;
	}
}
