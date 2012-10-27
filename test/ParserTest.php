<?php

namespace agentecho\test;

require_once __DIR__ . '/../component/Autoload.php';

use \agentecho\grammar\EnglishGrammar;
use \agentecho\grammar\DutchGrammar;
use \agentecho\exception\ParseException;
use \agentecho\component\Parser;

/**
 * This is suite of tests that tests the intermediate representation of parsed sentences.
 * It tests the SentenceContext.
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	function test()
	{
		$Dutch = new DutchGrammar();
		$English = new EnglishGrammar();

		$Parser = new Parser();
		$Parser->setGrammars(array($Dutch, $English));
		$Parser->setCurrentGrammar($English);

		// S => VP ; parse sentences in two languages in the same line
		$sentences = $Parser->parse('Book that flight. Boek die vlucht');
		$this->assertSame('english', $sentences[0]->getLanguage());
		$this->assertSame('[S [VP [verb book][NP [DP [determiner that]][NBar [noun flight]]]]]', $sentences[0]->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: imperative, Clause: Clause {predicate: book, DeepDirectObject: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}', $sentences[0]->getObjectString());
		$this->assertSame('dutch', $sentences[1]->getLanguage());
		$this->assertSame('[S [VP [verb boek][NP [DP [determiner die]][NBar [noun vlucht]]]]]', $sentences[1]->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: imperative, Clause: Clause {predicate: book, DeepDirectObject: Entity {category: flight, Determiner: Determiner {category: that, question: }, number: singular}, tense: present}, voice: active}', $sentences[1]->getObjectString());

		// S => WhNP VP ; referring expression "I" ; agreement feature
		$Sentence = $Parser->parseFirstLine('Who am I?');
		$this->assertSame('[S [WhNP [whword who]][VP [verb am]][NP [pronoun i]]]', $Sentence->getSyntaxString());
#		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: be, DeepSubject1: Entity {category: firstPerson, number: singular}, DeepDirectObject: Entity {number: singular, question: 1}, tense: present}, voice: active}', $Sentence->getObjectString());
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: be, DeepSubject: Entity {category: firstPerson, number: singular}, Preposition: Preposition {category: identity, Object: Entity {number: singular, question: 1}}, tense: present}, voice: active}', $Sentence->getObjectString());

		$Sentence = $Parser->parseFirstLine('Was Lord Byron influenced by the author of Paradise Lost?');
		$this->assertSame('[S [aux was][NP [propernoun Lord Byron]][VP [verb influenced]][passivisationPreposition by][NP [DP [determiner the]][NBar [NBar [noun author]][PP [preposition of][NP [propernoun Paradise Lost]]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: yes-no-question, Clause: Clause {predicate: influence, DeepSubject: Entity {category: author, Determiner: Determiner {category: the, question: }, number: singular, Preposition: Preposition {category: of, Object: Entity {name: Paradise Lost, number: singular}}}, DeepDirectObject: Entity {name: Lord Byron, number: singular}, tense: past}, voice: passive}', $Sentence->getObjectString());

		$Sentence = $Parser->parseFirstLine('How many children did Lord Byron have?');
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: have, DeepSubject: Entity {name: Lord Byron, number: singular}, DeepDirectObject: Entity {category: child, Determiner: Determiner {category: many, question: 1}, number: singular}, tense: past}, voice: active}', $Sentence->getObjectString());

		// S => NP VP
		$Sentence = $Parser->parseFirstLine('John sees the book');
		$this->assertSame('[S [NP [propernoun John]][VP [verb sees][NP [DP [determiner the]][NBar [noun book]]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: see, DeepSubject: Entity {name: John, number: singular}, DeepDirectObject: Entity {category: book, Determiner: Determiner {category: the, question: }, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		// agreement success
		// S => NP VP
		$Sentence = $Parser->parseFirstLine('I am Patrick');
		$this->assertSame('[S [NP [pronoun i]][VP [verb am][NP [propernoun Patrick]]]]', $Sentence->getSyntaxString());
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: be, DeepSubject: Entity {category: firstPerson, number: singular}, DeepDirectObject: Entity {name: Patrick, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		// agreement fail
		$caught = false;
		try {
			$Parser->parseFirstLine('I are Patrick');
		} catch (ParseException $E) {
			$caught = true;
		}
		$this->assertSame(true, $caught);

		// S => NP VP NP NP
		$Sentence = $Parser->parseFirstLine("John gives Mary flowers.");
		$this->assertSame('Sentence {sentenceType: declarative, Clause: Clause {predicate: give, DeepSubject: Entity {name: John, number: singular}, DeepDirectObject: Entity {category: flower, number: singular}, DeepIndirectObject: Entity {name: Mary, number: singular}, tense: present}, voice: active}', $Sentence->getObjectString());

		// secondary sentence
		$Sentence = $Parser->parseFirstLine("How old was Mary Shelley when she died?");
		$this->assertSame('Sentence {sentenceType: wh-question, Clause: Clause {predicate: be, DeepSubject: Entity {name: Mary Shelley, number: singular}, DeepDirectObject: Entity {category: old, Determiner: Determiner {question: 1}, number: singular}, tense: past}, voice: active, RelativeClause: RelativeClause {complementizer: when, Clause: Clause {predicate: die, DeepSubject: Entity {category: subject, number: singular}, tense: past}}}', $Sentence->getObjectString());

	}
}