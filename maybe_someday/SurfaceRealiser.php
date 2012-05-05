<?php

namespace agentecho\later;

/**
 * A surface realiser turns a syntactic representation of a sentence into a single string of characters.
 * It applies these rules:
 * - syntax
 * - morphology
 * - orthography (Start with a capital letter, end with a sentence terminator (.!?))
 */
class SurfaceRealiser
{
	/**
	 * Turns a syntax tree into surface text
	 *
	 * @param array $phraseSpecification
	 * @return string|false A human readable string, or false
	 */
	public function realise(array $phraseSpecification)
	{
		$words = $this->realisePhrase($phraseSpecification);

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