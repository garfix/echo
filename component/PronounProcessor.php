<?php

namespace agentecho\component;

use agentecho\datastructure\PredicationList;
use agentecho\datastructure\Predication;
use agentecho\datastructure\Property;

/**
 * @author Patrick van Bergen
 */
class PronounProcessor
{
	/**
	 * Dummy interpretation for pronoun resolution on the semantic level.
	 * Replaces all properties predicated with reference() by S.subject
	 */
	public function replaceReferences(PredicationList $PredicationList)
	{
		$referents = array();

		// find the referenced properties
		/** @var Predication $Predication */
		foreach ($PredicationList->getPredications() as $Predication) {
			if ($Predication->getPredicate() == 'reference') {
				$Referent = $Predication->getFirstArgument();
				$referents[] = $Referent;
			}
		}

		// collect referenced properties by S.subject
		$replacements = array();
		foreach ($PredicationList->getPredications() as $Predication) {
			$arguments = $Predication->getArguments();
			foreach ($arguments as $Argument) {
				if ($Argument instanceof Property) {
					foreach ($referents as $Referent) {
						if ($Argument == $Referent) {
							$replacements[] = $Argument;
						}
					}
				}
			}
		}

		// replace them
		foreach ($replacements as $Property) {
			$Property->getObject()->setName('S');
			$Property->setName('subject');
		}
	}
}
