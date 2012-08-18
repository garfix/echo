<?php

namespace agentecho\grammar;

class DutchGrammar extends SimpleGrammar
{
	public function getLanguage()
	{
		return 'dutch';
	}

	public function getLanguageCode()
	{
		return 'nl';
	}

	public function getLexicon()
	{
		return array(
			',' => array(
				'punctuationMark' => array(
					'features' => array('head' => array('sem' => array('category' => 'comma')))
				)
			),
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
							'sem' => array('predicate' => 'influence', 'tense' => 'past', 'form' => 'participle'),
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
			'en' => array(
				'conjunction' => array(
				),
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
			'getrouwd' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'marry', 'tense' => 'past'),
						)
					),
				)
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'have', 'tense' => 'past'),
						)
					)
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
								'arg2{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => true, 'category' => 'many')),
							),
							'variables' => array('role' => '?arg')
						)
					)
				),
			),
			'ik' => array(
				'pronoun' => array('features' => array('person' => 1, 'number' => 'singular'))
			),
			'in' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'in'),
					))
				),
			),
			'ja' => array(
				'adverb' => array(
					'features' => array('head' => array('sem' => array('category' => 'yes')))
				),
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
			'met' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'to'),
					))
				),
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
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be'),
						)
					),
				),
				'auxBe' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be', 'tense' => 'past'),
						)
					),
				),
			),
			'werd' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be', 'tense' => 'past'),
						)
					),
				),
//				'auxBe' => array(
//					'features' => array(
//						'head' => array(
//							'sem' => array('predicate' => 'be', 'tense' => 'past'),
//						)
//					),
//				),
				'auxPsv' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be', 'tense' => 'past'),
						)
					),
				),

			),
			'waar' => array(
				'whword' => array(
					//'features' => array('head' => array('sem' => array('location' => array('question' => true))))
					'features' => array('head' => array('sem' => array(
						'preposition' => array(
							'type' => 'preposition', 'category' => 'location', 'object' => array(
								'type' => 'entity', 'question' => true)))))
				),
			),
			'wanneer' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array(
						'preposition' => array(
							'type' => 'preposition', 'category' => 'time', 'object' => array(
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

	protected function getGenerationRules()
	{
		return parent::getGenerationRules() + array(

			'CP' => array(
				// CP, NP ; non-toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('sem' => array('left' => array('type' => 'conjunction')), 'subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('sem' => '?left', 'subconjunction' => true))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('sem' => array('category' => 'comma')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
					)
				),
				// NP, NP, ; non-toplevel conjunction with entity at the left hand
				array(
					'condition' => array('head' => array('subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?left'))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('sem' => array('category' => 'comma')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
					)
				),
				// CP en NP ; toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('sem' => array('left' => array('type' => 'conjunction')))),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('sem' => '?left', 'subconjunction' => true))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
					)
				),
				// NP en NP ; toplevel conjunction with entity at the left hand
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?left'))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
					)
				),
			),
		);
	}
}
