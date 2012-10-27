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
		$Clause = $Sentence->getClause();
		if ($Clause) {
			$DeepSubject = $Clause->getDeepSubject();
			if ($DeepSubject) {
				$ConversationContext->setSubject($DeepSubject);
			}
		}
	}
}
