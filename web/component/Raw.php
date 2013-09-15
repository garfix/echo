<?php

namespace agentecho\web\component;

/**
 * @author Patrick van Bergen
 */
class Raw extends HtmlElement
{
	/** @var string */
	private $contents;

	public function __construct($contents)
	{
		$this->contents = $contents;
	}

	public function __toString()
	{
		return $this->contents;
	}
}
