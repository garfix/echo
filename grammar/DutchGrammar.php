<?php

namespace agentecho\grammar;

class DutchGrammar extends SimpleGrammar
{
	public function __construct()
	{
		parent::__construct();
		$this->loadGenerationGrammar(__DIR__ . '/../resources/dutch.generation.grammar');
	}

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
			array(
				'form' => ',',
				'part-of-speech' => 'punctuationMark',
				'features' => array(
					'head' => array('syntax' => array('category' => 'comma')),
					'space' => 'after_only'
				)
			),
			array(
				'form' => '\'s',
				'part-of-speech' => 'possessiveMarker',
				'features' => array('head' => array(
					'syntax' => array('category' => 'possessive'),
				)),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'auteur',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'syntax' => array('category' => 'author'),
				)),
				'semantics' => 'isa(this.object, Author)'
			),
			array(
				'form' => 'ben',
				'part-of-speech' => 'verb',
				'features' => array('person' => 1, 'number' => 'singular', 'head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'beïnvloed',
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
				'form' => 'boek',
				'part-of-speech' => 'verb',
				'features' => array('head' => array(
					'agreement' => array('person' => 2, 'number' => 'singular'),
					'syntax' => array('predicate' => 'book'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'de',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'the'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'de',
				'part-of-speech' => 'insertion',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'die',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'that'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'door',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'by'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'door',
				'part-of-speech' => 'passivisationPreposition',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'dochter',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'daughter'),
					)
				),
				'semantics' => 'isa(this.object, Daughter)'
			),
			array(
				'form' => 'een',
				'part-of-speech' => 'determiner',
				'features' => array('head' => array(
					'syntax' => array('category' => 'a'),

				)),
				'semantics' => ''
			),
			array(
				'form' => 'en',
				'part-of-speech' => 'conjunction',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'geboren',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'bear'),
					)
				),
				'semantics' => 'isa(this.event, Bear)'
			),
			array(
				'form' => 'getrouwd',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'marry', 'tense' => 'past'),
					)
				),
				'semantics' => 'isa(this.event, Marry)'
			),
			array(
				'form' => 'had',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'have', 'tense' => 'past'),
					)
				),
				'semantics' => 'isa(this.event, Have)'
			),
			array(
				'form' => 'het',
				'part-of-speech' => 'article',
				'features' => array('head' => array(
				)),
				'semantics' => ''
			),
			array(
				'form' => 'hoeveel',
				'part-of-speech' => 'whwordNP',
				'features' => array(
					'head' => array(
						'syntax' => array(
							'deepDirectObject{?arg}' => array('determiner' => array('type' => 'determiner', 'question' => true, 'category' => 'many')),
						),
						'variables' => array('role' => '?arg')
					)
				),
				'semantics' => 'manner(this.object, this.adjunct) and many(this.object)'
			),
			array(
				'form' => 'ik',
				'part-of-speech' => 'pronoun',
				'features' => array('person' => 1, 'number' => 'singular', 'head' => array(
				)),
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
				'form' => 'ja',
				'part-of-speech' => 'adverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'yes'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'januari',
				'part-of-speech' => 'noun',
				'features' => array(
					'head' => array(
						'syntax' => array('category' => 'january', 'monthIndex' => 1),
					),
				),
				'semantics' => ''
			),
			array(
				'form' => 'kinderen',
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
				'form' => 'met',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'to'),
				)),
				'semantics' => 'to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'noem',
				'part-of-speech' => 'verb',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'name'),
					)
				),
				'semantics' => 'isa(this.event, Name)'
			),
			array(
				'form' => 'op',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'on'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'van',
				'part-of-speech' => 'preposition',
				'features' => array('head' => array(
					'syntax' => array('category' => 'of'),
				)),
				'semantics' => 'belong_to(this.superObject, this.subObject)'
			),
			array(
				'form' => 'vlucht',
				'part-of-speech' => 'noun',
				'features' => array('head' => array(
					'agreement' => array('person' => 3, 'number' => 'singular'),
					'syntax' => array('category' => 'flight'),
				)),
				'semantics' => ''
			),
			array(
				'form' => 'was',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be'),
					)
				),
				'semantics' => ''
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
				'form' => 'werd',
				'part-of-speech' => 'aux',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			array(
				'form' => 'werd',
				'part-of-speech' => 'auxPsv',
				'features' => array(
					'head' => array(
						'syntax' => array('predicate' => 'be', 'tense' => 'past'),
					)
				),
				'semantics' => 'tense(this.event, Past)'
			),
			array(
				'form' => 'waar',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'where'),
				)),
				'semantics' => 'location(this.event, this.adjunct)'
			),
			array(
				'form' => 'wanneer',
				'part-of-speech' => 'whAdverb',
				'features' => array('head' => array(
					'syntax' => array('category' => 'when'),
				)),
				'semantics' => 'at_time(this.event, this.adjunct)'
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
}
