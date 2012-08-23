<?php

namespace agentecho\grammar;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar extends BaseGrammar
{
	/**
	 * Returns true if $words for a proper noun.
	 * @param $words
	 * @return bool
	 */
	public function isProperNoun($words)
	{
		// Sjaak
		// Sjaak Zwart
		// Kees Willem Zwart
		// Sjaak (de|van|van de|van der) Zwart
		$exp = '/^([A-Z][a-z]+)( [A-Z][a-z]+)?( (de |van |van de |van der )?[A-Z][a-z]+)?$/';

		return preg_match($exp, implode(' ', $words));
	}

	public function unglue($word)
	{
		return array($word);
	}

	/**
	 * Returns true if $word belongs to the $partOfSpeech category.
	 *
	 * @param string $word
	 * @param string $partOfSpeech
	 * @return bool
	 */
	public function isWordAPartOfSpeech($word, $partOfSpeech)
	{
		$result = false;

		if (isset($this->lexicon[$word])) {

			if (isset($this->lexicon[$word][$partOfSpeech])) {
				$result = true;
			}

		} else {

			// all words can be proper nouns
			if ($partOfSpeech == 'propernoun') {
				$result = true;
			}
		}

		return $result;
	}

	public function formatTime($time)
	{
		if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $time, $matches)) {
			$year = $matches[1];
			$month = (int)$matches[2];
			$day = (int)$matches[3];

			$monthWord = $this->getWordForFeatures('noun',
				array('monthIndex' => $month)
			);

			return $day . ' ' . $monthWord . ' ' . $year;
		} else {
			return $time;
		}
	}

	public function getParseRules()
	{
		// Find parse rules:
		//
		// http://nlp.stanford.edu:8080/parser/index.jsp

		return array(
			'S' => array(

				// passive declarative
				// The car was driven by John
				#todo Deze regel wordt niet gebruikt!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'relation' => '?sem-3'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'aux', 'features' => array('head-1' => null)),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-3' => array('predicate' => null, 'arg1' => '?sem-2', 'arg2' => '?sem-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
				),

				// active declarative

				// John drives
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'active', 'relation' => '?sem-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg1' => '?sem')))),
				),
				// John was driving
				#todo Deze regel wordt niet gebruikt!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'relation' => '?sem-2'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem'))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-2' => array('arg1' => '?sem')))),
				),

				// imperative

				// Drive! / Book that flight.
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'imperative', 'relation' => '?sem-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('sem-1' => null))),
				),

				// non-subject questions

				// Who Is John? / How many children had Lord Byron?
				// present tense
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'relation' => '?sem-1'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg1' => '?sem-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
				),

				// How many children did John have?
				// NP delivers arg1
#todo alleen-engels constructie!
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'relation' => '?sem-1'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxDo', 'features' => array('head-1' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg1' => '?sem-2')))),
				),
				// Where was John born?
				// NP delivers arg2
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'passive', 'relation' => '?sem-1'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('sem-1' => null))),
					array('cat' => 'auxPsv', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-1' => array('arg2' => '?sem-2')))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'relation' => '?sem-3'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-3' => array('arg2' => '?sem-1')))),
				),

				// Was the car driven by John?
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'passive', 'relation' => '?sem-3'))),
					array('cat' => 'aux'),
					array('cat' => 'NP', 'features' => array('head-2' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'sem-3' => array('predicate' => null, 'arg1' => '?sem-2', 'arg2' => '?sem-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head-3' => array('sem' => '?sem-2'))),
				),

				// Was John a fool?
				// The verb is 'be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'relation' => '?sem-3'))),
					array('cat' => 'aux', 'features' => array('head-1' => array('agreement-2' => null, 'sem-3' => array('type' => 'relation', 'arg1' => '?sem-1', 'arg2' => '?sem-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'sem' => '?sem-2'))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('sem' => array('type' => 'relation')))),
					array('cat' => 'verb', 'features' => array('head-1' => null)),
				),
				// book that flight! / sees the book
				// verb is the head constituent (head-1)
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('sem-1' => array('type' => 'relation')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem-1' => array('arg2' => '?sem')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
				// John gives Mary flowers
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('sem-1' => array('type' => 'relation')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem-1' => array('arg2' => '?sem-3', 'arg3' => '?sem-2')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-3'))),
				),
				// driven by John
				// verb is the head constituent (head-1)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('sem-1' => array('type' => 'relation')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('sem-1' => array('preposition' => '?sem')))),
					array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			'WhNP' => array(
				// where, who
				array(
					array('cat' => 'WhNP', 'features' => array('head' => '?head')),
					array('cat' => 'whword', 'features' => array('head' => '?head')),
				),
				// which car, how many children
				array(
					array('cat' => 'WhNP', 'features' => array('head-1' => null)),
					array('cat' => 'whwordNP', 'features' => array('head-1' => array('variables' => array('role' => '?sem')))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			'NP' => array(
				// John
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem' => array('type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head-1' => null)),
				),
				// he
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem' => array('type' => 'entity')))),
					array('cat' => 'pronoun', 'features' => array('head-1' => null)),
				),
				// the car
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('type' => 'entity')))),
					array('cat' => 'DP', 'features' => array('head' => array('sem' => '?sem-2'))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('determiner' => '?sem-2')))),
				),
				// (large) car (in the lot)
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('sem-1' => array('type' => 'entity')))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => null))),
				),
			),
			// For N-bar, see 'The structure of modern english' - Brinton (2000) - p. 175
			'NBar' => array(
				// car
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => null))),
					array('cat' => 'noun', 'features' => array('head-1' => array('sem-1' => null))),
				),
				// car in the lot
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => null))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('sem-1' => array('preposition' => '?sem-2')))),
					array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem-2'))),
				),
			),
			// Prepositional Phrase
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP', 'features' => array('head-1' => array('sem-1' => array('type' => 'preposition')))),
					array('cat' => 'preposition', 'features' => array('head-1' => array('sem-1' => array('type' => null, 'object' => '?sem')))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
				),
			),
			// Determiner Phrase
			// See 'The structure of modern english' - Brinton (2000) - p. 170
			'DP' => array(
				// the
				array(
					array('cat' => 'DP', 'features' => array('head-1' => array('sem' => array('type' => 'determiner')))),
					array('cat' => 'determiner', 'features' => array('head-1' => null)),
				),
				// Byron's
				array(
					array('cat' => 'DP', 'features' => array('head-1' => array('sem-1' => array('type' => 'determiner')))),
					array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem'))),
					array('cat' => 'possessiveMarker', 'features' => array('head-1' => array('sem-1' => array('type' => null, 'object' => '?sem')))),
				),
			)
		);
	}

	public function getGenerationRules()
	{
		// de volgorde van deze regels wijkt waarschijnlijk af van de syntax regels hierboven;
		// de volgorde van deze regels is namelijk die van meest restrictief naar minst restrictief
		// de volgorde van de regels hierboven is die van aflopende trefkans

		// merk op dat de sem juist niet gedeeld wordt met de head van de rule; ze worden juist gescheiden

		// de 'rule'-attributen zijn nodig om te bepalen hoe de phrase specification verdeeld wordt over de syntactische regel

		return array(
			'S' => array(

				// passive declarative sentence with a preposition
				// (yes, ) Lord Byron was born in London
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'relation' => array('preposition' => null, 'arg2' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'tense' => '?tense',
							'arg2' => '?sem-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?sem-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('sem' => array('predicate' => 'be', 'tense-1' => null)))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'sem' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem-3' => null))),
					),
				),

				// passive declarative sentence
				// (yes, ) Lord Byron was influenced by John Milton
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('tense-1' => null, 'predicate' => '?pred', 'arg1' => '?sem-1', 'arg2' => '?sem-2', 'adverb-1' => null)))),
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('sem' => array('predicate' => 'be', 'tense-1' => null)))),
						array('cat' => 'VP', 'features' => array('head' => array('sem' => array('predicate' => '?pred')))),
						array('cat' => 'passivisationPreposition', 'features' => array()),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-1'))),
					)
				),

				// active declarative past-tense sentence with a preposition
				// (yes, ) Lord Byron was married to Anne Isabella Milbanke
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'relation' => array('preposition' => null, 'arg1' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'tense' => '?tense',
							'arg1' => '?sem-1', 'arg2' => '?sem-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?sem-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
						array('cat' => 'auxBe', 'features' => array('head' => array('sem' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'sem' => array('predicate' => '?pred', 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem-3' => null))),
					),
				),

				// active declarative sentence with a preposition
				// Lord Byron was born in London
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'relation' => array('preposition' => null, 'arg2' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'tense' => '?tense',
							'arg2' => '?sem-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?sem-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-2' => null))),
						array('cat' => 'auxBe', 'features' => array('head' => array('sem' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'sem' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem-3' => null))),
					),
				),

				// active declarative sentence with third argument
				// John gives Mary flowers
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'relation' => array('arg3' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'arg1' => '?sem-1', 'arg2' => '?sem-2', 'arg3' => '?sem-3')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem' => '?sem-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'sem' => array('predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-3'))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
					),
				),

				// active declarative sentence with 'be' as verb
				// (yes, ) Ada Lovelace was the daughter of Lord Byron
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'relation' => array('predicate' => 'be'))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'tense' => '?tense', 'arg1' => '?sem-1', 'arg2' => '?sem-2', 'adverb-1' => null)))),
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
						array('cat' => 'auxBe', 'features' => array('head' => array('sem' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
					),
				),

				// simple active declarative sentence
				// John likes Mary
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('predicate' => '?pred', 'tense' => '?tense', 'arg1' => '?sem-1', 'arg2' => '?sem-2')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'sem-1' => null))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'sem' => array('predicate' => '?pred', 'tense' => '?tense')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2'))),
					),
				),

				// 'yes' or 'no' answers
				array(
					'condition' => array('head' => array('relation' => array('modifier' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('relation' => array('modifier' => '?modifier-1')))),
						array('cat' => 'adverb', 'features' => array('head' => array('sem' => '?modifier-1'))),
					)
				),
			),

			'premodifier' => array(
				// yes, ...
				array(
					'condition' => array('head' => array('sem' => null)),
					'rule' => array(
						array('cat' => 'premodifier', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
						array('cat' => 'adverb', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('sem' => array('category' => 'comma')))),
					)
				),
				// null rule, needed to fill the empty optional clause
				array(
					'condition' => array(),
					'rule' => array()
				)
			),

			'NP' => array(
				array(
					'condition' => array('head' => array('sem' => array('category' => null, 'preposition' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'preposition' => '?sem', 'determiner' => '?det')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'determiner' => '?det')))),
						array('cat' => 'PP', 'features' => array('head' => array('sem' => '?sem')))
					)
				),
				array(
					'condition' => array('head' =>array('sem' =>  array('category' => null, 'determiner' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat', 'determiner' => array('category' => '?det'))))),
						array('cat' => 'determiner', 'features' => array('head' => array('sem' => array('category' => '?det')))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('category' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
						array('cat' => 'noun', 'features' => array('head' => array('sem' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('name' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('sem' => array('name' => '?name')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('sem' => array('name' => '?name')))),
					)
				),
			),
			'VP' => array(
//				// gives Mary flowers
//				array(
//					'condition' => array('head' => array('sem' => array('predicate' => null, 'category' => null, 'arg3' => null))),
//					'rule' => array(
//						array('cat' => 'VP', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred', 'arg2' => '?sem-2', 'arg3' => '?sem-3')))),
//						array('cat' => 'verb', 'features' => array('head' => array('tense' => '?tense', 'sem' => array('predicate' => '?pred')))),
//						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-3'))),
//						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?sem-2')))
//					)
//				),
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null, 'category' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('sem' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('sem' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array())
					)
				),
				array(
					'condition' => array('head' => array('sem' => array('predicate' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('sem' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('sem' => array('tense' => '?tense', 'predicate' => '?pred')))),
					)
				),
			),
			'PP' => array(
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'PP', 'features' => array('head' => array('sem' => array('category' => '?category', 'object' => '?obj')))),
						array('cat' => 'preposition', 'features' => array('head' => array('sem' => array('category' => '?category')))),
						array('cat' => 'NP', 'features' => array('head' => array('sem' => '?obj'))),
					)
				),
			),

		);
	}
}