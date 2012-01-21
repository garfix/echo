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
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 's'),
							),
						'arguments' => 1)
					)
				),
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
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'agreement' => array('person' => 2, 'number' => 's'),
							'sem' => array('predicate' => '*book', 'agent{subject-1}' => null, 'theme{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					),
				),
				'noun' => array(
					'features' => array('head' => array('sem' => array('isa' => '*book'))),
				),
			),
			'born' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*bear', 'agent{subject-1}' => null, 'theme{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					),
				),
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
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'p'),
							'sem' => array('isa' => '*child')
						)
					),
				),
			),
			'daughter' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
//							'agreement' => array('person' => 3, 'number' => 's'),
							'sem' => array('isa' => '*daughter')
						)
					),
				),
			),
			'did' => array(
				'aux' => array(),
			),
			'die' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 0,
						'head' => array(
							'sem' => array('predicate' => '*die', 'theme{subject-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					)),
			),
			'flight' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 's'),
							'sem' => array('isa' => '*flight'),
						)
					)
				),
			),
			'have' => array(
				'verb' => array(
					'features' => array(
//						'arguments' => 1,
						'head' => array(
							'sem' => array('predicate' => '*have', 'possessor{subject-1}' => null, 'possession{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					),
				),
			),
			'how' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('manner' => array('question' => '*object'))))
				),
				'whwordNP' => array(
					//'features' => array('head' => array('sem' => array('object' => array('question' => 'extent'))))
					'features' => array('head' => array('sem' => array('question' => '*extent')))
				),
			),
			'i' => array(
				'pronoun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 's'),
							'sem' => array('isa' => '*firstPerson')
						)
					)
				),
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
						)
					),
				)
			),
			'many' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('determiner' => '*many')))
				),
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
				'determiner' => array(
					'features' => array('head' => array('sem' => array('determiner' => '*the')))
				)
			),
			'that' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('determiner' => '*that')))
				),
			),
			'was' => array(
				'auxPsv' => array(
					'features' => array(
						'head' => array(
#todo klopt niet
							'sem' => array('predicate' => '*be', 'theme{subject-1}' => null, 'isa{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					),
				),
			),
			'when' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('time' => array('question' => true))))
				),
			),
			'where' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('location' => array('question' => true))))
				),
			),
			'who' => array(
				'whwordNP' => array(),
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
				'how' => array('question' => '*manner'),
				'when' => array('question' => '*time'),
				'where' => array('question' => '*location'),
				'who' => array('question' => '*person'),
			),
			'whwordNP' => array(
				'how' => array('question' => '*nature-of'),
				'when' => array('question' => '*time'),
				'where' => array('question' => '*location'),
				'who' => array('question' => '*person'),
			)
		);
	}
}

?>
