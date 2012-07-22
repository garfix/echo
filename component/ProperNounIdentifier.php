<?php

namespace agentecho\component;

interface ProperNounIdentifier
{
	/**
	 * Returns true if $text is a proper noun
	 * @param $text
	 * @return bool
	 */
	public function isProperNoun($text);
}