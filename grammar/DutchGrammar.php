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
					'head' => array('syntax' => array('category' => 'comma')),
					'space' => 'after_only'
				)
			),
			'\'s' => array(
				'form' => '\'s',
				'part-of-speech' => 'possessiveMarker',
				'features' => array('head' => array(
					'syntax' => array('category' => 'possessive'),
					'semantics' => ''
				))
			),
			'auteur' => array(
				'form' => 'auteur',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'author'),
					'semantics' => ''))
			),
			'ben' => array(
				'form' => 'ben',
				'part-of-speech' => 'verb',
				'features' => array('person' => 1, 'number' => 'singular', 'head' => array(
					'semantics' => ''
				))
			),
			'beïnvloed' => array(
				'form' => 'beïnvloed',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 1,
					'head' => array(
						'syntax' => array('predicate' => 'influence', 'tense' => 'past', 'form' => 'participle'),
						'semantics' => ''
					)
				)
			),
			'boek' => array(
				'form' => 'boek',
				'part-of-speech' => 'verb',
				'features' => array('head' => array(
					'agreement' => array('person' => 2, 'number' => 'singular'),
					'syntax' => array('predicate' => 'book'),
					'semantics' => ''
				))
			),
			'de' => array(
				'form' => 'de',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'the'),
					'semantics' => ''))
			),
			'die' => array(
				'form' => 'die',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'that'),
					'semantics' => ''))
			),
			'door_prep' => array(
				'form' => 'door',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'by'),
					'semantics' => ''
				))
			),
			'door_psv' => array(
				'form' => 'door',
				'part-of-speech' => 'passivisationPreposition',
				'features' => array('head' => array(
					'semantics' => ''
				))
			),
			'dochter' => array(
				'form' => 'dochter',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'daughter'),
						'semantics' => ''
					)
				),
			),
			'een' => array(
				'form' => 'een',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a'),
					'semantics' => ''))
			),
			'en' => array(
				'form' => 'en',
				'part-of-speech' => 'conjunction',
				'features' => array('head' => array(
					'semantics' => ''
				))
			),
			'geboren' => array(
				'form' => 'geboren',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'bear'),
						'semantics' => ''
					)
				),
			),
			'getrouwd' => array(
				'form' => 'getrouwd',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'marry', 'tense' => 'past'),
						'semantics' => ''
					)
				)
			),
			'had' => array(
				'form' => 'had',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have', 'tense' => 'past'),
						'semantics' => ''
					)
				)
			),
			'het' => array(
				'form' => 'het',
				'part-of-speech' => 'article',
				'features' => array('head' => array(
					'semantics' => ''
				))
			),
			'hoeveel' => array(
				'form' => 'hoeveel',
				'part-of-speech' => 'whwordNP',
				'features' => array(
					'head' => array(
						'syntax' => array(
							'deepDirectObject{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => true, 'category' => 'many')),
						),
						'semantics' => '',
						'variables' => array('role' => '?arg')
					)
				)
			),
			'ik' => array(
				'form' => 'ik',
				'part-of-speech' => 'pronoun',
				'features' => array('person' => 1, 'number' => 'singular', 'head' => array(
					'semantics' => ''
				))
			),
			'in' => array(
				'form' => 'in',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'in'),
					'semantics' => ''
				))
			),
			'ja' => array(
				'form' => 'ja',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'yes'),
					'semantics' => ''))
			),
			'januari' => array(
				'form' => 'januari',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'january', 'monthIndex' => 1),
						'semantics' => ''
					),
				),
			),
			'kinderen' => array(
				'form' => 'kinderen',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'plural'),
						'syntax' => array('category' => 'child'),
						'semantics' => ''
					)
				),
			),
			'met' => array(
				'form' => 'met',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'to'),
					'semantics' => ''
				))
			),
			'noem' => array(
				'form' => 'noem',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'name'),
						'semantics' => ''
					)
				)
			),
			'op' => array(
				'form' => 'op',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'on'),
					'semantics' => ''
				))
			),
			'van' => array(
				'form' => 'van',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'of'),
					'semantics' => ''
				))
			),
			'vlucht' => array(
				'form' => 'vlucht',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'agreement' => array('person' => 3, 'number' => 'singular'),
					'syntax' => array('category' => 'flight'),
					'semantics' => ''
				)),
			),
			'was_aux' => array(
				'form' => 'was',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be'),
						'semantics' => ''
					)
				),
			),
			'was_be' => array(
				'form' => 'was',
				'part-of-speech' => 'auxBe',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
						'semantics' => ''
					)
				),
			),
			'werd_aux' => array(
				'form' => 'werd',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
						'semantics' => ''
					)
				),
			),
			'werd_psv' => array(
				'form' => 'werd',
				'part-of-speech' => 'auxPsv',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
						'semantics' => ''
					)
				),
			),
			'waar' => array(
				'form' => 'waar',
				'part-of-speech' => 'whword',
				'features' => array('head' => array(
					'syntax' => array('category' => 'where'),
					'semantics' => ''))
			),
			'wanneer' => array(
				'form' => 'wanneer',
				'part-of-speech' => 'whword',
				'features' => array('head' => array(
					'syntax' => array('category' => 'when'),
					'semantics' => ''))
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
					'condition' => array('head' => array('syntax' => array('left' => array('type' => 'conjunction')), 'subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => '?left', 'subconjunction' => true))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
					)
				),
				// NP, NP, ; non-toplevel conjunction with entity at the left hand
				array(
					'condition' => array('head' => array('subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?left'))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
					)
				),
				// CP en NP ; toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('syntax' => array('left' => array('type' => 'conjunction')))),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => '?left', 'subconjunction' => true))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
					)
				),
				// NP en NP ; toplevel conjunction with entity at the left hand
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?left'))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
					)
				),
			),
		);

		$rules['NP'][] =

			// 11 augustus 1979
			array(
				'condition' => array('head' => array('syntax' => array('year' => null))),
				'rule' => array(
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('year' => '?year', 'month' => '?month', 'day' => '?day')))),
					array('cat' => 'numeral', 'features' => array('head' => array('syntax' => array('value' => '?day')))),
					array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('monthIndex' => '?month')))),
					array('cat' => 'numeral', 'features' => array('head' => array('syntax' => array('value' => '?year')))),
				)
			);

		return $rules;
	}
}
