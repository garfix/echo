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
		$this->select[] = $clause;
	}

	public function where($clause)
	{
		$this->where[] = $clause;
	}

	public function __toString()
	{
		return 'SELECT ' . implode(', ', $this->select) . ' WHERE {' . implode(' . ', $this->where) . '}';
	}
}
