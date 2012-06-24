<?php

namespace agentecho\phrasestructure;

abstract class PhraseStructure
{
	protected $data = array();

	public function __toString()
	{
		$parts = array();

		foreach ($this->data as $name => $value) {

			if ($value !== null) {
				if (is_array($value)) {

					if (!empty($value)) {
						$elements = array();

						foreach ($value as $elementName => $elementValue) {
							$elements[] = $elementName . ' = ' . (string)$elementValue;
						}

						$parts[] = $name . ': ' . '[' . implode(', ', $elements) . ']';
					}

				} else {
					$parts[] = $name . ': ' . (string)$value;
				}
			}

		}

		$className = array_slice(explode('\\', get_class($this)), -1);
		return $className[0] . ' {' . implode(', ',  $parts) . '}';
	}

	public function getHashCode()
	{
		return sha1(serialize($this));
	}

    public function getChildPhrases()
    {
        $children = array();

        foreach ($this->data as $value) {

            if (is_array($value)) {

                foreach ($value as $v) {

                    if ($v instanceof PhraseStructure) {
                        $children[] = $v;
                    }
                }

            } else {

                if ($value instanceof PhraseStructure) {
                    $children[] = $value;
                }

            }

        }

        return $children;
    }
}