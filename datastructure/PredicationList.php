<?php

namespace agentecho\datastructure;

/**
 * A list of predications, joined by logical AND:
 * name(o, "John") and below(o, t) and isa(t, Table)
 *
 * @author Patrick van Bergen
 */
class PredicationList
{
	private $predications = array();

	public function setPredications($predications)
	{
		$this->predications = $predications;
	}

	public function __toString()
	{
		return implode(' and ',  $this->predications);
	}
}
