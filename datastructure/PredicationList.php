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

	public function getPredication($index)
	{
		return isset($this->predications[$index]) ? $this->predications[$index] : false;
	}

	/**
	 * Returns a name => name list of the names of the variables used in this list.
	 * @return array
	 */
	public function getVariableNames()
	{
		$names = array();

		foreach ($this->predications as $Predication) {
			$names = $names + $Predication->getVariableNames();
		}

		return $names;
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
