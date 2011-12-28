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
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's')), 'arguments' => 1))),
			'are' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 'p')), 'arguments' => 1))),
			'author' => array(
				'noun' => array(
					'features' => array('head' => array('sem' => array('isa' => '*author')))
				),
			),
			'book' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 2, 'number' => 's'))),
				),
				'noun' => array(
					'features' => array('head' => array('sem' => array('isa' => '*book'))),
				),
			),
			'born' => array(
				'verb' => array(),
			),
			'by' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('by{prep-1}' => null),
						'prep-1' => null
					))
				),
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
				'verb' => array(
					'features' => array('arguments' => 0)),
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
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 's')))),
			),
			'influenced' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => '*influence', 'agent{subject-1}' => null, 'experiencer{object-1}' => null, 'by{subject-1}' => null),
							'subject-1' => null,
							'object-1' => null,
							// by => agent
							//'prepositional_by{subject-1}' => null,
						)
					),
				)
			),
			'many' => array(
				'determiner' => array(),
			),
			'of' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('of{prep-1}' => null),
						'prep-1' => null
					))
				),
			),
			'sees' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => '*see', 'agent{subject-1}' => null, 'theme{object-1}' => null),
							'subject-1' => null,
							'object-1' => null
						)
					),
				)
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
				'sees' => array('predicate' => '*see'),
			),
			'noun' => array(
				'author' => array('isa' => '*author'),
				'book' => array('isa' => '*book'),
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
