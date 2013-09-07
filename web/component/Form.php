<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class Form extends HtmlElement
{
	protected $name = 'form';

	public function setMethodGet()
	{
		$this->attributes['method'] = 'get';
	}

	public function getJavascriptElements()
	{
		$javascriptHtml = '';
		foreach ($this->children as $Child) {
			foreach ($Child->getJavascriptFiles() as $javascriptFile) {
				$javascriptHtml .= "<script src='$javascriptFile'></script>";
			}
		}

		return $javascriptHtml;
	}

	public function getStyleElements()
	{
		$styleHtml = '';
		foreach ($this->children as $Child) {
			foreach ($Child->getStyleSheetFiles() as $styleSheetFile) {
				$styleHtml .= "<link rel='stylesheet' type='text/css' media='screen' href='$styleSheetFile' />";
			}
		}

		return $styleHtml;
	}
}
