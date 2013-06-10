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
	public function __construct()
	{
		parent::__construct();

		$this->loadParseGrammar(__DIR__ . '/../resources/english.parse.grammar');
		$this->loadGenerationGrammar(__DIR__ . '/../resources/english.generation.grammar');
	}

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
			array(
				'form' => ',',
				'part-of-speech' => 'punctuationMark',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'comma')
					),
					'space' => 'after_only'
				),
			),
			array(
				'form' => '\'s',
				'part-of-speech' => 'possessiveMarker',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'possessive'),
					),
				),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'a',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a')
				)),
				'semantics' => ''
			),
			array(
				'form' => 'an',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a'),
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'and',
				'part-of-speech' => 'conjunction',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
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
			array(
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
			array(
				'form' => 'author',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'author'),
				)),
				'semantics' => 'isa(this.object, Author)'
			),
			array(
				'form' => 'barking',
				'part-of-speech' => 'adjective',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'bed',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'bed'),
				)),
				'semantics' => 'isa(this.object, Bed)'
			),
			array(
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
			array(
				'form' => 'book',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'book'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'born',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'bear'),
					)
				),
				'semantics' => 'isa(this.event, Bear)'
			),
			array(
				'form' => 'bright',
				'part-of-speech' => 'degreeAdverb',
				'features' => array(
					'head' => array(
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'by',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'by'),
				)),
				'semantics' => '',
			),
			array(
				'form' => 'by',
				'part-of-speech' => 'passivisationPreposition',
				'features' => array(
					'head' => array(

					)
				),
				'semantics' => '',
			),
			array(
				'form' => 'calms',
				'part-of-speech' => 'verb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
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
			array(
				'form' => 'daughter',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'daughter'),
					)
				),
				'semantics' => 'isa(this.object, Daughter)'
			),
			array(
				'form' => 'did',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'did',
				'part-of-speech' => 'auxDo',
				'features' => array(
					'head' => array(
						'syntax' => array('tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			array(
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
			array(
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
			array(
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
			array(
				'form' => 'dog',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'fiercely',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
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
			array(
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
			array(
				'form' => 'gives',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'give', 'tense' => 'present'),
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'had',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have', 'tense' => 'past'),
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'have',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have'),
					)
				),
				'semantics' => 'isa(this.event, Have)'
			),
			array(
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
			array(
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
			array(
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
			array(
				'form' => 'in',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'in'),
				)),
				'semantics' => ''
			),
			array(
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
			array(
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
			array(
				'form' => 'many',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'many'),

				)),
				'semantics' => 'many(this.object)'
			),
			array(
				'form' => 'married',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'marry', 'tense' => 'past'),
					)
				),
				'semantics' => 'isa(this.event, Marry)'
			),
			array(
				'form' => 'name',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'name'),
					)
				),
				'semantics' => 'isa(this.event, Name)'
			),
			array(
				'form' => 'of',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'of'),
				)),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'old',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'old'),
					)
				),
				'semantics' => 'isa(this.object, Old)'
			),
			array(
				'form' => 'on',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'on'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'reads',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'read', 'tense' => 'present'),
					)
				),
				'semantics' => ''
			),
			array(
				'form' => 'red',
				'part-of-speech' => 'adjective',
				'features' => array(
					'head' => array(
						'syntax' => array(),
					)
				),
				'semantics' => ''
			),
			array(
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
			array(
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
			array(
				'form' => 'the',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'the'),
				)),
				'semantics' => '',
			),
			array(
				'form' => 'the',
				'part-of-speech' => 'insertion',
				'features' => array('head' => array(
				)),
				'semantics' => '',
			),
			array(
				'form' => 'that',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'that'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'to',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'to'),
				)),
				'semantics' => 'to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'very',
				'part-of-speech' => 'degreeAdverb',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'was',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			array(
				'form' => 'was',
				'part-of-speech' => 'auxBe',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			array(
				'form' => 'was',
				'part-of-speech' => 'auxPsv',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			array(
				'form' => 'when',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'when'),
				)),
				'semantics' => 'at_time(this.event, this.adjunct)'
			),
			array(
				'form' => 'where',
				// http://www.comp.leeds.ac.uk/amalgam/tagsets/upenn.html
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'where'),
				)),
				'semantics' => 'location(this.event, this.adjunct)'
			),
			array(
				'form' => 'who',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'identity'),
				)),
				'semantics' => ''
			),
			array(
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
}
