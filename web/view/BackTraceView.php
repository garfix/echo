<?php

namespace agentecho\web\view;

/**
 * @author Patrick van Bergen
 */
class BackTraceView extends TreeView
{
	public function getHtml(array $trace)
	{
		$html = '<table>';

		foreach ($trace as $entry) {

			if (empty($entry['function'])) {
				$function = $this->markAsException($this->baseName($entry['class']));
			} else {
				$function =
					$this->markAsFunction($this->baseName($entry['class'])) . '::' .
					$this->markAsFunction($entry['function']);
			}

			$file =
				$this->markAsFile($entry['file']) . ': ' .
				$this->markAsFile($entry['line']);

			$html .= '<tr><td>' . $function . '&nbsp;&nbsp;&nbsp;&nbsp;</td><td>' . $file . '</td></tr>';
		}

		$html .= '</table>';

		return $html;
	}

	private function baseName($path)
	{
		preg_match('#\\\\([^\\\\]+)$#', $path, $matches);
		return $matches[1];
	}
}
