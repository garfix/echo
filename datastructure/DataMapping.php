<?php

namespace agentecho\datastructure;

use agentecho\datastructure\RelationList;

/**
 * This structure maps one set of relations to another.
 *
 * http://en.wikipedia.org/wiki/Data_mapping
 *
 * @author Patrick van Bergen
 */
class DataMapping
{
	/** @var RelationList $PreList */
	private $PreList;

	/** @var RelationList $PostList */
	private $PostList;

	/**
	 * @param RelationList $PostList
	 */
	public function setPostList($PostList)
	{
		$this->PostList = $PostList;
	}

	/**
	 * @return RelationList
	 */
	public function getPostList()
	{
		return $this->PostList;
	}

	/**
	 * @param RelationList $PreList
	 */
	public function setPreList(RelationList $PreList)
	{
		$this->PreList = $PreList;
	}

	/**
	 * @return RelationList
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
