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

	public function getLanguageCode()
	{
		return 'en';
	}

	public function getLexicon()
	{
#todo Neem 'plural' ook op in de semantiek als de syntactische number = p; want je moet alleen verder kunnen met de semantiek; hetzelfde geld voor tense; kunnen we hier automatische regels voor opstellen?
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
			'a' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'a')))
				),
			),
			'an' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'a')))
				),
			),
			'and' => array(
				'conjunction' => array(
				),
			),
			'am' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 'singular'),
							'sem' => array('predicate' => 'be', 'tense' => 'present'),
							),
						)//'arguments' => 2)
					)
				),
			'are' => array(
				'verb' => array(
					'features' => array('head' => array('agreement' => array('person' => 1, 'number' => 'plural')), 'arguments' => 1))),
			'author' => array(
				'noun' => array(
					'features' => array('head' => array('sem' => array('category' => 'author')))
				),
			),
			'book' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'agreement' => array('person' => 2, 'number' => 'singular'),
							'sem' => array('predicate' => 'book'),
						)
					),
				),
				'noun' => array(
					'features' => array('head' => array('sem' => array('category' => 'book'))),
				),
			),
			'born' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'bear'),
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
							'agreement' => array('person' => 3, 'number' => 'plural'),
							'sem' => array('category' => 'child')
						)
					),
				),
			),
			'daughter' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
//							'agreement' => array('person' => 3, 'number' => 'singular'),
							'sem' => array('category' => 'daughter')
						)
					),
				),
			),
			'did' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('tense' => 'past'),
						)
					),
				),
				'auxDo' => array(
					'features' => array(
						'head' => array(
							'sem' => array('tense' => 'past'),
						)
					),
				),
			),
			'die' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 0,
						'head' => array(
							'sem' => array('predicate' => 'die'),
						)
					)),
			),
			'flight' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'singular'),
							'sem' => array('category' => 'flight'),
						)
					)
				),
			),
			'flowers' => array(
				'noun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 3, 'number' => 'plural'),
							'sem' => array('category' => 'flower'),
						)
					)
				),
			),
			'gives' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'give', 'tense' => 'present'),
						)
					),
				),
			),
			'had' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'have', 'tense' => 'past'),
						)
					),
				),
			),
			'have' => array(
				'verb' => array(
					'features' => array(
//						'arguments' => 1,
						'head' => array(
							'sem' => array('predicate' => 'have'),
						)
					),
				),
			),
			'how' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('manner' => array('question' => 'object'))))
				),
				'whwordNP' => array(
					'features' => array(
						'head' => array(
							'sem' => array('arg2{?arg}' => array('determiner' => array('question' => true))),
							'variables' => array('role' => '?arg')
						)
					)
				),
			),
			'i' => array(
				'pronoun' => array(
					'features' => array(
						'head' => array(
							'agreement' => array('person' => 1, 'number' => 'singular'),
							'sem' => array('category' => 'firstPerson')
						)
					)
				),
			),
			'in' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'in'),
					))
				),
			),
			'influenced' => array(
				'verb' => array(
					'features' => array(
						'arguments' => 1,
						'head' => array(
							'sem' => array('predicate' => 'influence', 'tense' => 'past', 'form' => 'participle'),
						)
					),
				)
			),
			'many' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'many')))
				),
			),
			'married' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'marry', 'tense' => 'past'),
						)
					),
				)
			),
			'name' => array(
				'verb' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'name'),
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
							'sem' => array('predicate' => 'see', 'tense' => 'present'),
						)
					),
				)
			),
			'the' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'the')))
				)
			),
			'that' => array(
				'determiner' => array(
					'features' => array('head' => array('sem' => array('category' => 'that')))
				),
			),
			'to' => array(
				'preposition' => array(
					'features' => array('head' => array(
						'sem' => array('category' => 'to'),
					))
				),
			),
			'was' => array(
				'aux' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be', 'tense' => 'past'),
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
				'auxPsv' => array(
					'features' => array(
						'head' => array(
							'sem' => array('predicate' => 'be', 'tense' => 'past'),
						)
					),
				),
			),
			'when' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array(
						'preposition' => array(
							'type' => 'preposition', 'category' => 'time', 'object' => array(
								'type' => 'entity', 'question' => true)))))
				),
			),
			'where' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array(
						'preposition' => array(
							'type' => 'preposition', 'category' => 'location', 'object' => array(
								'type' => 'entity', 'question' => true)))))
				),
			),
			'who' => array(
				'whword' => array(
					'features' => array('head' => array('sem' => array('arg2' => array('type' => 'entity', 'question' => true))))
				),
			),
			'yes' => array(
				'adverb' => array(
					'features' => array('head' => array('sem' => array('category' => 'yes')))
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

	protected function getGenerationRules()
	{
		return parent::getGenerationRules() + array(

			'CP' => array(
				// CP NP, ; non-toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('sem' => array('left' => array('type' => 'conjunction')), 'subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('sem' => '?left', 'subconjunction' => true))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('sem' => array('category' => 'comma')))),
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
						array('cat' => 'punctuationMark', 'features' => array('head' => array('sem' => array('category' => 'comma')))),
					)
				),
				// CP and NP ; toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('sem' => array('left' => array('type' => 'conjunction')))),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('sem' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('sem' => '?left', 'subconjunction' => true))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?right'))),
					)
				),
				// NP and NP ; toplevel conjunction with entity at the left hand
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
