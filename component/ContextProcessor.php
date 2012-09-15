<?php

namespace agentecho\component;

use \agentecho\phrasestructure\Sentence;
use \agentecho\datastructure\ConversationContext;

/**
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
