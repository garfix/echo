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

	public function __toString()
	{
		return $this->PreList . ' => ' . $this->PostList;
	}
}
