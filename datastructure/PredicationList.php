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
	 * Returns all predications in this list with a given predicate.
	 *
	 * @param $predicate
	 * @return array An array of predications
	 */
	public function getPredicationsByPredicate($predicate)
	{
		$results = array();

		foreach ($this->predications as $Predication) {
			if ($Predication->getPredicate() == $predicate) {
				$results[] = $Predication;
			}
		}

		return $results;
	}

	/**
	 * Returns the first found predication with a given predicate.
	 *
	 * @param Predication|false $predicate
	 */
	public function getPredicationByPredicate($predicate)
	{
		$results = $this->getPredicationsByPredicate($predicate);
		return empty($results) ? false : reset($results);
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
