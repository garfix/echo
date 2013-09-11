<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class Anchor extends HtmlElement
{
	protected $name = 'a';

	/**
	 * @param string $href
	 */
	public function setHref($href)
	{
		$this->attributes['href'] = $href;
	}

	public function onClick($action)
	{
		$this->attributes['onclick'] = $action;
	}
}
