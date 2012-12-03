<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Assignment
{
	private $left;
	private $right;

	public function setLeft($left)
	{
		$this->left = $left;
	}

	public function getLeft()
	{
		return $this->left;
	}

	public function setRight($right)
	{
		$this->right = $right;
	}

	public function getRight()
	{
		return $this->right;
	}

	public function __toString()
	{
		return $this->left . ' = ' . $this->right;
	}
}
