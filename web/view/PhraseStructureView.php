<?php

namespace agentecho\web\view;

use agentecho\phrasestructure\PhraseStructure;

/**
 * @author Patrick van Bergen
 */
class PhraseStructureView extends TreeView
{
	public function getHtml(PhraseStructure $PhraseStructure)
	{
		return $this->getChildHtml($PhraseStructure, 0);
	}

	public function getChildHtml(PhraseStructure $PhraseStructure, $depth)
	{
		$parts = array();
		$data = $PhraseStructure->getAttributes();

		foreach ($data as $name => $value) {

			if ($value !== null) {
				if (is_array($value)) {

					if (!empty($value)) {
						$elements = array();

						foreach ($value as $elementName => $elementValue) {
							$elements[] = $this->eol() . $this->indent($depth) . $elementName . ' = ' . (string)$elementValue;
						}

						$parts[] = $this->markAsAttribute($name) . ' : ' . '[' . implode(', ', $elements) . ']';
					}

				} else {
					$valueHtml = $value instanceof PhraseStructure
						? $this->getChildHtml($value, $depth + 1)
						: $this->markAsValue($value);

					$parts[] = $this->eol() . $this->indent($depth + 1) . $this->markAsAttribute($name) . ' : ' . $valueHtml;
				}
			}
		}

		$className = array_slice(explode('\\', get_class($PhraseStructure)), -1);

		return $this->markAsType($className[0]) . ' {' . implode(', ',  $parts) . '}';
	}
}
