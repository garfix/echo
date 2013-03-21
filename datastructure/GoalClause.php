<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class GoalClause
{
	/** @var PredicationList $Goal */
	private $Goal;

	/** @var PredicationList $Means */
	private $Means;

	/**
	 * @param \agentecho\datastructure\PredicationList $Goal
	 */
	public function setGoal($Goal)
	{
		$this->Goal = $Goal;
	}

	/**
	 * @return \agentecho\datastructure\PredicationList
	 */
	public function getGoal()
	{
		return $this->Goal;
	}

	/**
	 * @param \agentecho\datastructure\PredicationList $Means
	 */
	public function setMeans($Means)
	{
		$this->Means = $Means;
	}

	/**
	 * @return \agentecho\datastructure\PredicationList
	 */
	public function getMeans()
	{
		return $this->Means;
	}

	public function __toString()
	{
		return $this->Goal . ' :- ' . $this->Means;
	}

	public function createClone()
	{
		$Clone = new GoalClause();
		$Clone->setGoal($this->Goal->createClone());
		$Clone->setMeans($this->Means->createClone());
		return $Clone;
	}
}
