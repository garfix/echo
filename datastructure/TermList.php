<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class TermList extends SemanticStructure
{
	private $terms = array();

	public function setTerms($terms)
	{
		$this->terms = $terms;
	}

	public function getTerms()
	{
		return $this->terms;
	}

	public function __toString()
	{
		return implode(' ',  $this->terms);
	}
}
