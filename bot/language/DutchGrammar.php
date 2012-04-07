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
			'be�nvloed' => array(
				'verb' => array()
			),
			'boek' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 2, 'number' => 's')))),
			),
			'de' => array(
				'determiner' => array()
			),
			'die' => array(
				'determiner' => array()
			),
			'door' => array(
				'preposition' => array(),
				'passivisationPreposition' => array(
					'features' => array('head' => array())
				)
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
							'sem' => array('predicate' => '*have'),
						)
					)
				)
			),
			'geboren' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*bear'),
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
								'arg2-1' => array('question' => '*extent', 'determiner' => '*many'),
							),
							'variables' => array('role{arg2-1}' => null)
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
						'sem' => array('type' => 'of'),
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
							'sem' => array('predicate' => '*be'),
						)
					),
				),
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be'),
						)
					),
				),
			),
			'werd' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*be'),
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
}
