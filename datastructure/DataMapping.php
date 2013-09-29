<?php

namespace agentecho\datastructure;

use agentecho\datastructure\PredicationList;

/**
 * This structure maps one set of relations to another.
 *
 * http://en.wikipedia.org/wiki/Data_mapping
 *
 * @author Patrick van Bergen
 */
class DataMapping
{
	/** @var PredicationList $PreList */
	private $PreList;

	/** @var PredicationList $PostList */
	private $PostList;

//	/** @var AssignmentList $Transformations */
//	private $Transformations = null;

	/**
	 * @param PredicationList $PostList
	 */
	public function setPostList($PostList)
	{
		$this->PostList = $PostList;
	}

	/**
	 * @return PredicationList
	 */
	public function getPostList()
	{
		return $this->PostList;
	}

	/**
	 * @param PredicationList $PreList
	 */
	public function setPreList(PredicationList $PreList)
	{
		$this->PreList = $PreList;
	}

	/**
	 * @return PredicationList
	 */
	public function getPreList()
	{
		return $this->PreList;
	}

//	/**
//	 * @param \agentecho\datastructure\AssignmentList $Transformations
//	 */
//	public function setTransformations($Transformations)
//	{
//		$this->Transformations = $Transformations;
//	}
//
//	/**
//	 * @return \agentecho\datastructure\AssignmentList
//	 */
//	public function getTransformations()
//	{
//		return $this->Transformations;
//	}

	public function __toString()
	{
		return $this->PreList . ' => ' . $this->PostList;
//			(is_null($this->Transformations) ? '' : ', ' . $this->Transformations);
	}

	public function createClone()
	{
		$Clone = new GoalClause();
		$Clone->setPreList($this->PreList->createClone());
		$Clone->setPostList($this->PostList->createClone());
//		$Clone->setTransformations($this->Transformations->createClone());
		return $Clone;
	}
}
