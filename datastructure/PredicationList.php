<?php

namespace agentecho\datastructure;

/**
 * A list of predications, joined by logical AND:
 * name(o, "John") and below(o, t) and isa(t, Table)
 *
 * @author Patrick van Bergen
 */
class PredicationList extends  Term
{
	private $predications = array();

	public function setPredications($predications)
	{
		$this->predications = $predications;
	}

	public function getPredications()
	{
		return $this->predications;
	}

	public function __toString()
	{
		return implode(' and ',  $this->predications);
	}

	public function createClone()
	{
		$Clone = new PredicationList();

		$predications = array();

		foreach ($this->predications as $Predication) {
			$predications[] = $Predication->createClone();
		}

		$Clone->setPredications($predications);
		return $Clone;
	}
}
