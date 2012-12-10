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

	public function getParseRules()
	{
		// Find parse rules:
		//
		// http://nlp.stanford.edu:8080/parser/index.jsp

		return array(
			'S' => array(

				// passive declarative
				// The car was driven by John
//				#todo Deze regel wordt niet gebruikt!
//				array(
//					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'clause' => '?syntax-3'))),
//					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
//					array('cat' => 'aux', 'features' => array('head-1' => null)),
//					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-3' => array('predicate' => null, 'deepSubject' => '?syntax-2', 'deepDirectObject' => '?syntax-1')))),
//					array('cat' => 'passivisationPreposition'),
//					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
//				),

				// active declarative

				// John drives / She died
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb
				array(
					array('cat' => 'S',
'semantics' => '
	S.sem = NP.sem and VP.sem and subject(S.event, S.subject);
	S.event = VP.event;
	S.subject = NP.object
',
						'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => '?syntax-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-1' => array('deepSubject' => '?syntax')))),
				),
				// Lady Lovelace was born
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'clause' => '?syntax-2'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax'))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-2' => array('deepSubject' => '?syntax')))),
				),

				// imperative

				// Drive! / Book that flight / She died
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'imperative', 'clause' => '?syntax-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('syntax-1' => null))),
				),

				// non-subject questions

				// Who Is John? / How many children had Lord Byron?
				// present tense
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'clause' => '?syntax-1'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('syntax-1' => null))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-1' => array('deepSubject' => '?syntax-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
				),

				// Where was John born?
				// NP delivers deepDirectObject
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'passive', 'clause' => '?syntax-1'))),
					array('cat' => 'WhNP', 'features' => array('head' => array('syntax-1' => null))),
					array('cat' => 'auxPsv', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-1' => array('deepDirectObject' => '?syntax-2')))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head-1)
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'clause' => '?syntax-3'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-3' => array('deepDirectObject' => '?syntax-1')))),
				),

				// Was the car driven by John?
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'passive', 'clause' => '?syntax-3'))),
					array('cat' => 'aux'),
					array('cat' => 'NP', 'features' => array('head-2' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'VP', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-3' => array('predicate' => null, 'deepSubject' => '?syntax-2', 'deepDirectObject' => '?syntax-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head-3' => array('syntax' => '?syntax-2'))),
				),

				// Was John a fool?
				// The verb is 'be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'clause' => '?syntax-3'))),
					array('cat' => 'aux', 'features' => array('head-1' => array('agreement-2' => null, 'syntax-3' => array('type' => 'clause', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
				),

				// How old was Mary Shelley?
				array(
					array('cat' => 'S',
						'features' => array('head-1' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'clause' => '?syntax-3', 'relativeClause' => '?syntax-4')),

'semantics' => '
	S.sem = WhNP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject);
	S.event = WhNP.object;
	S.event = auxBe.event;
	S.subject = NP.object;
	S.request = WhNP.request
'
					),
					array('cat' => 'WhNP', 'features' => array('head' => array('syntax-3' => array('deepDirectObject' => null)))),
					array('cat' => 'auxBe', 'features' => array('head-1' => array('agreement' => '?agr', 'syntax-3' => array('type' => 'clause', 'deepSubject' => '?syntax-1')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
				),

				// How old was Mary Shelley when she died?
				array(
					array('cat' => 'S', 'features' => array('head-1' => array('relativeClause' => '?syntax-1')),
# todo: should accept S1 and S2
'semantics' => '
	S.sem = S.sem and SBar.sem;
	S.event = S.event;
	S.subject = S.subject;
	S.object = S.object;
	S.event = SBar.superEvent;
	S.request = S.request
'

					),
					array('cat' => 'S', 'features' => array('head-1' => null)),
					array('cat' => 'SBar', 'features' => array('head' => array('syntax' => '?syntax-1'))),
				)

			),
			// S-Bar, see 'The structure of modern english' - Brinton (2000) - p. 230
			// secondary clause
			'SBar' => array(
				array(
					array('cat' => 'SBar', 'features' => array('head' => array('syntax' =>
						array('type' => 'relativeClause', 'clause' => '?syntax-2',	'complementizer' => '?cat'))),
'semantics' => '
	SBar.sem = whword.sem and S.sem;
	SBar.superEvent = whword.superEvent;
	SBar.subEvent = whword.subEvent;
	SBar.subEvent = S.event
'


					),
					array('cat' => 'whword', 'features' => array('head' => array(
						'syntax' => array('category' => '?cat')))),
					array('cat' => 'S', 'features' => array('head' => array('syntax' => '?syntax-2'))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP',
'semantics' => '
	VP.sem = verb.sem;
	VP.event = verb.event
',
						'features' => array('head-1' => array('syntax' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head-1' => null)),
				),
				// book that flight! / sees the book
				// verb is the head constituent (head-1)
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('syntax-1' => array('deepDirectObject' => '?syntax')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
				// John gives Mary flowers
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('syntax-1' => array('deepDirectObject' => '?syntax-3', 'deepIndirectObject' => '?syntax-2')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
				),
				// driven by John
				// verb is the head constituent (head-1)
				// NP forms the object of verb
				array(
					array('cat' => 'VP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head-1' => array('syntax-1' => array('preposition' => '?syntax')))),
					array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			'WhNP' => array(
				array(
#todo: this interpreation is counterintuitive, especially for 'who'
					// where, when, who
					array('cat' => 'WhNP', 'features' => array('head' => array('syntax' => array(
						'preposition' => array(
							'type' => 'preposition',
							'category' => '?cat',
							'object' => array(
								'type' => 'entity', 'question' => true
							)
						)))),
					),
					array('cat' => 'whword', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
				),
				// which car, how many children
				array(
					array('cat' => 'WhNP',
'semantics' => '
	WhNP.sem = whwordNP.sem and NP.sem;
	WhNP.object = whwordNP.object;
	WhNP.object = NP.object;
	WhNP.request = whwordNP.request
',
						'features' => array('head-1' => null)),
					array('cat' => 'whwordNP', 'features' => array('head-1' => array('variables' => array('role' => '?syntax')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			'NP' => array(
				// John
				array(
					array('cat' => 'NP',
'semantics' => '
	NP.sem = propernoun.sem;
	NP.object = propernoun.object
',
						'features' => array('head-1' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head-1' => null)),
				),
				// he
				array(
					array('cat' => 'NP',
'semantics' => '
	NP.sem = pronoun.sem;
	NP.object = pronoun.object
',
						'features' => array('head-1' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'pronoun', 'features' => array('head-1' => null)),
				),
				// the car
				array(
					array('cat' => 'NP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'entity')))),
					array('cat' => 'DP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('syntax-1' => array('determiner' => '?syntax-2')))),
				),
				// (large) car (in the lot)
				array(
					array('cat' => 'NP',
'semantics' => '
	NP.sem = NBar.sem;
	NP.object = NBar.object
',
						'features' => array('head-1' => array('syntax-1' => array('type' => 'entity')))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('syntax-1' => null))),
				),
			),
			// For N-bar, see 'The structure of modern english' - Brinton (2000) - p. 175
			'NBar' => array(
				// car
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('syntax-1' => null)),
'semantics' => '
	NBar.sem = noun.sem;
	NBar.object = noun.object
'
					),
					array('cat' => 'noun', 'features' => array('head-1' => array('syntax-1' => null))),
				),
				// car in the lot
				array(
					array('cat' => 'NBar', 'features' => array('head-1' => array('syntax-1' => null))),
					array('cat' => 'NBar', 'features' => array('head-1' => array('syntax-1' => array('preposition' => '?syntax-2')))),
					array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
				),
			),
			// Prepositional Phrase
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'preposition')))),
					array('cat' => 'preposition', 'features' => array('head-1' => array('syntax-1' => array('type' => null, 'object' => '?syntax')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			// Determiner Phrase
			// See 'The structure of modern english' - Brinton (2000) - p. 170
			'DP' => array(
				// the
				array(
					array('cat' => 'DP', 'features' => array('head-1' => array('syntax' => array('type' => 'determiner')))),
					array('cat' => 'determiner', 'features' => array('head-1' => null)),
				),
				// Byron's
				array(
					array('cat' => 'DP', 'features' => array('head-1' => array('syntax-1' => array('type' => 'determiner')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
					array('cat' => 'possessiveMarker', 'features' => array('head-1' => array('syntax-1' => array('type' => null, 'object' => '?syntax')))),
				),
			)
		);
	}

	public function getGenerationRules()
	{
		// de volgorde van deze regels wijkt waarschijnlijk af van de syntax regels hierboven;
		// de volgorde van deze regels is namelijk die van meest restrictief naar minst restrictief
		// de volgorde van de regels hierboven is die van aflopende trefkans

		// merk op dat de syntax juist niet gedeeld wordt met de head van de rule; ze worden juist gescheiden

		// de 'rule'-attributen zijn nodig om te bepalen hoe de phrase specification verdeeld wordt over de syntactische regel

		return array(
			'S' => array(

				// passive declarative sentence with a preposition
				// (yes, ) Lord Byron was born in London
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'clause' => array('preposition' => null, 'deepDirectObject' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
							'deepDirectObject' => '?syntax-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-2' => null))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense-1' => null)))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax-3' => null))),
					),
				),

				// passive declarative sentence
				// (yes, ) Lord Byron was influenced by John Milton
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('tense-1' => null, 'predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb-1' => null)))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense-1' => null)))),
						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('predicate' => '?pred')))),
						array('cat' => 'passivisationPreposition', 'features' => array()),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-1'))),
					)
				),

				// active declarative sentence with a preposition
				// Lord Byron died in Missolonghi
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('preposition' => null, 'deepSubject' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
							'deepSubject' => '?syntax-1', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-1' => null))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax-3' => null))),
					),
				),

				// active declarative past-tense sentence with a preposition
				// (yes, ) Lord Byron was married to Anne Isabella Milbanke
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('preposition' => null, 'deepSubject' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
//							'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-2' => null))),
//						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
//						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
//						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax-3' => null))),
//					),
//				),

				// active declarative sentence with a preposition
				// Lord Byron was born in London
				// (yes, ) Lord Byron was married to Anne Isabella Milbanke
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('preposition' => null, 'deepDirectObject' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
							'deepDirectObject' => '?syntax-2', 'adverb-1' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-2' => null))),
						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax-3' => null))),
					),
				),

				// active declarative sentence with third argument
				// John gives Mary flowers
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('deepIndirectObject' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'deepIndirectObject' => '?syntax-3')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => '?syntax-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					),
				),

				// active declarative sentence with 'be' as verb
				// (yes, ) Ada Lovelace was the daughter of Lord Byron
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('predicate' => 'be'))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb-1' => null)))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-1' => null))),
						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),//'agreement-2' => null, 'predicate' => '?pred', array('tense' => '?tense')
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					),
				),

				// simple active declarative sentence
				// John likes Mary
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement-2' => null, 'syntax-1' => null))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement-2' => null, 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					),
				),

				// 'yes' or 'no' answers
				array(
					'condition' => array('head' => array('clause' => array('modifier' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('modifier' => '?modifier-1')))),
						array('cat' => 'adverb', 'features' => array('head' => array('syntax' => '?modifier-1'))),
					)
				),
			),

			'premodifier' => array(
				// yes, ...
				array(
					'condition' => array('head' => array('syntax' => null)),
					'rule' => array(
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
						array('cat' => 'adverb', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
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
					'condition' => array('head' => array('syntax' => array('category' => null, 'preposition' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'preposition' => '?syntax', 'determiner' => '?det')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'determiner' => '?det')))),
						array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax')))
					)
				),
				array(
					'condition' => array('head' =>array('syntax' =>  array('category' => null, 'determiner' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'determiner' => array('category' => '?det'))))),
						array('cat' => 'determiner', 'features' => array('head' => array('syntax' => array('category' => '?det')))),
						array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('syntax' => array('category' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
						array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
					)
				),
				array(
					'condition' => array('head' => array('syntax' => array('name' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
					)
				),



			),
			'VP' => array(
//				// gives Mary flowers
//				array(
//					'condition' => array('head' => array('syntax' => array('predicate' => null, 'category' => null, 'deepIndirectObject' => null))),
//					'rule' => array(
//						array('cat' => 'VP', 'features' => array('head' => array('tense' => '?tense', 'syntax' => array('predicate' => '?pred', 'deepDirectObject' => '?syntax-2', 'deepIndirectObject' => '?syntax-3')))),
//						array('cat' => 'verb', 'features' => array('head' => array('tense' => '?tense', 'syntax' => array('predicate' => '?pred')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2')))
//					)
//				),
				array(
					'condition' => array('head' => array('syntax' => array('predicate' => null, 'category' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array())
					)
				),
				array(
					'condition' => array('head' => array('syntax' => array('predicate' => null))),
					'rule' => array(
						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
						array('cat' => 'verb', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
					)
				),
			),
			'PP' => array(
				array(
					'condition' => array(),
					'rule' => array(
						array('cat' => 'PP', 'features' => array('head' => array('syntax' => array('category' => '?category', 'object' => '?obj')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?category')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?obj'))),
					)
				),
			),

		);
	}
}