<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class Input extends HtmlElement
{
	protected $name = 'input';

	protected $allowChildren = false;

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->attributes['type'] = $type;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->attributes['name'] = $name;
	}

	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->attributes['value'] = $value;
	}
}
