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

	public abstract function answerQuestion(Sentence $Sentence);

    public abstract function checkQuestion(Sentence $Sentence);

	/**
	 * Provided with a predication that contains open variables, this function returns a list of
	 * variable-binding sets that match the predication.
	 *
	 * @param $predicate
	 * @param array $boundVariables
	 * @return array An array of result sets. Each result set is an array of values.
	 */
	public abstract function bind($predicate, array $arguments);
}