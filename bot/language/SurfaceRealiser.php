<?php

class SurfaceRealiser
{
	/**
	 * Turns a syntax tree into surface text
	 *
	 * @param array $syntaxTree
	 * @return string|false A human readable string, or false
	 */
	public function realise(array $syntaxTree)
	{
		$words = $this->realisePhrase($syntaxTree);

		$sentence = implode(' ', $words);

		$sentence = ucfirst($sentence);
		$sentence .= '.';

		return $sentence;
	}

	protected function realisePhrase($phrase)
	{
		$words = array();

		if (isset($phrase['word'])) {

			$words[] = $phrase['word'];

		} else {

			foreach ($phrase['constituents'] as $constituent) {
				$words = array_merge($words, $this->realisePhrase($constituent));
			}

		}

		return $words;
	}
}