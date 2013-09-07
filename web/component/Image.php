<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class Image extends HtmlElement
{
	protected $name = 'img';

	protected $allowChildren = false;

	/**
	 * @param string $src
	 */
	public function setSource($src)
	{
		$this->attributes['src'] = $src;
	}
}
