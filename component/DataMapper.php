<?php

namespace agentecho\component;

use agentecho\component\SemanticStructureParser;
use agentecho\datastructure\DataMapping;
use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Variable;

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
	 * Maps all predications in $Predications to their relation counterparts in $mapFile.
	 * One or more predications may be mapped to zero or more relations.
	 *
	 * @param PredicationList $Predications
	 * @return PredicationList
	 */
	public function mapPredications(PredicationList $Predications)
	{
		$newPredications = array();

		/** @var DataMapping $Mapping */
		foreach ($this->map as $Mapping) {

			$matchAllArguments = true;
			$argumentMap = array();

			// go through all preconditions of the mapping
			foreach ($Mapping->getPreList()->getPredications() as $PrePredication) {

				$matchSingleArgument = false;

				// check this precondition with all predications
				/** @var Predication $PrePredication */
				foreach ($Predications->getPredications() as $Predication) {

					if (($result = $PrePredication->match($Predication)) !== false) {
						$matchSingleArgument = true;
						// integrate the predication map with the rest of the prepredication argument maps
						$argumentMap += $result;
						break;
					}
				}

				if (!$matchSingleArgument) {
					$matchAllArguments = false;
					break;
				}
			}

			if ($matchAllArguments) {

				// create new predications filled in with the values in argument map
				foreach ($Mapping->getPostList()->getPredications() as $PostPredication) {
					$NewPredication = $PostPredication->createClone();

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

		$NewPredicationList = new PredicationList();
		$NewPredicationList->setPredications($newPredications);
		return $NewPredicationList;
	}
}
