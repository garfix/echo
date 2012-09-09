<?php

namespace agentecho\test;

use \agentecho\test\TestBase;
use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\exception\ParseException;
use \agentecho\component\Parser;

/**
 * This is suite of tests that tests the intermediate representation of parsed sentences.
 * It tests the SentenceContext.
 */
class ParserTest extends TestBase
{
	function execute()
	{
		$Dutch = new DutchGrammar();
		$English = new EnglishGrammar();

		$Parser = new Parser();
		$Parser->setGrammars(array($Dutch, $English));
		$Parser->setCurrentGrammar($English);

		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Parser->parse('Book that flight. Boek die vlucht');
		$this->test(101, $sentences[0]->getLanguage(), 'english');
		$this->test(102, $sentences[0]->getSyntaxString(), '[S [VP [verb book][NP [DP [determiner that]][NBar [noun flight]]]]]');
		$this->test(103, $sentences[0]->getObjectString(), 'Sentence {sentenceType: imperative, Relation: Relation {predicate: book, Arg2: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}');
		$this->test(106, $sentences[1]->getLanguage(), 'dutch');
		$this->test(107, $sentences[1]->getSyntaxString(), '[S [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]');
		$this->test(103, $sentences[1]->getObjectString(), 'Sentence {sentenceType: imperative, Relation: Relation {predicate: book, Arg2: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}');

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Parser->parseFirstLine('Who am I?');
		$this->test(111, $Sentence->getSyntaxString(), '[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]');
#		$this->test(112, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: be, Arg1: Entity {category: firstPerson, number: singular}, Arg2: Entity {number: singular, question: 1}, tense: present}, voice: active}');
		$this->test(112, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: be, Arg1: Entity {category: firstPerson, number: singular}, Preposition: Preposition {category: identity, Object: Entity {number: singular, question: 1}}, tense: present}, voice: active}');

		$Sentence = $Parser->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->test(203, $Sentence->getSyntaxString(), '[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]]');
		$this->test(204, $Sentence->getObjectString(), 'Sentence {sentenceType: yes-no-question, Relation: Relation {predicate: influence, Arg1: Entity {category: author, Determiner: Determiner {category: the, question: }, number: singular, Preposition: Preposition {category: of, Object: Entity {name: Paradise Lost, number: singular}}}, Arg2: Entity {name: Lord Byron, number: singular}, tense: past}, voice: passive}');

		$Sentence = $Parser->parseFirstLine('How many children did Lord Byron have?');
		$this->test(213, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: have, Arg1: Entity {name: Lord Byron, number: singular}, Arg2: Entity {category: child, Determiner: Determiner {category: many, question: 1}, number: singular}, tense: past}, voice: active}');

		// S => NP VP
		$Sentence = $Parser->parseFirstLine('John sees the book');
		$this->test(251, $Sentence->getSyntaxString(), '[S [NP [propernoun John]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]');
		$this->test(252, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: see, Arg1: Entity {name: John, number: singular}, Arg2: Entity {category: book, Determiner: Determiner {category: the, question: }, number: singular}, tense: present}, voice: active}');

		// agreement success
		// S => NP VP
		$Sentence = $Parser->parseFirstLine('I am Patrick');
		$this->test(261, $Sentence->getSyntaxString(), '[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]');
		$this->test(262, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: be, Arg1: Entity {category: firstPerson, number: singular}, Arg2: Entity {name: Patrick, number: singular}, tense: present}, voice: active}');

		// agreement fail
		$caught = false;
		try {
			$Parser->parseFirstLine('I are Patrick');
		} catch (ParseException $E) {
			$caught = true;
		}
		$this->test(264, $caught, true);

		// S => NP VP NP NP
		$Sentence = $Parser->parseFirstLine("John gives Mary flowers.");
		$this->test(281, $Sentence->getObjectString(), 'Sentence {sentenceType: declarative, Relation: Relation {predicate: give, Arg1: Entity {name: John, number: singular}, Arg2: Entity {category: flower, number: singular}, Arg3: Entity {name: Mary, number: singular}, tense: present}, voice: active}');

		// secondary sentence
		$Sentence = $Parser->parseFirstLine("How old was Mary Shelley when she died?");
		$this->test(291, $Sentence->getObjectString(), 'Sentence {sentenceType: wh-question, Relation: Relation {predicate: be, Arg1: Entity {name: Mary Shelley, number: singular}, Arg2: Entity {category: old, Determiner: Determiner {question: 1}, number: singular}, tense: past}, voice: active, RelativeClause: RelativeClause {complementizer: when, Clause: Relation {predicate: die, Arg1: Entity {category: she, number: singular}, tense: past}}}');

	}
}