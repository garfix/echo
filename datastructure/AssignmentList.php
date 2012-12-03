<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class AssignmentList
{
	private $assignments;

	public function setAssignments($assignments)
	{
		$this->assignments = $assignments;
	}

	public function getAssignments()
	{
		return $this->assignments;
	}

	public function __toString()
	{
		return implode('; ', $this->assignments);
	}
}
