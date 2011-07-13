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
			'a' => 'article', 'an' => 'article',
			'am' => 'verb',
			'author' => 'noun',
			'book' => 'verb',
			'born' => 'verb',
			'by' => 'preposition',
			'children' => 'noun',
			'did' => 'aux',
			'flight' => 'noun',
			'have' => 'verb',
			'how' => 'wh-word',
			'i' => 'pronoun',
			'influenced' => 'verb',
			'many' => 'determiner',
			'of' => 'preposition',
			'the' => array('article', 'determiner'),
			'that' => 'determiner',
			'was' => 'aux',
			'when' => 'wh-word',
			'where' => 'wh-word',
			'who' => 'wh-word',
		);
	}

	public function getWord2Predicate()
	{
		return array(
			'preposition' => array(
				'of' => array('preposition' => '*belong-to'),
				'by' => array('preposition' => '*actor'),
			),
			'verb' => array(
				'influenced' => array('predicate' => '*influence'),
				'book' => array('predicate' => '*book'),
				'am' => array('predicate' => '*be'),
				'have' => array('predicate' => '*have'),
				'born' => array('predicate' => '*give-birth')
			),
			'noun' => array(
				'author' => array('isa' => '*author'),
				'flight' => array('isa' => '*flight'),
				'children' => array('isa' => '*child')
			),
#todo
			'determiner' => array(
				'the' => array('determiner' => 'the'),
				'many' => array('determiner' => 'many'),
				'that' => array('determiner' => 'that')
			),
			'wh-word' => array(
				'when' => array('question' => '*time'),
				'where' => array('question' => '*location'),
				'how' => array('question' => '*nature-of'),
			)
		);
	}
}

?>
