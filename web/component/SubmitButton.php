<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class SubmitButton extends HtmlElement
{
	protected $name = 'button';

	public function __construct()
	{
		$this->attributes['type'] = 'submit';
	}

	public function setTitle($title)
	{
		$this->children[] = $title;
	}
}
