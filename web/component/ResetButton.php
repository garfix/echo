<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class ResetButton extends HtmlElement
{
	protected $name = 'button';

	public function __construct()
	{
		$this->attributes['type'] = 'reset';
	}

	public function setTitle($title)
	{
		$this->children[] = $title;
	}
}
