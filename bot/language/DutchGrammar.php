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
				'article' => array(),
				'determiner' => array()
			),
			'die' => array(
				'determiner' => array()
			),
			'door' => array(
				'preposition' => array()
			),
			'dochter' => array(
				'noun' => array()
			),
			'een' => array(
				'article' => array(),
				'determiner' => array()
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*have', 'possessor{subject-1}' => null, 'possession{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					)
				)
			),
			'geboren' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*bear', 'agent{subject-1}' => null, 'theme{object-1}' => null),
							'subject-1' => null,
							'object-1' => null,
						)
					),
				)
			),
			'het' => array(
				'article' => array()
			),
			'hoeveel' => array(
				'whwordNP' => array(
					'features' => array('head' => array('sem' => array('question' => '*extent', 'determiner' => '*many')))
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
				'preposition' => array()
			),
			'vlucht' => array(
				'noun' => array('features' => array('head' => array('agreement' => array('person' => 3, 'number' => 's')))),
			),
			'was' => array(
				'aux' => array()
			),
			'werd' => array(
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
