<?php

namespace agentecho\web\view;

/**
 * @author Patrick van Bergen
 */
class BindingsView extends TreeView
{
	public function getHtml(array $bindings)
	{
		return $this->markUpRecursive($bindings, 0);
	}

	private function markUpRecursive($value, $level)
	{
		$html = '';

		$spacing = str_repeat('&nbsp;&nbsp;&nbsp;', $level);

		if (is_array($value)) {

			$html .= '{<br/>';

			foreach($value as $key => $val) {
				$label = is_numeric($key) ? '' : "<span class='label'>" . $key . '</span> = ';
				$html .= $spacing . '&nbsp;&nbsp;&nbsp;' . $label . $this->markUpRecursive($val, $level + 1) . '<br />';
			}

			$html .= $spacing . '},';

		} else {

			return "<span class='value'>" . htmlspecialchars($value) . "</span>";

		}

		return $html;
	}}
