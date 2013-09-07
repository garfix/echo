<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class HtmlElement
{
	/** @var HtmlElement[]  */
	protected $children = array();
	/** @var string[]  */
	protected $attributes = array();

	protected $allowChildren = true;

	/**
	 * @param HtmlElement $Element
	 */
	public function add(HtmlElement $Element)
	{
		$this->children[] = $Element;
	}

	/**
	 * @param string $name Like 'data-language'
	 * @param $value
	 */
	public function setDataAttribute($name, $value)
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * @param string $class
	 */
	public function addClass($class)
	{
		$this->attributes['class'] =
			isset($this->attributes['class']) ?
				$this->attributes['class'] . ' ' . $class :
				$class;
	}

	/**
	 * @param string $text
	 */
	public function addText($text)
	{
		$this->children[] = $text;
	}

	/**
	 * @return array
	 */
	public function getJavascriptFiles()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public function getStyleSheetFiles()
	{
		return array();
	}

	public function __toString()
	{
		$attrs = array();
		foreach($this->attributes as $key => $value) {
			$attrs[] = $key . '=' . "'" . htmlspecialchars($value) . "'";
		}
		$attributes = $attrs ? (' ' . implode(' ', $attrs)) : '';

		$children = '';
		foreach ($this->children as $child) {
			if (is_string($child)) {
				$children .= htmlspecialchars($child);
			} else {
				$children .= $child;
			}
		}

		if ($this->allowChildren) {
			return "<" . $this->name . $attributes .">\n" . $children . "</" . $this->name . ">\n";
		} else {
			return "<" . $this->name . $attributes ." />\n";
		}
	}
}
