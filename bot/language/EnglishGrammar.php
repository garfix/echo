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
			'a' => 'article',
			'an' => 'article',
			'am' => array(
				'verb' => array(
						'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's'))))),
			'are' => array(
				'verb' => array(
						'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 'p'))))),
			'author' => 'noun',
			'book' => 'verb',
			'born' => 'verb',
			'by' => 'preposition',
			'children' => 'noun',
			'daughter' => 'noun',
			'did' => 'aux',
			'die' => 'verb',
			'flight' => 'noun',
			'have' => 'verb',
			'how' => 'whword',
			'i' => array(
				'pronoun' => array(
						'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's'))))),
			'influenced' => 'verb',
			'many' => 'determiner',
			'of' => 'preposition',
			'the' => array(
				'article' => array('cat' => 'article'),
				# note: an article _is_ a determiner
				'determiner' => array('cat' => 'determiner')),
			'that' => 'determiner',
			'was' => 'aux',
			'when' => 'whword',
			'where' => 'whword',
			'who' => 'whword',
		);
	}

	public function getWord2PhraseStructure()
	{
		return array(
			'preposition' => array(
				'of' => array('preposition' => '*belong-to'),
				'by' => array('preposition' => '*actor'),
			),
			'verb' => array(
				'am' => array('predicate' => '*be'),
				'are' => array('predicate' => '*be'),
				'book' => array('predicate' => '*book'),
				'born' => array('predicate' => '*give-birth'),
				'die' => array('predicate' => '*die'),
				'influenced' => array('predicate' => '*influence'),
				'have' => array('predicate' => '*have'),
			),
			'noun' => array(
				'author' => array('isa' => '*author'),
				'children' => array('isa' => '*child'),
				'daughter' => array('isa' => '*daughter'),
				'flight' => array('isa' => '*flight'),
			),
			'pronoun' => array(
				'i' => array('referring-expression' => '*current-speaker'),
			),
			'determiner' => array(
				'the' => array('determiner' => '*the'),
				'that' => array('determiner' => '*that'),
				'many' => array('determiner' => '*many'),
			),
			'whword' => array(
				'how' => array('question' => '*nature-of'),
				'when' => array('question' => '*time'),
				'where' => array('question' => '*location'),
				'who' => array('question' => '*person'),
			)
		);
	}
}

?>
