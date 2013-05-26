<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class SparqlQuery
{
	private $select = array();
	private $where = array();

	public function select($clause)
	{
		$this->select[$clause] = $clause;
	}

	public function where($clause)
	{
		$this->where[$clause] = $clause;
	}

	public function __toString()
	{
		return 'SELECT ' . implode(', ', $this->select) . "\nWHERE {\n    " . implode(" .\n    ", $this->where) . "\n}";
	}
}