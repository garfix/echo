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

	public function add(HtmlElement $Element)
	{
		$this->children[] = $Element;
	}

	public function getJavascriptFiles()
	{
		return array();
	}

	public function getStyleSheetFiles()
	{
		return array();
	}

	public function __toString()
	{
		$attrs = array();

		foreach($this->attributes as $key => $value) {
			$attrs[] = $key . '=' . "'" . $value . "'";
		}

		$attributes = $attrs ? (' ' . implode(' ', $attrs)) : '';

		return "<" . $this->name . $attributes .">\n" . implode("\n", $this->children) . "</" . $this->name . ">\n";
	}
}
