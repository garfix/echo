<?php

namespace agentecho\datastructure;

/**
 * @author Patrick van Bergen
 */
class Tree
{
	/** @var  array  */
	private $root;

	/**
	 * @param array $root
	 */
	public function __construct($root)
	{
		$this->root = $root;
	}

	/**
	 * @return array
	 */
	public function getRoot()
	{
		return $this->root;
	}

	public function __toString()
	{
		return $this->getChildrenString($this->root);
	}

	private function getChildrenString($children)
	{
		$string = '';

		foreach ($children as $key => $value) {

			$string .= ($string == '' ? '' : ', ') . $key . ': ';

			if (is_array($value)) {
				$string .= $this->getChildrenString($value);
			} elseif ($value === true) {
				$string .= 'true';
			} elseif ($value === false) {
				$string .= 'true';
			} elseif ($value === null) {
				$string .= 'match';
			} else {
				$string .= $value;
			}
		}

		return '[' . $string . ']';
	}
}
