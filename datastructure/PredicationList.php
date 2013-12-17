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
	/** @var Predication[] */
	private $predications = array();

	public function setPredications($predications)
	{
		$this->predications = $predications;
	}

	/**
	 * @param Predication $Predication
	 */
	public function addPredication(Predication $Predication)
	{
		$this->predications[] = $Predication;
	}

	/**
	 * @param Predication $Predication
	 */
	public function removePredication(Predication $Predication)
	{
		$new = array();

		foreach ($this->predications as $P) {
			if ($P != $Predication) {
				$new[] = $P;
			}
		}

		$this->predications = $new;
	}

	/**
	 * @return Predication[]
	 */
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
	 * @return Predication[] An array of predications
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
	 * @param null $arguments Optional arguments to be matched (don't care = null)
	 * @return Predication|false
	 */
	public function getPredicationByPredicate($predicate, $arguments = null)
	{
		$results = $this->getPredicationsByPredicate($predicate);
		if (empty($results)) {
			return false;
		} else {
			if ($arguments) {
				foreach ($results as $Result) {
					$found = true;
					foreach ($arguments as $i => $Argument) {
						if ($Argument !== null) {
							if ($Result->getArgument($i) != $Argument) {
								$found = false;
							}
						}
					}
					if ($found) {
						return $Result;
					}
				}
				return false;
			} else {
				return reset($results);
			}
		}
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
