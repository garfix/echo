<?php

namespace agentecho\web;

use agentecho\AgentEcho;
use agentecho\component\AgentConfig;
use agentecho\component\DataMapper;
use agentecho\component\events\LogEvent;
use agentecho\component\GrammarFactory;
use agentecho\knowledge\DBPedia;
use agentecho\web\component\Div;
use agentecho\web\component\Form;
use agentecho\web\component\Image;
use agentecho\web\component\Input;
use agentecho\web\component\Raw;
use agentecho\web\component\ResetButton;
use agentecho\web\component\SideTabs;
use agentecho\web\component\SubmitButton;
use agentecho\web\component\LineEditor;
use agentecho\web\view\BackTraceView;
use agentecho\web\view\BindingsView;
use agentecho\web\view\RelationListView;
use agentecho\web\view\SparqlQueryView;
use agentecho\web\view\SyntaxView;

require_once __DIR__ . '/../Autoload.php';

/**
 * @author Patrick van Bergen
 */
class Processor
{
	private $language = 'en';

	function run(array $parameters)
	{
		mb_internal_encoding ('UTF-8');

		// language
		if (isset($parameters['language'])) {
			$this->language = $parameters['language'];
		}

		if (isset($parameters['action'])) {

			// Ajax calls

			$action = $parameters['action'];
			$this->$action($parameters);

		} else {

			$this->showMainPage($parameters);

		}
	}

	private function showMainPage($parameters)
	{
		$template = file_get_contents(__DIR__ . '/template.html');

		$Container = new Div();
		$Container->addClass('container');

			$Container->add($Bird = new Image());
			$Bird->addClass('bird');
			// http://www.clipartsalbum.com/?l=en-us&m=start&c=birds&s=lovebirds&p=1&t=20&q=&e=1&i=180727&r=6
			$Bird->setSource('img/lovebird_balloon.jpg');

			$Container->add($BirdEyes = new Image());
			$BirdEyes->setId('birdEyes');
			$BirdEyes->addClass('bird');
			$BirdEyes->setSource('img/lovebird_eyes.png');
			$BirdEyes->addStyle('display', 'none');

			$Container->add($Text = new Div());
			$Text->addClass('text');
			$Text->addText($this->translate('Ask me something...'));

			$Container->add($Interaction = new Div());
			$Interaction->addClass('interaction');

				$Interaction->add($Question = new Div());
				$Question->addClass('question');
				$Question->addText($this->translate('Question'));

				$Interaction->add($Form = $this->getForm($parameters));

		if (isset($parameters['q'])) {

			$data = explode(LineEditor::SEPARATOR, $parameters['q']);
			$lexemes = array();

			foreach ($data as $value) {
				if (preg_match('/[ ,\-()]/', $value, $matches)) {
					$lexemes[] = '"' . $value . '"';
				} else {
					$lexemes[] = $value;
				}
			}

			$sentence = implode(' ', $lexemes);

			$response = $this->getResponse($sentence);

			$Interaction->add($Answer = new Div());
			$Answer->addClass('answer');
			$Answer->addText($this->translate('Answer'));

			$Interaction->add($AnswerText = new Div());
			$AnswerText->addText($response['answer']);
			$AnswerText->addClass('answerText');

			$Interaction->add($this->getSideTabs($response));
		}

		$tokens = array(
			'css' => $Container->getStyleElements(),
			'body' => (string)$Container,
			'javascript' => $Container->getJavascriptElements(),
		);

		$html = $this->createHtml($template, $tokens);

		echo $html;
	}

	private function getSideTabs(array $response)
	{
		$SideTabs = new SideTabs();
		$SideTabs->setTabWidth(250);

		foreach ($response as $key => $value) {
			$SideTabs->addTab($key, $this->markUp($key, $value));
		}

		return $SideTabs;
	}

	private function markUp($key, $value)
	{
		if ($key == 'syntax') {
			$View = new SyntaxView();
			$html = $View->getHtml($value);
		} elseif ($key == 'semantics') {
			$View = new RelationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'interpretation') {
			$View = new RelationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'relations') {
			$View = new RelationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'query') {
			$View = new SparqlQueryView();
			$html = $View->getHtml($value);
		} elseif ($key == 'bindings') {
			$View = new BindingsView();
			$html = $View->getHtml($value);
		} elseif ($key == 'formulation') {
			$View = new RelationListView();
			$html = $View->getHtml($value);
		} elseif ($key == 'backtrace') {
			$View = new BackTraceView();
			$html = $View->getHtml($value);
		} else {
			$html = htmlspecialchars($value);
		}

		return new Raw($html);
	}

	private function getForm(array $parameters)
	{
		$Form = new Form();
		$Form->addClass('form');
		$Form->setMethodGet();

			$Form->add($ResetButton = new ResetButton());
			$ResetButton->setTitle($this->translate('Clear'));

			$Form->add($Panel = new Div());
			$Panel->addClass('editPanel');

				$Panel->add($LineEditor = new LineEditor());
				$LineEditor->setName('q');
				$LineEditor->setId('lineEditor');
				$LineEditor->setLanguage($this->language);

				if (isset($parameters['q'])) {
					$LineEditor->setLinePieces(explode(LineEditor::SEPARATOR, $parameters['q']));
				} else {
					//$LineEditor->setLinePieces(array('where', 'was', 'Lord Byron', 'born'));
					$LineEditor->setLinePieces(array('how', 'old', 'was', 'Mary Shelley', 'when', 'she', 'died'));
				}

			$Form->add($SubmitButton = new SubmitButton());
			$SubmitButton->setTitle($this->translate('Ask'));

			$Form->add($Language = new Input());
			$Language->setType('hidden');
			$Language->setName('language');
			$Language->setValue($this->language);

			$Form->onReset('$("lineEditor").ed.reset()');

		return $Form;
	}

	private function createHtml($template, $tokens)
	{
		$html = $template;

		foreach ($tokens as $token => $contents) {
			$html = str_replace('##' . $token . '##', $contents, $html);
		}

		return $html;
	}

	public function translate($text)
	{
		$texts = array(
			'nl' => array(
				'Ask' => 'Vraag',
				'Question' => 'Vraag',
				'Answer' => 'Antwoord',
				'Clear' => 'Nieuw',
				'Response' => 'Antwoord',
				'Semantics' => 'Semantiek',
				'Ask me something...' => 'Vraag maar wat...',
				'{Name famous person}' => '{Naam beroemd persoon}',
			)
		);

		$language = $this->language;

		if ($language == 'en') {
			return $text;
		} elseif (isset($texts[$language][$text])) {
			return $texts[$language][$text];
		} else {
			trigger_error('Text not translated:' . $text, E_USER_WARNING);
			return $text;
		}
	}

	public function suggest($params)
	{
		$language = $this->language;

		$sentences = array(
			'nl' => array(
				'waar is X geboren',
				'wanneer is X geboren',
				'waar is X gestorven',
				'wanneer is X gestorven',
				'wie waren X \'s ouders',
				'wie waren X \'s kinderen',
			),
			'en' => array(
				'where was X born',
				'when was X born',
				'where did X die',
				'when did X die',
				'who were X \'s parents',
				'who were X \'s children',
			)
		);

		if ($params['value'] == '') {
			$inputWords = array();
		} else {
			$inputWords = explode(LineEditor::SEPARATOR, $params['value']);
		}

		$wordIndex = count($inputWords) > 0 ? count($inputWords) - 1 : 0;
		$sentenceArrays = $this->getSentenceWords($sentences[$language]);
		$allowedSentences = $this->getAllowedSentences($sentenceArrays, $inputWords);
		$actualWord = empty($inputWords) ? '' : $inputWords[$wordIndex];

		$suggests = array();
		foreach ($allowedSentences as $allowedSentence) {

			if (isset($allowedSentence[$wordIndex])) {

				$allowedWord = $allowedSentence[$wordIndex];

				if ($allowedWord == 'X') {

					$names = $this->getNamesLike($actualWord);
					if (empty($names)) {
						$names = array('' => "<span class='wordTypeSuggest'>" . $this->translate('{Name famous person}') . '</span>');
					}

					$suggests += $names;

				} else {
					$html = preg_replace('/\b(' . $actualWord .  ')/iu', '<b>\1</b>', $allowedWord);
					$suggests[$allowedWord] = $html;
				}
			}
		}

		header('Content-type: application/json');

		$response = array(
			'suggests' => $suggests
		);

		echo json_encode($response);
	}

	private function getAllowedSentences($sentenceArrays, $inputWords)
	{
		$allowedSentences = array();

		foreach ($sentenceArrays as $sentenceArray) {

			$success = true;

			for ($i = 0; $i < count($inputWords); $i++) {
				$inputWord = $inputWords[$i];

				if (isset($sentenceArray[$i])) {

					$word = $sentenceArray[$i];

					if ($word == 'X') {

					} elseif ($inputWord == '') {

					} elseif (strpos($word, $inputWord) !== 0) {

						$success = false;
						break;
					}
				}
			}

			if ($success) {
				$allowedSentences[] = $sentenceArray;
			}

		}

		return $allowedSentences;
	}

	private function getSentenceWords($sentences)
	{
		$words = array();

		foreach ($sentences as $sentence) {
			$words[] = explode(' ' , $sentence);
		}

		return $words;
	}

	function getNamesLike($word)
	{
		$trimmed = trim($word);
		if (empty($trimmed)) {
			return array();
		}

		$escapedWord = preg_replace('/([().])/', '\\\\$1', $word);

		$names = file_get_contents('/home/patrick/Desktop/names.csv');
		preg_match_all('/^([^\n]*\b' . $escapedWord . '[^\n]*)$/miu', $names, $results);
		$names = $results[1];
		$names = array_splice($names, 0, 20);

		$i = 0;
		$nameHtml = array();
		while ($i < count($names) && $i < 20) {

			$name = $names[$i];

			$html = preg_replace('/\b(' . $escapedWord .  ')/iu', '<b>\1</b>', $name);

			$nameHtml[$name] = $html;
			$i++;
		}

		return $nameHtml;
	}

	private function getResponse($sentence)
	{
		$Config = new AgentConfig();

		$Config->addListener(function($Event) use (&$logs) {
			if ($Event instanceof LogEvent) {
				$logs = array_merge($logs, $Event->getParams());
			}
		});

		switch ($this->language) {
			case 'en':
				$Config->addGrammar(GrammarFactory::getGrammar('en'));
				break;
			case 'nl':
				$Config->addGrammar(GrammarFactory::getGrammar('nl'));
				break;
		}

		$Config->addKnowledgeSource(new DBPedia());
		$Config->addInterpreter(new DataMapper(__DIR__ . '/../resources/basic.interpretations'));

		$Agent = new AgentEcho($Config);

		$logs = array();

		$answer = $Agent->answer($sentence);

		$logs['answer'] = $answer;

		return $logs;
	}
}
