<?php

namespace agentecho\component;

use agentecho\component\events\EventSender;
use agentecho\component\events\LogEvent;
use agentecho\datastructure\Atom;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\SentenceInformation;
use agentecho\exception\FormulatorException;
use agentecho\exception\NoSemanticsAtTopLevelException;
use agentecho\exception\ParseException;
use agentecho\grammar\Grammar;
use agentecho\datastructure\Relation;
use agentecho\datastructure\Property;
use agentecho\datastructure\Variable;
use agentecho\exception\EchoException;

/**
 * This class answers question and processes imperatives.
 *
 * @author Patrick van Bergen
 */
class SentenceProcessor
{
	use EventSender;

	/**
	 * Returns a response to $question, using $Parser to parse the $question,
	 * and using $ConversationContext to process pronouns.
	 *
	 * @param string $question
	 * @param Conversation $Conversation
	 * @param KnowledgeManager $KnowledgeManager
	 * @return string
	 */
	public function reply($question, Conversation $Conversation, KnowledgeManager $KnowledgeManager = null)
	{
		// notify the question
		$this->send(new LogEvent(array('question' => $question)));

		try {

			// parse the sentence
			$SentenceInformation = $this->parseFirstLine($question, $Conversation);

			// update the current grammar from the language found in this sentence
			$CurrentGrammar = $Conversation->getCurrentGrammar();

			// notify the syntax
			$this->send(new LogEvent(array('syntax' => $SentenceInformation->getPhraseSpecification())));

			// get semantics
			$Question = $SentenceInformation->getSemantics();

			// replace references
			$PronounProcessor = new PronounProcessor();
			$PronounProcessor->replaceReferences($Question);

			// notify the semantics
			$this->send(new LogEvent(array('semantics' => $Question)));

			// replace all request properties with variables
			foreach ($Question->getRelations() as $Relation) {
				$this->changeRequestPropertyIntoVariable($Relation);
			}

			try {

				// transform human-based metaphores into machine-processable tasks
				$InterpretedQuestion = $this->interpret($Question, $KnowledgeManager);

				// ask the knowledge manager to find the answer (in a set of variable bindings)
				$bindings = $this->findAnswer($InterpretedQuestion, $KnowledgeManager);

				// turn the answer bindings into relations
				$Formulator = new Formulator(__DIR__ . '/../resources/basic.formulations');
				$Answer = $Formulator->formulate($Question, $bindings);

				$SentenceRelation = $Question->getRelationByPredicate('sentence');
				if (!$SentenceRelation) {
					return false;
				}

				/** @var Variable $SentenceEvent */
				$SentenceEvent = $SentenceRelation->getArgument(0);
				$this->makeDeclarative($Answer, $SentenceEvent);

			} catch (FormulatorException $E) {

				$Answerer = new Answerer1();
				$Answer = $Answerer->answer($Question, $CurrentGrammar, $KnowledgeManager);

			}

			// substitute proper nouns by pronouns
#todo

			// generate the surface representation
			$Generator = new Generator();
			$answer = $Generator->generate($CurrentGrammar, $Answer);

		} catch (EchoException $E) {

			if ($E instanceof EchoException) {
				$translatedMessage = Translations::translate($E->getMessageText(), $Conversation->getCurrentGrammar()->getLanguageCode());
				$E->setMessageText($translatedMessage);
			}

			$this->send(new LogEvent(array('backtrace' => $this->getFullTrace($E))));

			$answer = $E->getMessage();
		}

		return $answer;
	}

	private function makeDeclarative(RelationList $Relations, Variable $SentenceEvent)
	{
		$Mood = $Relations->getRelationByPredicate('mood');

		$Relations->removeRelation($Mood);
		$DeclarativeMood = new Relation();
		$DeclarativeMood->setPredicate('mood');
		$A0 = $SentenceEvent->createClone();
		$A1 = new Atom('Declarative');
		$DeclarativeMood->setArgument(0, $A0);
		$DeclarativeMood->setArgument(1, $A1);
		$Relations->addRelation($DeclarativeMood);
	}

	/**
	 * Tries to parse $input in the $Conversation's current grammar,
	 * or any of its other grammars.
	 *
	 * @param $input
	 * @param Conversation $Conversation
	 * @return array
	 * @throws \Exception
	 * @throws null
	 */
	public function parse($input, Conversation $Conversation)
	{
		if (trim($input) == '') {
			return [];
		}

		$sentences = array();

		// create an array of grammars in which the current one is in the front
		$grammars = array($Conversation->getCurrentGrammar());

		foreach ($Conversation->getGrammars() as $Grammar) {
			if ($Grammar != $Conversation->getCurrentGrammar()) {
				$grammars[] = $Grammar;
			}
		}

		// keep track of the _first_ exception that is cast
		$Exception = null;

		// try to parse the sentence in each of the available grammars
		foreach ($grammars as $Grammar) {

			try {

				// parse a single line with a single grammar
				$Sentence = $this->parseSentence($input, $Grammar);

				$sentences[] = $Sentence;

				// update current language
				$Conversation->setCurrentGrammar($Grammar);

				// now parse the rest of the input, if there is one
				$restInput = str_replace($Sentence->getSurfaceText(), '', $input);

				return array_merge($sentences, $this->parse($restInput, $Conversation));

			} catch (\Exception $E) {

				// save the first exception
				if (!$Exception) {
					$Exception = $E;
				}

			}
		}

		// all grammars failed; throw the first exception
		throw $Exception;
	}

	/**
	 * Parses $input into a series of Sentences, but returns only the first of these,
	 *
	 * @param string $input
	 * @param Conversation $Conversation
	 * @return SentenceInformation
	 */
	public function parseFirstLine($input, Conversation $Conversation)
	{
		$sentences = $this->parse($input, $Conversation);
		return $sentences ? $sentences[0] : false;
	}

	/**
	 * This function turns a line of text into a SentenceInformation
	 *
	 * @param $input
	 * @param Grammar $Grammar
	 * @return \agentecho\datastructure\SentenceInformation
	 * @throws \agentecho\exception\ParseException
	 * @throws \agentecho\exception\NoSemanticsAtTopLevelException
	 */
	public function parseSentence($input, Grammar $Grammar)
	{
		$Sentence = new SentenceInformation();

		$Sentence->setLanguage($Grammar->getLanguage());

		// analyze words
		$Lexer = new Lexer();
		$Lexer->analyze($input, $Sentence, $Grammar);

		// create a phrase specification from these lexical items
		$result = EarleyParser::getFirstTree($Grammar, $Sentence->getLexicalItems());
		$Sentence->setPhraseSpecification($result['tree']);
		$Sentence->setSemantics($result['tree']['semantics']);

		if (!$result['success']) {
			throw new ParseException(implode(' ', array_slice($Sentence->getLexicalItems(), $result['lastParsedIndex'] - 1, 4)));
		}

		if ($result['tree']['semantics'] === null) {
			throw new NoSemanticsAtTopLevelException();
		}

		return $Sentence;
	}

	private function interpret(RelationList $RawSemantics, KnowledgeManager $KnowledgeManager)
	{
		$ExpandedQuestion = $RawSemantics;

		if ($KnowledgeManager) {

			// first explode the relations into all possible solution paths
			// this is an array of relationlists (or relation-arrays)
			$interpreters = $KnowledgeManager->getInterpreters();

			foreach($interpreters as $Interpreter) {

				$ExpandedQuestion = $Interpreter->mapRelations($RawSemantics, true, true);
			}
		}

		$this->send(new LogEvent(array('interpretation' => $ExpandedQuestion)));

		return $ExpandedQuestion;
	}

	private function findAnswer(RelationList $ExpandedQuestion, KnowledgeManager $KnowledgeManager)
	{
		$bindings = array();

		if ($KnowledgeManager) {

			$knowledgeSources = $KnowledgeManager->getKnowledgeSources();
			$Exception = null;

			foreach ($knowledgeSources as $KnowledgeSource) {

				if (isset($this->EventManager)) {
					$KnowledgeSource->setEventManager($this->EventManager);
				}

				try {
					// execute the query
					$newBindings = $KnowledgeSource->answer($ExpandedQuestion);

					if ($newBindings) {

						// perform the translations
						$newBindings = $this->performTranslations($newBindings, $ExpandedQuestion);

						$bindings = array_merge($bindings, $newBindings);
					}

				} catch (\Exception $E) {
					$Exception = $E;
				}

			}

			if (empty($bindings) && !is_null($Exception)) {
				throw $Exception;
			}

		}

		$this->send(new LogEvent(array('bindings' => $bindings)));

		return $bindings;
	}

	/**
	 * Invokes all `let`- and `aggregate`- relations in $Relations on $bindings
	 *
	 * @param $bindings
	 * @param \agentecho\datastructure\RelationList $Relations
	 *
	 * @return array A new list of bindings.
	 */
	private function performTranslations($bindings, RelationList $Relations)
	{
		$Assigner = new Assigner();
		$Aggregator = new Aggregator();

		foreach ($bindings as &$binding) {
			foreach ($Relations->getRelations() as $Relation) {
				if ($Relation->getPredicate() == 'let') {
					$binding = $Assigner->applyLet($Relation, $binding);
				}
			}
		}

		foreach ($Relations->getRelations() as $Relation) {
			if ($Relation->getPredicate() == 'aggregate') {

				$bindings = array($Aggregator->applyAggregate($Relation, $bindings));
			}
		}

		return $bindings;
	}

	private function changeRequestPropertyIntoVariable(Relation $Relation)
	{
		foreach ($Relation->getArguments() as $index => $Argument) {
			if ($Argument instanceof Property) {
				$propertyName = $Argument->getName();
				$objectName = $Argument->getObject()->getName();
				$Variable = new Variable($objectName . '_' . $propertyName);
				$Relation->setArgument($index, $Variable);
			}
		}
	}

	private function getFullTrace(\Exception $E)
	{
		$topLevel = array(
			'file' => $E->getFile(),
			'line' => $E->getLine(),
			'class' => get_class($E),
			'function' => ''
		);

		return array_merge(array($topLevel), $E->getTrace());
	}
}
