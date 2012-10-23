<?php

namespace agentecho\component;

use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\ConversationContext;

/**
 * This processor updates the conversation context with the latest sentences.
 *
 * @author Patrick van Bergen
 */
class ContextProcessor
{
	public function updateSubject(Sentence $Sentence, ConversationContext $ConversationContext)
	{
		$Relation = $Sentence->getRelation();
		if ($Relation) {
			$Arg1 = $Relation->getArgument1();
			if ($Arg1) {
				$ConversationContext->setSubject($Arg1);
			}
		}
	}
}
