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

	protected function getLexicon()
	{
#todo Neem 'plural' ook op in de semantiek als de syntactische number = p; want je moet alleen verder kunnen met de semantiek; hetzelfde geld voor tense; kunnen we hier automatische regels voor opstellen?
		return array(
			',' => array(
				'form' => ',',
				'part-of-speech' => 'punctuationMark',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'comma')
					),
					'space' => 'after_only'
				),
			),
			'\'s' => array(
				'form' => '\'s',
				'part-of-speech' => 'possessiveMarker',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'possessive'),
					),
				),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			'a' => array(
				'form' => 'a',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a')
				)),
				'semantics' => ''
			),
			'an' => array(
				'form' => 'an',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a'),
					)
				),
				'semantics' => ''
			),
			'and' => array(
				'form' => 'and',
				'part-of-speech' => 'conjunction',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'am' => array(
				'form' => 'am',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 1, 'number' => 'singular'),
						'syntax' => array('predicate' => 'be', 'tense' => 'present'),
					),
				),
				'semantics' => ''
			),
			'are' => array(
				'form' => 'are',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 1, 'number' => 'plural'),
					),
					'arguments' => 1
				),
				'semantics' => ''
			),
			'author' => array(
				'form' => 'author',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'author'),
				)),
				'semantics' => 'isa(this.object, Author)'
			),
			'barking' => array(
				'form' => 'barking',
				'part-of-speech' => 'adjective',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'bed' => array(
				'form' => 'bed',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'bed'),
				)),
				'semantics' => 'isa(this.object, Bed)'
			),
			'book_v' => array(
				'form' => 'book',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 1,
					'head' => array(
						'agreement' => array('person' => 2, 'number' => 'singular'),
						'syntax' => array('predicate' => 'book'),
					)
				),
				'semantics' => ''
			),
			'book_n' => array(
				'form' => 'book',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'book'),
				)),
				'semantics' => ''
			),
			'born' => array(
				'form' => 'born',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'bear'),
					)
				),
				'semantics' => 'isa(this.event, Bear)'
			),
			'bright' => array(
				'form' => 'bright',
				'part-of-speech' => 'degreeAdverb',
				'features' => array(
					'head' => array(
					)
				),
				'semantics' => ''
			),
			'by_prp' => array(
				'form' => 'by',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'by'),
				)),
				'semantics' => '',
			),
			'by_psv' => array(
				'form' => 'by',
				'part-of-speech' => 'passivisationPreposition',
				'features' => array(
					'head' => array(

					)
				),
				'semantics' => '',
			),
			'calms' => array(
				'form' => 'calms',
				'part-of-speech' => 'verb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'children' => array(
				'form' => 'children',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'plural'),
						'syntax' => array('category' => 'child'),
					)
				),
				'semantics' => 'isa(this.object, Child)'
			),
			'daughter' => array(
				'form' => 'daughter',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'daughter'),
					)
				),
				'semantics' => 'isa(this.object, Daughter)'
			),
			'did_aux' => array(
				'form' => 'did',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			'did_auxDo' => array(
				'form' => 'did',
				'part-of-speech' => 'auxDo',
				'features' => array(
					'head' => array(
						'syntax' => array('tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			'die_smp' => array(
				'form' => 'die',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 0,
					'head' => array(
						'syntax' => array('predicate' => 'die', 'tense' => 'present', 'form' => 'simple'),
					)
				),
				'semantics' => 'isa(this.event, Die)'
			),
			'die_inf' => array(
				'form' => 'die',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 0,
					'head' => array(
						'syntax' => array('predicate' => 'die', 'verb_form' => 'infinitive'),
					)
				),
				'semantics' => 'isa(this.event, Die)'
			),
			'died' => array(
				'form' => 'died',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 0,
					'head' => array(
						'syntax' => array('predicate' => 'die', 'tense' => 'past'),

// and tense(this.event, Past)
					)
				),
				'semantics' => 'isa(this.event, Die)'
			),
			'dog' => array(
				'form' => 'dog',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'fiercely' => array(
				'form' => 'fiercely',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'flight' => array(
				'form' => 'flight',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'singular'),
						'syntax' => array('category' => 'flight'),
					)
				),
				'semantics' => ''
			),
			'flowers' => array(
				'form' => 'flowers',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'plural'),
						'syntax' => array('category' => 'flower'),
					)
				),
				'semantics' => ''
			),
			'gives' => array(
				'form' => 'gives',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'give', 'tense' => 'present'),
					)
				),
				'semantics' => ''
			),
			'had' => array(
				'form' => 'had',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have', 'tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			'have' => array(
				'form' => 'have',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have'),
					)
				),
				'semantics' => 'isa(this.event, Have)'
			),
			'how_1' => array(
				'form' => 'how',
#				'part-of-speech' => 'whword',
				'part-of-speech' => 'whAdverb',
				'features' => array(
					'head' => array(
						'syntax' => array('manner' => array('question' => 'object')),

					)
				),
				'semantics' => ''
			),
			'how_2' => array(
				'form' => 'how',
				'part-of-speech' => 'whwordNP',
				'features' => array(
					'head' => array(
						'syntax' => array('deepDirectObject{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => true))),
						'variables' => array('role' => '?arg'),
					)
				),
				'semantics' => 'manner(this.object, this.adjunct)'
			),
			'i' => array(
				'form' => 'i',
				'part-of-speech' => 'pronoun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 1, 'number' => 'singular'),
						'syntax' => array('category' => 'firstPerson'),
					),
					'capitalize' => true
				),
				'semantics' => ''
			),
			'in' => array(
				'form' => 'in',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'in'),
				)),
				'semantics' => ''
			),
			'influenced' => array(
				'form' => 'influenced',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 1,
					'head' => array(
						'syntax' => array('predicate' => 'influence', 'tense' => 'past', 'form' => 'participle'),
					)
				),
				'semantics' => 'isa(this.event, Influence) and tense(this.event, Past)'
			),
			'january' => array(
				'form' => 'january',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'january', 'monthIndex' => 1),
					),
					'capitalize' => true
				),
				'semantics' => ''
			),
			'many' => array(
				'form' => 'many',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'many'),

				)),
				'semantics' => 'many(this.object)'
			),
			'married' => array(
				'form' => 'married',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'marry', 'tense' => 'past'),
					)
				),
				'semantics' => 'isa(this.event, Marry)'
			),
			'name' => array(
				'form' => 'name',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'name'),
					)
				),
				'semantics' => 'isa(this.event, Name)'
			),
			'of' => array(
				'form' => 'of',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'of'),
				)),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			'old' => array(
				'form' => 'old',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'old'),
					)
				),
				'semantics' => 'isa(this.object, Old)'
			),
			'on' => array(
				'form' => 'on',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'on'),
				)),
				'semantics' => ''
			),
			'reads' => array(
				'form' => 'reads',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'read', 'tense' => 'present'),
					)
				),
				'semantics' => ''
			),
			'red' => array(
				'form' => 'red',
				'part-of-speech' => 'adjective',
				'features' => array(
					'head' => array(
						'syntax' => array(),
					)
				),
				'semantics' => ''
			),
			'sees' => array(
				'form' => 'sees',
				'part-of-speech' => 'verb',
				'features' => array(
					'arguments' => 1,
					'head' => array(
						'syntax' => array('predicate' => 'see', 'tense' => 'present'),
					)
				),
				'semantics' => ''
			),
			'she' => array(
				'form' => 'she',
				'part-of-speech' => 'pronoun',
				'features' => array(
					'head' => array(
						'agreement' => array('person' => 3, 'number' => 'singular'),
						'syntax' => array('category' => 'subject'), // thirdPerson?
					)
				),
				'semantics' => 'isa(this.object, Female) and reference(this.object)'
			),
			'the_det' => array(
				'form' => 'the',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'the'),
				)),
				'semantics' => '',
			),
			'the_ins' => array(
				'form' => 'the',
				'part-of-speech' => 'insertion',
				'features' => array('head' => array(
				)),
				'semantics' => '',
			),
			'that' => array(
				'form' => 'that',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'that'),
				)),
				'semantics' => ''
			),
			'to' => array(
				'form' => 'to',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'to'),
				)),
				'semantics' => 'to(this.superObject, this.subObject)'
			),
			'very' => array(
				'form' => 'very',
				'part-of-speech' => 'degreeAdverb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			'was_aux' => array(
				'form' => 'was',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			'was_be' => array(
				'form' => 'was',
				'part-of-speech' => 'auxBe',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			'was_psv' => array(
				'form' => 'was',
				'part-of-speech' => 'auxPsv',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			'when' => array(
				'form' => 'when',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'when'),
				)),
				'semantics' => 'at_time(this.event, this.adjunct)'
			),
			'where' => array(
				'form' => 'where',
				// http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'where'),
				)),
				'semantics' => 'location(this.event, this.adjunct)'
			),
			'who' => array(
				'form' => 'who',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'identity'),
				)),
				'semantics' => ''
			),
			'yes' => array(
				'form' => 'yes',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'yes')
				))
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

	public function getParseRules()
	{
		$rules = parent::getParseRules();

		// How many children did John have?
		// Where did Lord Byron die?
		// NP delivers deep subject
		$rules['S'][] =
			array(
				array('cat' => 'S',
					'semantics' => '
						S.sem = WhADVP.sem and auxDo.sem and NP.sem and VP.sem and subject(S.event, S.subject) and object(S.event, S.object) and request(S.request);
						S.event = VP.event;
						S.subject = NP.object;
						S.object = WhADVP.object;
						S.request = WhADVP.request;
						S.event = WhADVP.event
					',
					'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'clause' => '?syntax-1'))),
				array('cat' => 'WhADVP', 'features' => array('head' => array('syntax-1' => null))),
				array('cat' => 'auxDo', 'features' => array('head-1' => array('agreement' => '?agr'))),
				array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
				array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-1' => array('deepSubject' => '?syntax-2')))),
			);

		return $rules;
	}

	public function getGenerationRules()
	{
		$rules = parent::getGenerationRules();

		$rules += array(

			'CP' => array(
				// CP NP, ; non-toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('syntax' => array('left' => array('type' => 'conjunction')), 'subconjunction' => true)),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => '?left', 'subconjunction' => true))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
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
						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
					)
				),
				// CP and NP ; toplevel conjunction with conjunction at the left hand
				array(
					'condition' => array('head' => array('syntax' => array('left' => array('type' => 'conjunction')))),
					'rule' => array(
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => array('left' => '?left', 'right' => '?right')))),
						array('cat' => 'CP', 'features' => array('head' => array('syntax' => '?left', 'subconjunction' => true))),
						array('cat' => 'conjunction'),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?right'))),
					)
				),
				// NP and NP ; toplevel conjunction with entity at the left hand
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

			// August 11, 1979
			array(
				'condition' => array('head' => array('syntax' => array('year' => null))),
				'rule' => array(
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('year' => '?year', 'month' => '?month', 'day' => '?day')))),
					array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('monthIndex' => '?month')))),
					array('cat' => 'numeral', 'features' => array('head' => array('syntax' => array('value' => '?day')))),
					array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
					array('cat' => 'numeral', 'features' => array('head' => array('syntax' => array('value' => '?year')))),
				)
			);

		return $rules;
	}
}
