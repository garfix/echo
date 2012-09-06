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

	protected function getLexicon()
	{
		return array(
			',' => array(
				'form' => ',',
				'part-of-speech' => 'punctuationMark',
				'features' => array(
					'head' => array('sem' => array('category' => 'comma')),
					'space' => 'after_only'
				)
			),
			'\'s' => array(
				'form' => '\'s',
				'part-of-speech' => 'possessiveMarker',
				'features' => array('head' => array(
					'sem' => array('category' => 'possessive'),
				))
			),
			'auteur' => array(
				'form' => 'auteur',
				'part-of-speech' => 'noun',
				'features' => array('head' => array('sem' => array('category' => 'author')))
			),
			'ben' => array(
				'form' => 'ben',
				'part-of-speech' => 'verb',
				'features' => array('person' => 1, 'number' => 'singular')
			),
			'beïnvloed' => array(
				'form' => 'beïnvloed',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 1,
					'head' => array(
						'sem' => array('predicate' => 'influence', 'tense' => 'past', 'form' => 'participle'),
					)
				)
			),
			'boek' => array(
				'form' => 'boek',
				'part-of-speech' => 'verb',
				'features' => array('head' => array(
					'agreement' => array('person' => 2, 'number' => 'singular'),
					'sem' => array('predicate' => 'book'),
				))
			),
			'de' => array(
				'form' => 'de',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array('sem' => array('category' => 'the')))
			),
			'die' => array(
				'form' => 'die',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array('sem' => array('category' => 'that')))
			),
			'door_prep' => array(
				'form' => 'door',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'sem' => array('category' => 'by'),
				))
			),
			'door_psv' => array(
				'form' => 'door',
				'part-of-speech' => 'passivisationPreposition',
				'features' => array('head' => array())
			),
			'dochter' => array(
				'form' => 'dochter',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'sem' => array('category' => 'daughter')
					)
				),
			),
			'een' => array(
				'form' => 'een',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array('sem' => array('category' => 'a')))
			),
			'en' => array(
				'form' => 'en',
				'part-of-speech' => 'conjunction',
			),
			'geboren' => array(
				'form' => 'geboren',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'bear'),
					)
				),
			),
			'getrouwd' => array(
				'form' => 'getrouwd',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'marry', 'tense' => 'past'),
					)
				)
			),
			'had' => array(
				'form' => 'had',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'have', 'tense' => 'past'),
					)
				)
			),
			'het' => array(
				'form' => 'het',
				'part-of-speech' => 'article',
			),
			'hoeveel' => array(
				'form' => 'hoeveel',
				'part-of-speech' => 'whwordNP',
				'features' => array(
					'head' => array(
						'sem' => array(
							'arg2{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => true, 'category' => 'many')),
						),
						'variables' => array('role' => '?arg')
					)
				)
			),
			'ik' => array(
				'form' => 'ik',
				'part-of-speech' => 'pronoun',
				'features' => array('person' => 1, 'number' => 'singular')
			),
			'in' => array(
				'form' => 'in',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'sem' => array('category' => 'in'),
				))
			),
			'ja' => array(
				'form' => 'ja',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array('sem' => array('category' => 'yes')))
			),
			'januari' => array(
				'form' => 'januari',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'sem' => array('category' => 'january', 'monthIndex' => 1),
					),
				),
			),
			'kinderen' => array(
				'form' => 'kinderen',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'plural'),
						'sem' => array('category' => 'child')
					)
				),
			),
			'met' => array(
				'form' => 'met',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'sem' => array('category' => 'to'),
				))
			),
			'noem' => array(
				'form' => 'noem',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'name'),
					)
				)
			),
			'op' => array(
				'form' => 'op',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'sem' => array('category' => 'on'),
				))
			),
			'van' => array(
				'form' => 'van',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'sem' => array('category' => 'of'),
				))
			),
			'vlucht' => array(
				'form' => 'vlucht',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'agreement' => array('person' => 3, 'number' => 'singular'),
					'sem' => array('category' => 'flight')
				)),
			),
			'was_aux' => array(
				'form' => 'was',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'be'),
					)
				),
			),
			'was_be' => array(
				'form' => 'was',
				'part-of-speech' => 'auxBe',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
			),
			'werd_aux' => array(
				'form' => 'werd',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
			),
			'werd_psv' => array(
				'form' => 'werd',
				'part-of-speech' => 'auxPsv',
				'features' => array(
					'head' => array(
						'sem' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
			),
			'waar' => array(
				'form' => 'waar',
				'part-of-speech' => 'whword',
				'features' => array('head' => array('sem' => array(
					'preposition' => array(
						'type' => 'preposition', 'category' => 'location', 'object' => array(
							'type' => 'entity', 'question' => true)))))
			),
			'wanneer' => array(
				'form' => 'wanneer',
				'part-of-speech' => 'whword',
				'features' => array('head' => array('sem' => array(
					'preposition' => array(
						'type' => 'preposition', 'category' => 'time', 'object' => array(
							'type' => 'entity', 'question' => true)))))
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

	public function getGenerationRules()
	{
		$rules = parent::getGenerationRules();

		$rules += array(

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

		$rules['NP'][] =

			// 11 augustus 1979
			array(
				'condition' => array('head' => array('sem' => array('year' => null))),
				'rule' => array(
					array('cat' => 'NP', 'features' => array('head' => array('sem' => array('year' => '?year', 'month' => '?month', 'day' => '?day')))),
					array('cat' => 'numeral', 'features' => array('head' => array('sem' => array('value' => '?day')))),
					array('cat' => 'noun', 'features' => array('head' => array('sem' => array('monthIndex' => '?month')))),
					array('cat' => 'numeral', 'features' => array('head' => array('sem' => array('value' => '?year')))),
				)
			);

		return $rules;
	}
}
