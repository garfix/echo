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
#todo Neem 'plural' ook op in de semantiek als de syntactische number = p; want je moet alleen verder kunnen met de semantiek; hetzelfde geld voor tense; kunnen we hier automatische regels voor opstellen?
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
							'sem' => array('predicate' => '*be', 'theme{param1-1}' => null, 'isa{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
							),
						)//'arguments' => 2)
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
							'sem' => array('predicate' => '*book', 'agent{param1-1}' => null, 'theme{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
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
							'sem' => array('predicate' => '*bear', 'agent{param1-1}' => null, 'theme{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
							'progressive' => 0
						)
					),
				),
			),
			'by' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('by{prep-1}' => null),
						'variables' => array('prep-1' => null)
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
							'sem' => array('predicate' => '*die', 'theme{param1-1}' => null, 'param1-1' => null, 'param2-1' => null),
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
							'sem' => array('predicate' => '*have', 'possessor{param1-1}' => null, 'possession{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
						)
					),
				),
			),
			'how' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('manner' => array('question' => '*object'))))
				),
				'whwordNP' => array(
					'features' => array(
						'head' => array(
							'sem-1' => array('param2-1' => array('question' => '*extent')),
							'variables' => array('role{param2-1}' => null)
						)
					)
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
							// possible forms: simple, participle, infinitive
							'form' => 'participle',
							'sem' => array('predicate' => '*influence', 'agent{param1-1}' => null, 'experiencer{param2-1}' => null, 'by{param1-1}' => null, 'param1-1' => null, 'param2-1' => null),
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
						'variables' => array('prep-1' => null),
					))
				),
			),
			'sees' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'tense' => 'present',
							'sem' => array('predicate' => '*see', 'agent{param1-1}' => null, 'theme{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
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
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be', 'theme{param1-1}' => null, 'isa{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
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
				'whword' => array(
					'features' => array('head' => array('sem' => array('param2' => array('question' => true))))
				),
			),
		);
	}
}

?>
