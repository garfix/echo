<?php

abstract class KnowledgeSource
{
	/**
	 * Returns true if $identifier is the name of some object in the knowledge source.
	 * @param string $identifier
	 * @return bool
	 */
	public abstract function isProperNoun($identifier);
}