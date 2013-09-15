<?php

namespace agentecho\web\view;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Constant;
use agentecho\datastructure\PredicationList;

/**
 * @author Patrick van Bergen
 */
class PredicationListView extends TreeView
{
	public function getHtml(PredicationList $PredicationList)
	{
		$html = '';

		foreach ($PredicationList->getPredications() as $Predication) {
			$attributeHtml = array();

			foreach($Predication->getArguments() as $Argument) {

				if ($Argument instanceof Constant) {
					$valueHtml = $this->markAsConstant($Argument);
				} elseif ($Argument instanceof Atom) {
					$valueHtml = $this->markAsAtom($Argument);
				} else {
					$valueHtml = $this->markAsValue($Argument);
				}

				$attributeHtml[] = ' ' . $valueHtml . ' ';
			}

			$html .= $this->markAsType($Predication->getPredicate()) . '(' . implode(',', $attributeHtml) . ')' . $this->eol();
		}

		return $html;
	}
}
