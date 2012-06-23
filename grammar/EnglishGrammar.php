<?php
/**
 * Provides all knowledge of the English language needed to parse and generate English sentences.
 *
 * Notes:
 *
 * 1. On parsing, we use a 'need to have' method. We only stem a word if it is suspected to be a noun. This in contrast
 *    to a method that would first stem all words before proceeding with the next step.
 */

namespace agentecho\grammar;

class EnglishGrammar extends SimpleGrammar
{
	public function getLanguage()
	{
		return 'english';
	}

	public function getLexicon()
	{
#todo Neem 'plural' ook op in de semantiek als de syntactische number = p; want je moet alleen verder kunnen met de semantiek; hetzelfde geld voor tense; kunnen we hier automatische regels voor opstellen?
		return array(
			'\'s' => array(
				'possessiveMarker' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'possessive'),
					))
				)
			),
			'a' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => '*a')))
				),
			),
			'an' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => '*a')))
				),
			),
			'and' => array(
				'conjunction' => array(
					//'features' => array('head' => array('sem' => array('category' => '*and')))
				),
			),
			'am' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 's'),
							'sem' => array('predicate' => '*be'),
							),
						)//'arguments' => 2)
					)
				),
			'are' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 'p')), 'arguments' => 1))),
			'author' => array(
				'noun' => array(
					'features' => array('head' => array('sem' => array('category' => '*author')))
				),
			),
			'book' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'agreement' => array('person' => 2, 'number' => 's'),
							'sem' => array('predicate' => '*book'),
						)
					),
				),
				'noun' => array(
					'features' => array('head' => array('sem' => array('category' => '*book'))),
				),
			),
			'born' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*bear'),
						)
					),
				),
			),
			'by' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'by'),
					))
				),

				'passivisationPreposition' => array(
					'features' => array('head' => array(
					))
				),

			),
			'children' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'p'),
							'sem' => array('category' => '*child')
						)
					),
				),
			),
			'daughter' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
//							'agreement' => array('person' => 3, 'number' => 's'),
							'sem' => array('category' => '*daughter')
						)
					),
				),
			),
			'did' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
						)
					),
				),
				'auxDo' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
						)
					),
				),
			),
			'die' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 0,
						'head' => array(
							'sem' => array('predicate' => '*die'),
						)
					)),
			),
			'flight' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 's'),
							'sem' => array('category' => '*flight'),
						)
					)
				),
			),
			'flowers' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'p'),
							'sem' => array('category' => '*flower'),
						)
					)
				),
			),
			'gives' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'tense' => 'present',
							'sem' => array('predicate' => '*give'),
						)
					),
				),
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => '*have'),
						)
					),
				),
			),
			'have' => array(
				'verb' => array(
					'features' => array(
//						'arguments' => 1,
						'head' => array(
							'sem' => array('predicate' => '*have'),
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
							'sem' => array('arg2{?arg}' => array('determiner' => array('question' => '*extent'))),
							'variables' => array('role' => '?arg')
						)
					)
				),
			),
			'i' => array(
				'pronoun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 's'),
							'sem' => array('category' => '*firstPerson')
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
							'sem' => array('predicate' => '*influence'),
						)
					),
				)
			),
			'many' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => '*many')))
				),
			),
			'name' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => '*name'),
						)
					)
				)
			),
			'of' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'of'),
					))
				),
			),
			'sees' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'tense' => 'present',
							'sem' => array('predicate' => '*see'),
						)
					),
				)
			),
			'the' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => '*the')))
				)
			),
			'that' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => '*that')))
				),
			),
			'was' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => '*be'),
						)
					),
				),
				'auxBe' => array(
					'features' => array(
						'head' => array(
							'tense' => 'past',
							'sem' => array('predicate' => '*be'),
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
					'features' => array('head' => array('sem' => array('arg2' => array('type' => 'entity', 'question' => true))))
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

		// $word ends with 're
		if (preg_match('/(^[^\']+)(\'re)$/', $word, $matches)) {
			// turn the 're part into 'are'
			return array($matches[1], "are");
		}

		// $word ends with n't
		if (preg_match('/(.+)(n\'t)$/', $word, $matches)) {
			if ($word == "won't") {
				return array("will", "not");
			} elseif (self::isVocal(substr($matches[1], -1, 1))) {
				// add an extra 'n' for "can't"
				return array($matches[1] . 'n', "not");
			} else {
				return array($matches[1], "not");
			}
		}

		// default: no action
		return array($word);
	}

	public function isVocal($letter)
	{
		return in_array($letter, array('a', 'e', 'i', 'o', 'u', 'y'));
	}

}
