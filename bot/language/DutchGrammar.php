<?php

require_once(__DIR__ . '/SimpleGrammar.php');

class DutchGrammar extends SimpleGrammar
{
	public function getLexicon()
	{
		return array(
			'auteur' => 'noun',
			'ben' => array(
				'verb' => array('features' => array('person' => 1, 'number' => 's'))
			),
			'beïnvloed' => array(
				'verb' => array()
			),
			'boek' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 2, 'number' => 's')))),
					//'roles' => array('agent', 'patient')
			),
			'de' => array(
				'determiner' => array()
			),
			'die' => array(
				'determiner' => array()
			),
			'door' => array(
				'preposition' => array()
			),
			'dochter' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
//							'agreement' => array('person' => 3, 'number' => 's'),
							'sem' => array('isa' => '*daughter')
						)
					),
				),
			),
			'een' => array(
				'determiner' => array()
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*have', 'possessor{param1-1}' => null, 'possession{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
						)
					)
				)
			),
			'geboren' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*bear', 'agent{param1-1}' => null, 'theme{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
							'progressive' => 0
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
							'sem-1' => array(
								'param2-1' => array('question' => '*extent', 'determiner' => '*many'),
							),
							'variables' => array('role{param2-1}' => null)
						)
					)
				),
			),
			'ik' => array(
				'pronoun' => array('features' => array('person' => 1, 'number' => 's'))
			),
			'kinderen' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'p'),
							'sem' => array('isa' => '*child')
						)
					),
				)
			),
			'van' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('of{prep-1}' => null),
						'variables' => array('prep-1' => null),
					))
				),
			),
			'vlucht' => array(
				'noun' => array('features' => array('head' => array('agreement' => array('person' => 3, 'number' => 's')))),
			),
			'was' => array(
				'auxPsv' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be', 'theme{param1-1}' => null, 'isa{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
						)
					),
				),
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be', 'theme{param1-1}' => null, 'isa{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
						)
					),
				),
			),
			'werd' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be', 'theme{param1-1}' => null, 'isa{param2-1}' => null, 'param1-1' => null, 'param2-1' => null),
						)
					),
				),

			),
			'waar' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('location' => array('question' => true))))
				)
			),
			'wanneer' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('time' => array('question' => true))))
				)
			),
		);
	}

	public function getWord2PhraseStructure()
	{
		return array(
			'preposition' => array(
				'van' => array('preposition' => '*belong-to'),
				'door' => array('preposition' => '*actor'),
			),
			'verb' => array(
				'beïnvloed' => array('predicate' => '*influence'),
				'boek' => array('predicate' => '*book'),
				'ben' => array('predicate' => '*be'),
				'had' => array('predicate' => '*have'),
				'geboren' => array('predicate' => '*give-birth'),
			),
			'noun' => array(
				'auteur' => array('isa' => '*author'),
				'vlucht' => array('isa' => '*flight'),
				'kinderen' => array('isa' => '*child'),
				'dochter' => array('isa' => '*daughter'),
			),
			'pronoun' => array(
				'ik' => array('referring-expression' => '*current-speaker'),
			),
			'determiner' => array(
				'die' => array('determiner' => '*that'),
				'de' => array('determiner' => '*the'),
				'een' => array('determiner' => '*a'),
			),
			'whword' => array(
				'wanneer' => array('question' => '*time'),
				'waar' => array('question' => '*location'),
			),
			'whwordNP' => array(
				'wanneer' => array('question' => '*time'),
				'waar' => array('question' => '*location'),
				'hoeveel' => array('question' => '*nature-of', 'determiner' => '*many'),
			)
		);
	}
}
