<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class AssociativeArray
{
	private $values = array();

	public function set($key, $value)
	{
		$this->values[$key] = $value;
	}

	public function get($key)
	{
		return isset($this->values[$key]) ? $this->values[$key] : null;
	}

	public function __toString()
	{
		$string = '[';

		foreach ($this->values as $key => $value) {
			$string .= ($string == '[' ? '' : ', ') . $key . ': ' . $value;
		}

		$string .= ']';

		return $string;
	}

	public function createClone()
	{
		$Clone = new AssociativeArray();

		foreach ($this->values as $key => $value) {

			$clonedValue = is_object($value) ? $value->createClone() : $value;

			$Clone->set($key, $clonedValue);
		}

		return $Clone;
	}
}
