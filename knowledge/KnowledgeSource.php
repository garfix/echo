<?php

namespace agentecho\knowledge;

use \agentecho\phrasestructure\Sentence;

abstract class KnowledgeSource
{
	/**
	 * Returns true if $identifier is the name of some object in the knowledge source.
	 * @param string $identifier
	 * @return bool
	 */
	public abstract function isProperNoun($identifier);

#todo: uitfaseren
	public abstract function answerQuestionAboutObject($phraseSpecification, $sentenceType);
	public abstract function answerQuestionAboutObject2(Sentence $Sentence);

	public abstract function answerQuestion(Sentence $Sentence);
    public abstract function checkQuestion(Sentence $Sentence);
}