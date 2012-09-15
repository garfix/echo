<?php

namespace agentecho\component;

use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\ConversationContext;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;


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
}
