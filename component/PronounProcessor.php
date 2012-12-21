<?php

namespace agentecho\component;

use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\ConversationContext;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;
use \agentecho\datastructure\PredicationList;
use \agentecho\datastructure\Predication;
use \agentecho\datastructure\Property;

/**
 * @author Patrick van Bergen
 */
class PronounProcessor
{
	/**
	 * Changes all pronouns in $Sentence to their referents.
	 * Updates $Context to store subjects and objects.
	 *
	 * @param Sentence $Sentence
	 * @param ConversationContext $Context
	 */
	public function replacePronounsByProperNouns(Sentence $Sentence, ConversationContext $Context)
	{
		$Subject = $Context->getSubject();

		$Sentence->visit(
			function(PhraseStructure $Phrase) use ($Subject)
			{
				if ($Phrase instanceof Entity) {

					/** @var Entity $Entity  */
					$Entity = $Phrase;

					// replace pronoun by proper noun
					if ($Entity->getCategory() == 'subject') {
						$Entity->setCategory(null);
						$Entity->setName($Subject->getName());
					}
				}
			});
	}

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
