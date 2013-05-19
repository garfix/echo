<?php

namespace agentecho\grammar;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar extends BaseGrammar
{
	public function __construct()
	{
		$this->loadGrammar(__DIR__ . '/../resources/simple.grammar');
		parent::__construct();
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

				// active declarative

				// John drives / She died
				// VP is the head constituent (head-1)
				// VP and NP agree (agreement-2)
				// NP forms the subject of VP's verb
				array(
					array('cat' => 'S',
'semantics' => '{
	S.sem = NP.sem and VP.sem and subject(S.event, S.subject);
	S.event = VP.event;
	S.subject = NP.object
}',
						'features' => array('head{?h1}' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => '?s1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax'))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s1}' => array('deepSubject' => '?syntax')))),
				),
				// Lady Lovelace was born
				array(
					array('cat' => 'S', 'features' => array('head{?h1}' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'clause' => '?s2'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax'))),
					array('cat' => 'aux'),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s2}' => array('deepSubject' => '?syntax')))),
				),

				// imperative

				// Drive! / Book that flight / She died
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = VP.sem and object(S.event, S.object);
							S.event = VP.event;
							S.object = VP.object
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'imperative', 'clause{?s1}' => null))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),

				// non-subject questions

				// Who Is John? / How many children had Lord Byron?
				// present tense
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = WhADVP.sem and VP.sem and NP.sem and subject(S.event, S.subject) and object(S.event, S.object) and request(S.request);
							S.event = VP.event;
							S.subject = NP.object;
							S.object = WhADVP.object;
							S.request = WhADVP.request
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'clause' => '?s1'))),
					array('cat' => 'WhADVP', 'features' => array('head' => array('syntax' => '?s1'))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s1}' => array('deepSubject' => '?syntax-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
				),

				// Where was John born?
				// NP delivers deepDirectObject
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = WhADVP.sem and auxPsv.sem and NP.sem and VP.sem and object(S.event, S.object) and request(S.request);
							S.event = WhADVP.event;
							S.event = VP.event;
							S.object = NP.object;
							S.request = WhADVP.request
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'wh-question', 'voice' => 'passive', 'clause' => '?s1'))),
					array('cat' => 'WhADVP', 'features' => array('head' => array('syntax' => '?s1'))),
					array('cat' => 'auxPsv', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s1}' => array('deepDirectObject' => '?syntax-2')))),
				),

				// yes-no questions

				// Was John driving?
				// VP is the head constituent (head{?h1})
				// aux, NP, and VP agree (agreement-2)
				// NP forms the object of VP's verb
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = NP.sem and VP.sem and subject(S.event, S.subject);
							S.event = VP.event;
							S.subject = NP.object
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'clause' => '?s3'))),
					array('cat' => 'aux', 'features' => array('head' => array('agreement' => '?agr'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s3}' => array('deepDirectObject' => '?syntax-1')))),
				),

				// Was the car driven by John?
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = NP1.sem and NP2.sem and VP.sem and subject(S.event, S.subject) and object(S.event, S.object);
							S.event = VP.event;
							S.subject = NP2.object;
							S.object = NP1.object
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'yes-no-question', 'voice' => 'passive', 'clause' => '?s3'))),
					array('cat' => 'aux'),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'VP', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s3}' => array('predicate' => null, 'deepSubject' => '?syntax-2', 'deepDirectObject' => '?syntax-1')))),
					array('cat' => 'passivisationPreposition'),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
				),

				// Was John a fool?
				// Was Ada Lovelace the daughter of Lord Byron?
				// The verb is 'be'
#todo see NLU, p.243: de tweede NP gaat als predicaat dienen
				array(
					array('cat' => 'S',
						'semantics' => '{
							S.sem = aux.sem and NP1.sem and NP2.sem and subject(S.event, S.subject);
							S.subject = NP2.object;
							S.subject = NP1.object
						}',
						'features' => array('head{?h1}' => array('sentenceType' => 'yes-no-question', 'voice' => 'active', 'clause' => '?s3'))),
					array('cat' => 'aux', 'features' => array('head{?h1}' => array('agreement' => null, 'syntax{?s3}' => array('type' => 'clause', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-2'))),
				),

				// How old was Mary Shelley?
				array(
					array('cat' => 'S',
						'features' => array('head{?h1}' => array('sentenceType' => 'wh-question', 'voice' => 'active', 'clause' => '?s3', 'relativeClause' => '?syntax-4')),

'semantics' => '{
	S.sem = WhADVP.sem and auxBe.sem and NP.sem and subject(S.event, S.subject) and request(S.request);
	S.event = WhADVP.object;
	S.event = auxBe.event;
	S.subject = NP.object;
	S.request = WhADVP.request
}'
					),
					array('cat' => 'WhADVP', 'features' => array('head' => array('syntax{?s3}' => array('deepDirectObject' => null)))),
					array('cat' => 'auxBe', 'features' => array('head{?h1}' => array('agreement' => '?agr', 'syntax{?s3}' => array('type' => 'clause', 'deepSubject' => '?syntax-1')))),
					array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
				),

				// How old was Mary Shelley when she died?
				array(
					array('cat' => 'S', 'features' => array('head{?h1}' => array('relativeClause' => '?syntax-1')),
# todo: should accept S1 and S2
'semantics' => '{
	S.sem = S.sem and SBar.sem;
	S.event = S.event;
	S.subject = S.subject;
	S.object = S.object;
	S.event = SBar.superEvent;
	S.request = S.request
}'

					),
					array('cat' => 'S', 'features' => array('head{?h1}' => null)),
					array('cat' => 'SBar', 'features' => array('head' => array('syntax' => '?syntax-1'))),
				)

			),
			// S-Bar, see 'The structure of modern english' - Brinton (2000) - p. 230
			// secondary clause
			'SBar' => array(
				array(
					array('cat' => 'SBar', 'features' => array('head' => array('syntax' =>
						array('type' => 'relativeClause', 'clause' => '?syntax-2',	'complementizer' => '?cat'))),
'semantics' => '{
	SBar.sem = whAdverb.sem and S.sem;
	SBar.superEvent = whAdverb.event;
	SBar.subEvent = whAdverb.adjunct;
	SBar.subEvent = S.event
}'


					),
					array('cat' => 'whAdverb', 'features' => array('head' => array(
						'syntax' => array('category' => '?cat')))),
					array('cat' => 'S', 'features' => array('head' => array('syntax' => '?syntax-2'))),
				),
			),
			'VP' => array(
				// drives
				array(
					array('cat' => 'VP',
'semantics' => '{
	VP.sem = verb.sem;
	VP.event = verb.event
}',
						'features' => array('head{?h1}' => array('syntax' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head{?h1}' => null)),
				),
				// book that flight! / sees the book
				// Name Lord Byron's children.
				// verb is the head constituent (head{?h1})
				// the verb has only 1 argument (arguments)
				// NP forms the object of verb
				array(
					array('cat' => 'VP',
						'semantics' => '{
							VP.sem = verb.sem and NP.sem and object(VP.event, VP.object);
							VP.event = VP.event;
							VP.object = NP.object
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('deepDirectObject' => '?syntax')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
				// John gives Mary flowers
				array(
					array('cat' => 'VP',
						'semantics' => '{
							S.sem = verb.sem and NP1.sem and NP2.sem and subject(S.event, S.subject) and object(S.event, S.object);
							S.event = VP.event;
							S.subject = NP1.object;
							S.object = NP2.object
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'clause')))),
# is this used? the order is wrong!
					array('cat' => 'verb', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('deepDirectObject' => '?syntax-3', 'deepIndirectObject' => '?syntax-2')), 'arguments' => 1)),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
				),
				// driven by John
				// verb is the head constituent (head{?h1})
				// NP forms the object of verb
				array(
					array('cat' => 'VP',
						'semantics' => '{
							VP.sem = verb.sem and PP.sem;
							VP.event = verb.event;
							VP.event = PP.superObject
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'clause')))),
					array('cat' => 'verb', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('preposition' => '?syntax')))),
					array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			'WhNP' => array(

			),
			'WhADVP' => array(
				array(
#todo: this interpreation is counterintuitive, especially for 'who'
					// where, when, who
					array('cat' => 'WhADVP',

'semantics' => '{
	WhADVP.sem = whAdverb.sem;
	WhADVP.request = whAdverb.adjunct;
	WhADVP.event = whAdverb.event
}',

						'features' => array('head' => array('syntax' => array(
						'preposition' => array(
							'type' => 'preposition',
							'category' => '?cat',
							'object' => array(
								'type' => 'entity', 'question' => true
							)
						)))),
					),
					array('cat' => 'whAdverb', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
				),
				// which car, how many children
				array(
					array('cat' => 'WhADVP',
'semantics' => '{
	WhADVP.sem = whwordNP.sem and NP.sem;
	WhADVP.object = whwordNP.object;
	WhADVP.object = NP.object;
	WhADVP.request = whwordNP.adjunct
}',
						'features' => array('head{?h1}' => null)),
					array('cat' => 'whwordNP', 'features' => array('head{?h1}' => array('variables' => array('role' => '?syntax')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			// Complete: See The Structure of Modern English (p. 171)
			'NP' => array(
				// John
				array(
					array('cat' => 'NP',
'semantics' => '{
	NP.sem = PN.sem;
	NP.object = PN.object
}',
						'features' => array('head{?h1}' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'PN', 'features' => array('head{?h1}' => null)),
				),
				// he
				array(
					array('cat' => 'NP',
'semantics' => '{
	NP.sem = pronoun.sem;
	NP.object = pronoun.object
}',
						'features' => array('head{?h1}' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'pronoun', 'features' => array('head{?h1}' => null)),
				),
				// the car
				array(
					array('cat' => 'NP',
						'semantics' => '{
							NP.sem = DP.sem and NBar.sem;
							NP.object = NBar.object;
							NP.object = DP.superObject
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'entity')))),
					array('cat' => 'DP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					array('cat' => 'NBar', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('determiner' => '?syntax-2')))),
				),
				// (large) car (in the lot)
				array(
					array('cat' => 'NP',
'semantics' => '{
	NP.sem = NBar.sem;
	NP.object = NBar.object
}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'entity')))),
					array('cat' => 'NBar', 'features' => array('head{?h1}' => array('syntax{?s1}' => null))),
				),
			),
			// Complete: see 'The structure of modern english' - Brinton (2000) - p. 175
			'NBar' => array(
				// car
				array(
					array('cat' => 'NBar', 'features' => array('head{?h1}' => array('syntax' => '?s1')),
'semantics' => '{
	NBar.sem = noun.sem;
	NBar.object = noun.object
}'
					),
					array('cat' => 'noun', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
				// blue car
				array(
					array('cat' => 'NBar', 'features' => array('head{?h1}' => array('syntax' => '?s1')),
'semantics' => '{
	NBar.sem = noun.sem;
	NBar.object = noun.object
}'
					),
					array('cat' => 'AP', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'NBar', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
				// car in the lot
				// the author of Paradise Lost
				array(
					array('cat' => 'NBar',
						'semantics' => '{
							NBar.sem = NBar.sem and PP.sem;
							NBar.object = NBar.object;
							NBar.object = PP.superObject
						}',
						'features' => array('head' => array('syntax' => '?s1'))),
					array('cat' => 'NBar', 'features' => array('head' => array('syntax{?s1}' => array('preposition' => '?syntax-2')))),
					array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
				),
			),
			// Proper Name
			'PN' => array(
				// John
				array(
					array('cat' => 'PN',
'semantics' => '{
	PN.sem = propernoun.sem;
	PN.object = propernoun.object
}',
						'features' => array('head{?h1}' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head{?h1}' => null)),
				),
				// Lord Byron, Ada Lovelace
				array(
					array('cat' => 'PN',
'semantics' => '{
	PN.sem = name(PN.object, propernoun1.text + " " + propernoun2.text);
	PN.object = propernoun1.object;
	PN.object = propernoun2.object
}',
						'features' => array('head' => array('syntax' => array('name' => '?firstname', 'lastname' => '?lastname', 'type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?firstname')))),
					array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
				),
				// Anne Isabella Milbanke
				array(
					array('cat' => 'PN',
'semantics' => '{
	PN.sem = name(PN.object, propernoun1.text + " " + propernoun2.text + " " + propernoun3.text);
	PN.object = propernoun1.object;
	PN.object = propernoun2.object;
	PN.object = propernoun3.object
}',
						'features' => array('head' => array('syntax' => array('name' => '?firstname', 'middlename' => '?middlename', 'lastname' => '?lastname', 'type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?firstname')))),
					array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?middlename')))),
					array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
				),
				// Jan de Wit
				array(
					array('cat' => 'PN',
'semantics' => '{
	PN.sem = propernoun1.sem and propernoun2.sem;
	PN.object = propernoun1.object;
	PN.object = propernoun2.object;
}',
						'features' => array('head{?h1}' => array('syntax' => array('type' => 'entity')))),
					array('cat' => 'propernoun', 'features' => array('head{?h1}' => null)),
					array('cat' => 'insertion', 'features' => array('head{?h1}' => null)),
					array('cat' => 'propernoun', 'features' => array('head{?h1}' => null)),
				),
			),
			// Prepositional Phrase
			'PP' => array(
				// in the lot
				array(
					array('cat' => 'PP',
						#todo not used
						'semantics' => '{
							PP.sem = preposition.sem and NP.sem;
							PP.superObject = preposition.superObject;
							PP.subObject = preposition.subObject;
							PP.subObject = NP.object
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'preposition')))),
					array('cat' => 'preposition', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => null, 'object' => '?syntax')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
				),
			),
			// Adjectival Phrase
			'AP' => array(
				// red
				array(
					array('cat' => 'AP',
						'semantics' => '',
						'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'adjective', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
				// fiercely barking
				array(
					array('cat' => 'AP',
						'semantics' => '',
						'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'AdvP', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'adjective', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
				// bright red
				array(
					array('cat' => 'AP',
						'semantics' => '',
						'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'degreeAdverb', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'adjective', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
			),
			// Adverbial Phrase
			'AdvP' => array(
				// quickly
				array(
					array('cat' => 'AdvP',
						'semantics' => '',
						'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'adverb', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
				// very quickly
				array(
					array('cat' => 'AdvP',
						'semantics' => '',
						'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'degreeAdverb', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
					array('cat' => 'adverb', 'features' => array('head{?h1}' => array('syntax' => '?s1'))),
				),
			),
			// Determiner Phrase
			// See 'The structure of modern english' - Brinton (2000) - p. 170
			'DP' => array(
				// the
				array(
					array('cat' => 'DP',
#todo not used
					'semantics' => '{
						DP.sem = determiner.sem
					}',
					'features' => array('head{?h1}' => array('syntax' => array('type' => 'determiner')))),
					array('cat' => 'determiner', 'features' => array('head{?h1}' => null)),
				),
				// Byron's
				array(
					array('cat' => 'DP',
						'semantics' => '{
							DP.sem = NP.sem and possessiveMarker.sem;
							DP.subObject = possessiveMarker.subObject;
							DP.superObject = possessiveMarker.superObject;
							DP.subObject = NP.object
						}',
						'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => 'determiner')))),
					array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax'))),
					array('cat' => 'possessiveMarker', 'features' => array('head{?h1}' => array('syntax{?s1}' => array('type' => null, 'object' => '?syntax')))),
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
							'deepDirectObject' => '?s2', 'adverb' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?s3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax{?s2}' => null))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense' => null)))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?s3'))),
					),
				),

				// passive declarative sentence
				// (yes, ) Lord Byron was influenced by John Milton
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('tense' => '?t1', 'predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb' => '?adverb-1')))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense' => '?t1')))),
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
							'deepSubject' => '?syntax-1', 'adverb' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
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
							'deepDirectObject' => '?s2', 'adverb' => '?adverb-1', 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?s2'))),
						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
					),
				),

				// active declarative sentence with third argument
				// John gives Mary flowers
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('deepIndirectObject' => null))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'deepIndirectObject' => '?syntax-3')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					),
				),

				// active declarative sentence with 'be' as verb
				// (yes, ) Ada Lovelace was the daughter of Lord Byron
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('predicate' => 'be'))),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb' => '?adverb-1')))),
						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
					),
				),

				// simple active declarative sentence
				// John likes Mary
				array(
					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
					'rule' => array(
						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2')))),
						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
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
//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//					)
//				),

				array(
					'condition' => array('head' => array('syntax' => array('name' => null))),
					'rule' => array(
						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
					)
				),
			),
			'PN' => array(

//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//					)
//				),
				array(
					'condition' => array('head' => array('syntax' => array('name' => null, 'middlename' => null, 'lastname' => null))),
					'rule' => array(
						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?middlename')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
					)
				),

				array(
					'condition' => array('head' => array('syntax' => array('name' => null, 'lastname' => null))),
					'rule' => array(
						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'lastname' => '?lastname')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
					)
				),

				array(
					'condition' => array('head' => array('syntax' => array('name' => null))),
					'rule' => array(
						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
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