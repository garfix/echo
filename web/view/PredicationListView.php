<?php

namespace agentecho\web\view;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class RelationListView extends TreeView
{
	public function getHtml(RelationList $RelationList)
	{
		$html = '';

		foreach ($RelationList->getRelations() as $Relation) {
			$attributeHtml = array();

			foreach($Relation->getArguments() as $Argument) {

				if ($Argument instanceof Constant) {
					$valueHtml = $this->markAsConstant($Argument);
				} elseif ($Argument instanceof Atom) {
					$valueHtml = $this->markAsAtom($Argument);
				} elseif ($Argument instanceof Variable) {
					$valueHtml = $this->markAsVariable($Argument);
				} else {
					$valueHtml = $this->markAsValue(htmlspecialchars($Argument));
				}

				$attributeHtml[] = ' ' . $valueHtml . ' ';
			}

			$html .= $this->markAsType($Relation->getPredicate()) . ' (' . implode(',', $attributeHtml) . ')' . $this->eol();
		}

		return $html;
	}
}
