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

	protected function markAsAttribute($value)
	{
		return "<span class='attribute'>" . $value . "</span>";
	}

	protected function markAsConstant($value)
	{
		return "<span class='constant'>" . $value . "</span>";
	}

	protected function markAsAtom($value)
	{
		return "<span class='atom'>" . $value . "</span>";
	}

	protected function markAsVariable($value)
	{
		return "<span class='variable'>" . $value . "</span>";
	}

	protected function markAsCondition($value)
	{
		return "<span class='condition'>" . $value . "</span>";
	}

	protected function markAsValue($value)
	{
		if ($value === '') {
			$valueHtml = "''";
		} elseif ($value === null) {
			$valueHtml = "<em>null</em>";
		} elseif ($value === false) {
			$valueHtml = "<em>false</em>";
		} elseif ($value === true) {
			$valueHtml = "<em>true</em>";
		} else {
			$valueHtml = $value;
		}

		return "<span class='value'>" . $valueHtml . "</span>";
	}

	protected function eol()
	{
		return "<br />";
	}

	protected function indent($depth)
	{
		return str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
	}
}
