<?php

namespace agentecho\grammar;

/**
 * I've called this common denomenator of the English and Dutch grammars 'Simple' for no special reason.
 */
abstract class SimpleGrammar extends BaseGrammar
{
	public function __construct()
	{
		$this->loadParseGrammar(__DIR__ . '/../resources/simple.parse.grammar');
		$this->loadGenerationGrammar(__DIR__ . '/../resources/simple.generation.grammar');
		parent::__construct();
	}

	public function unglue($word)
	{
		return array($word);
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
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive', 'clause' => array('preposition' => null, 'deepDirectObject' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
//							'deepDirectObject' => '?s2', 'adverb' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?s3'))))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax{?s2}' => null))),
//						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense' => null)))),
//						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
//						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?s3'))),
//					),
//				),

//				// passive declarative sentence
//				// (yes, ) Lord Byron was influenced by John Milton
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'passive')),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('tense' => '?t1', 'predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb' => '?adverb-1')))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
//						array('cat' => 'auxPsv', 'features' => array('head' => array('syntax' => array('predicate' => 'be', 'tense' => '?t1')))),
//						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('predicate' => '?pred')))),
//						array('cat' => 'passivisationPreposition', 'features' => array()),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-1'))),
//					)
//				),

//				// active declarative sentence with a preposition
//				// Lord Byron died in Missolonghi
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('preposition' => null, 'deepSubject' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
//							'deepSubject' => '?syntax-1', 'adverb' => null, 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
//						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
//						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
//					),
//				),

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

//				// active declarative sentence with a preposition
//				// Lord Byron was born in London
//				// (yes, ) Lord Byron was married to Anne Isabella Milbanke
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('preposition' => null, 'deepDirectObject' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense',
//							'deepDirectObject' => '?s2', 'adverb' => '?adverb-1', 'preposition' => array('category' => '?prepcat', 'object' => '?syntax-3'))))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?s2'))),
//						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),
//						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),// 'tense' => '?tense')))),
//						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?prepcat')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
//					),
//				),
//
//				// active declarative sentence with third argument
//				// John gives Mary flowers
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('deepIndirectObject' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'deepIndirectObject' => '?syntax-3')))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
//						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-3'))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
//					),
//				),

//				// active declarative sentence with 'be' as verb
//				// (yes, ) Ada Lovelace was the daughter of Lord Byron
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active', 'clause' => array('predicate' => 'be'))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2', 'adverb' => '?adverb-1')))),
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => '?adverb-1'))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
//						array('cat' => 'auxBe', 'features' => array('head' => array('syntax' => null))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
//					),
//				),

//				// simple active declarative sentence
//				// John likes Mary
//				array(
//					'condition' => array('head' => array('sentenceType' => 'declarative', 'voice' => 'active')),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('predicate' => '?pred', 'tense' => '?tense', 'deepSubject' => '?syntax-1', 'deepDirectObject' => '?syntax-2')))),
//						array('cat' => 'NP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => '?syntax-1'))),
//						array('cat' => 'VP', 'features' => array('head' => array('agreement' => '?agr', 'syntax' => array('predicate' => '?pred', 'tense' => '?tense')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?syntax-2'))),
//					),
//				),
//
//				// 'yes' or 'no' answers
//				array(
//					'condition' => array('head' => array('clause' => array('modifier' => null))),
//					'rule' => array(
//						array('cat' => 'S', 'features' => array('head' => array('clause' => array('modifier' => '?modifier-1')))),
//						array('cat' => 'adverb', 'features' => array('head' => array('syntax' => '?modifier-1'))),
//					)
//				),
			),

			'premodifier' => array(
//				// yes, ...
//				array(
//					'condition' => array('head' => array('syntax' => null)),
//					'rule' => array(
//						array('cat' => 'premodifier', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
//						array('cat' => 'adverb', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
//						array('cat' => 'punctuationMark', 'features' => array('head' => array('syntax' => array('category' => 'comma')))),
//					)
//				),
//				// null rule, needed to fill the empty optional clause
//				array(
//					'condition' => array(),
//					'rule' => array()
//				)
			),

			'NP' => array(
//				array(
//					'condition' => array('head' => array('syntax' => array('category' => null, 'preposition' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'preposition' => '?syntax', 'determiner' => '?det')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'determiner' => '?det')))),
//						array('cat' => 'PP', 'features' => array('head' => array('syntax' => '?syntax')))
//					)
//				),
//				array(
//					'condition' => array('head' =>array('syntax' =>  array('category' => null, 'determiner' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat', 'determiner' => array('category' => '?det'))))),
//						array('cat' => 'determiner', 'features' => array('head' => array('syntax' => array('category' => '?det')))),
//						array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
//					)
//				),
//				array(
//					'condition' => array('head' => array('syntax' => array('category' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
//						array('cat' => 'noun', 'features' => array('head' => array('syntax' => array('category' => '?cat')))),
//					)
//				),
//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//					)
//				),

//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
//					)
//				),
			),
			'PN' => array(

//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//					)
//				),
//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null, 'middlename' => null, 'lastname' => null))),
//					'rule' => array(
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'middlename' => '?middlename', 'lastname' => '?lastname')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?middlename')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
//					)
//				),

//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null, 'lastname' => null))),
//					'rule' => array(
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name', 'lastname' => '?lastname')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?lastname')))),
//					)
//				),

//				array(
//					'condition' => array('head' => array('syntax' => array('name' => null))),
//					'rule' => array(
//						array('cat' => 'PN', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//						array('cat' => 'propernoun', 'features' => array('head' => array('syntax' => array('name' => '?name')))),
//					)
//				),

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
//				array(
//					'condition' => array('head' => array('syntax' => array('predicate' => null, 'category' => null))),
//					'rule' => array(
//						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
//						array('cat' => 'verb', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
//						array('cat' => 'NP', 'features' => array())
//					)
//				),
//				array(
//					'condition' => array('head' => array('syntax' => array('predicate' => null))),
//					'rule' => array(
//						array('cat' => 'VP', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
//						array('cat' => 'verb', 'features' => array('head' => array('syntax' => array('tense' => '?tense', 'predicate' => '?pred')))),
//					)
//				),
			),
//			'PP' => array(
//				array(
//					'condition' => array(),
//					'rule' => array(
//						array('cat' => 'PP', 'features' => array('head' => array('syntax' => array('category' => '?category', 'object' => '?obj')))),
//						array('cat' => 'preposition', 'features' => array('head' => array('syntax' => array('category' => '?category')))),
//						array('cat' => 'NP', 'features' => array('head' => array('syntax' => '?obj'))),
//					)
//				),
//			),

		);
	}
}