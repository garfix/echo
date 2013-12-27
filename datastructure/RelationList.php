<?php

namespace agentecho\datastructure;

/**
 * A list of relations, joined by logical AND:
 * name(o, "John") and below(o, t) and isa(t, Table)
 *
 * @author Patrick van Bergen
 */
class RelationList extends  Term
{
	/** @var Relation[] */
	private $relations = array();

	public function setRelations($relations)
	{
		$this->relations = $relations;
	}

	/**
	 * @param Relation $Relation
	 */
	public function addRelation(Relation $Relation)
	{
		$this->relations[] = $Relation;
	}

	/**
	 * @param Relation $Relation
	 */
	public function removeRelation(Relation $Relation)
	{
		$new = array();

		foreach ($this->relations as $P) {
			if ($P != $Relation) {
				$new[] = $P;
			}
		}

		$this->relations = $new;
	}

	/**
	 * @return Relation[]
	 */
	public function getRelations()
	{
		return $this->relations;
	}

	public function getRelation($index)
	{
		return isset($this->relations[$index]) ? $this->relations[$index] : false;
	}

	/**
	 * Returns all relations in this list with a given predicate.
	 *
	 * @param $predicate
	 * @return Relation[] An array of relations
	 */
	public function getRelationsByPredicate($predicate)
	{
		$results = array();

		foreach ($this->relations as $Relation) {
			if ($Relation->getPredicate() == $predicate) {
				$results[] = $Relation;
			}
		}

		return $results;
	}

	/**
	 * Returns the first found relation with a given predicate.
	 *
	 * @param Relation|false $predicate
	 * @param null $arguments Optional arguments to be matched (don't care = null)
	 * @return Relation|false
	 */
	public function getRelationByPredicate($predicate, $arguments = null)
	{
		$results = $this->getRelationsByPredicate($predicate);
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

		foreach ($this->relations as $Relation) {
			$names = $names + $Relation->getVariableNames();
		}

		return $names;
	}

	public function __toString()
	{
		return implode(' and ',  $this->relations);
	}

	public function createClone()
	{
		$Clone = new RelationList();

		$relations = array();

		foreach ($this->relations as $Relation) {
			$relations[] = $Relation->createClone();
		}

		$Clone->setRelations($relations);
		return $Clone;
	}
}
