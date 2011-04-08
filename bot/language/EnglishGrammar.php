<?php
/**
 * Provides all knowledge of the English language needed to parse English sentences.
 *
 * Notes:
 *
 * 1. On parsing, we use a 'need to have' method. We only stem a word if it is suspected to be a noun. This in contrast
 *    to a method that would first stem all words before proceeding with the next step.
 */

require_once('SimpleGrammar.php');
require_once('EarleyParser.php');

class EnglishGrammar extends SimpleGrammar
{
	public function getLexicon()
	{
		return array(
			'i' => 'pronoun',
			'am' => 'verb',
			'a' => 'article', 'an' => 'article', 'the' => 'article',
			'that' => 'determiner',
			'book' => 'verb',
			'flight' => 'noun',
			'who' => 'wh-word'
		);
	}
}

?>
