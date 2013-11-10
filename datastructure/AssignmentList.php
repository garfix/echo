<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class AssignmentList extends SemanticStructure
{
	/** @var  Assignment[] */
	private $assignments;

	public function setAssignments($assignments)
	{
		$this->assignments = $assignments;
	}

	public function getAssignments()
	{
		return $this->assignments;
	}

	public function getFirstAssignment()
	{
		return (empty($this->assignments) ? false : reset($this->assignments));
	}

	public function __toString()
	{
		return '{' . implode('; ', $this->assignments) . '}';
	}
}
