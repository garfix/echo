<?php

namespace agentecho\web\view;
use agentecho\datastructure\SparqlQuery;

/**
 * @author Patrick van Bergen
 */
class SparqlQueryView extends TreeView
{
	public function getHtml(SparqlQuery $Query)
	{
		$selects = array();
		foreach ($Query->getSelects() as $select) {
			$selects[] = $this->markAsVariable($select);
		}

		$wheres = array();
		foreach ($Query->getWheres() as $where) {
			$wheres[] = $this->markAsCondition(htmlspecialchars($where));
		}

		return
			$this->markAsType('SELECT') . $this->eol() .
			$this->indent(1) . implode(', ' . $this->eol() . $this->indent(1), $selects) . $this->eol() .
			$this->markAsType("WHERE") . " {" . $this->eol() .
			$this->indent(1) . implode(" .  " . $this->eol() . $this->indent(1), $wheres) . $this->eol() .
			"}";
	}
}
