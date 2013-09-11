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
}
