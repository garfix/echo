<?php

namespace agentecho\phrasestructure;

/**
 * Base class for all sentence nodes.
 */
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

	public function getAttributes()
	{
		return $this->data;
	}

	public function setAttributes(array $attributes)
	{
		$this->data = array_merge($this->data, $attributes);
	}

	public function createClone()
	{
		$newData = array();

		foreach ($this->data as $key => $value) {
			if (is_object($value)) {
				$newData[$key] = $value->createClone();
			} else {
				$newData[$key] = $value;
			}
		}

		$Clone = clone $this;
		$Clone->setAttributes($newData);

		return $Clone;
	}

	/**
	 * Perform $function on this node and all its children.
	 * @param $function A function like f(PhraseStructure)
	 */
	public function visit(\Closure $function)
	{
		$function($this);

		foreach ($this->getChildPhrases() as $Child) {
			$Child->visit($function);
		}
	}
}