<?php

namespace agentecho\web\view;

/**
 * @author Patrick van Bergen
 */
class TreeView
{
	protected function markAsType($value)
	{
		return "<span class='type'>" . $value . "</span>";
	}

	protected function markAsValue($value)
	{
		return "<span class='value'>" . $value . "</span>";
	}

	protected function eol()
	{
		return "<br />";
	}
}
