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
	/** @var bool */
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

	public function addStyle($key, $value)
	{
		$this->attributes['style'] =
			isset($this->attributes['style'])
			? $this->attributes['style'] . ';' . $key . ':' . $value
			: $key . ':' . $value;
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
		$files = array();

		foreach ($this->children as $Child) {
			if ($Child instanceof HtmlElement) {
				$files = array_merge($files, $Child->getJavascriptFiles());
			}
		}

		return $files;
	}

	/**
	 * @return array
	 */
	public function getStyleSheetFiles()
	{
		$files = array();

		foreach ($this->children as $Child) {
			if ($Child instanceof HtmlElement) {
				$files = array_merge($files, $Child->getStyleSheetFiles());
			}
		}

		return $files;
	}

	/**
	 * @param $id
	 */
	public function setId($id)
	{
		$this->attributes['id'] = $id;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->attributes['id'];
	}

	public final function getJavascriptElements()
	{
		$javascriptHtml = '';
		foreach ($this->children as $Child) {
			foreach ($Child->getJavascriptFiles() as $javascriptFile) {
				$javascriptHtml .= "<script src='$javascriptFile'></script>";
			}
		}

		return $javascriptHtml;
	}

	public final function getStyleElements()
	{
		$styleHtml = '';
		foreach ($this->children as $Child) {
			foreach ($Child->getStyleSheetFiles() as $styleSheetFile) {
				$styleHtml .= "<link rel='stylesheet' type='text/css' media='screen' href='$styleSheetFile' />";
			}
		}

		return $styleHtml;
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
