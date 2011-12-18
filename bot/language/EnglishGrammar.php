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
			'a' => array(
				'determiner' => array(),
			),
			'an' => array(
				'determiner' => array(),
			),
			'am' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's'))))),
			'are' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 'p'))))),
			'author' => array(
				'noun' => array(),
			),
			'book' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 2, 'number' => 's')))),
					'roles' => array('agent', 'patient')
// check 'natural language understanding' voor mogelijke syntax
			),
			'born' => array(
				'verb' => array(),
			),
			'by' => array(
				'preposition' => array(),
			),
			'children' => array(
				'noun' => array(),
			),
			'daughter' => array(
				'noun' => array(),
			),
			'did' => array(
				'aux' => array(),
			),
			'die' => array(
				'verb' => array(),
				'roles' => array('patient')
			),
			'flight' => array(
				'noun' => array(
					'features' => array('head' => array('agreement' => array('person' => 3, 'number' => 's')))),
			),
			'have' => array(
				'verb' => array(),
			),
			'how' => array(
				'whword' => array(),
			),
			'i' => array(
				'pronoun' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's'))))),
			'influenced' => array(
				'verb' => array(),
			),
			'many' => array(
				'determiner' => array(),
			),
			'of' => array(
				'preposition' => array(),
			),
			'the' => array(
				'determiner' => array()
			),
			'that' => array(
				'determiner' => array(),
			),
			'was' => array(
				'aux' => array(),
			),
			'when' => array(
				'whword' => array(),
			),
			'where' => array(
				'whword' => array(),
			),
			'who' => array(
				'whword' => array(),
			),
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
