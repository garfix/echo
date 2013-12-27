<?php

namespace agentecho\component;

use agentecho\datastructure\RelationList;
use agentecho\datastructure\Relation;
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
	public function replaceReferences(RelationList $RelationList)
	{
		$referents = array();

		// find the referenced properties
		/** @var Relation $Relation */
		foreach ($RelationList->getRelations() as $Relation) {
			if ($Relation->getPredicate() == 'reference') {
				$Referent = $Relation->getFirstArgument();
				$referents[] = $Referent;
			}
		}

		// collect referenced properties by S.subject
		$replacements = array();
		foreach ($RelationList->getRelations() as $Relation) {
			$arguments = $Relation->getArguments();
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
