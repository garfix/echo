<?php

namespace agentecho\grammar;

class DutchGrammar extends SimpleGrammar
{
	public function getLanguage()
	{
		return 'dutch';
	}

	public function getLexicon()
	{
		return array(
			'\'s' => array(
				'possessiveMarker' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'possessive'),
					))
				)
			),
			'auteur' => array(
				'noun' => array(
					'features' => array('head' => array('sem' => array('category' => 'author')))
				),
			),
			'ben' => array(
				'verb' => array('features' => array('person' => 1, 'number' => 'singular'))
			),
			'beïnvloed' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'tense' => 'past',
							// possible forms: simple, participle, infinitive
							'form' => 'participle',
							'sem' => array('predicate' => 'influence'),
						)
					),
				)
			),
			'boek' => array(
				'verb' => array(
					'features' => array('head' => array(
						'agreement' => array('person' => 2, 'number' => 'singular'),
						'sem' => array('predicate' => 'book'),
					))
				),
			),
			'de' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'the')))
				)
			),
			'die' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'that')))
				)
			),
			'door' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'by'),
					))
				),
				'passivisationPreposition' => array(
					'features' => array('head' => array())
				)
			),
			'dochter' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
//							'agreement' => array('person' => 3, 'number' => 'singular'),
							'sem' => array('category' => 'daughter')
						)
					),
				),
			),
			'een' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'a')))
				)
			),
			'and' => array(
				'conjunction' => array(
				),
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'have'),
						)
					)
				)
			),
			'geboren' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'bear'),
						)
					),
				)
			),
			'het' => array(
				'article' => array()
			),
			'hoeveel' => array(
				'whwordNP' => array(
					'features' => array(
						'head' => array(
							'sem' => array(
								'arg2{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => 'extent', 'category' => 'many')),
							),
							'variables' => array('role' => '?arg')
						)
					)
				),
			),
			'ik' => array(
				'pronoun' => array('features' => array('person' => 1, 'number' => 'singular'))
			),
			'kinderen' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'plural'),
							'sem' => array('category' => 'child')
						)
					),
				)
			),
			'noem' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'name'),
						)
					)
				)
			),
			'van' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'of'),
					))
				),
			),
			'vlucht' => array(
				'noun' => array('features' => array('head' => array(
					'agreement' => array('person' => 3, 'number' => 'singular'),
					'sem' => array('category' => 'flight')
				))),
			),
			'was' => array(
				'auxPsv' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be'),
						)
					),
				),
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be'),
						)
					),
				),
			),
			'werd' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => 'be'),
						)
					),
				),
				'auxBe' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => 'be'),
						)
					),
				),

			),
			'waar' => array(
				'whword' => array(
					//'features' => array('head' => array('sem' => array('location' => array('question' => true))))
					'features' => array('head' => array('sem' => array(
						'modifier' => array(
							'type' => 'modifier', 'category' => 'location', 'object' => array(
								'type' => 'entity', 'question' => true)))))
				),
			),
			'wanneer' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array(
						'modifier' => array(
							'type' => 'modifier', 'category' => 'time', 'object' => array(
								'type' => 'entity', 'question' => true)))))
				),
			),
		);
	}

	public function unglue($word)
	{
		// $word ends with 's or '
		if (preg_match('/(^[^\']+)(\'s?)$/', $word, $matches)) {
			// split the 's part (and turn ' into 's)
			return array($matches[1], "'s");
		}

		// default: no action
		return array($word);
	}

}
