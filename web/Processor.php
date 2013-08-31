<?php

use agentecho\AgentEcho;
use agentecho\component\DataMapper;
use agentecho\grammar\EnglishGrammar;
use agentecho\knowledge\DBPedia;

require_once __DIR__ . '/component/lineeditor/LineEditor.php';
require_once __DIR__ . '/../component/Autoload.php';

/**
 * @author Patrick van Bergen
 */
class Processor
{
	function run()
	{
		if (isset($_REQUEST['action'])) {

			// Ajax calls

			$action = $_REQUEST['action'];
			$this->$action($_REQUEST);

		} else {

			$this->showMainPage();

		}
	}

	private function showMainPage()
	{
		$template = file_get_contents(__DIR__ . '/template.html');

		$LineEditor = new LineEditor();
		$LineEditor->setName('q');

		if (isset($_REQUEST['q'])) {
			$LineEditor->setLinePieces(explode(',', $_REQUEST['q']));
		} else {
			$LineEditor->setLinePieces(array('where', 'was', 'Lord Byron'));
		}

		$submitButton = "<button type='submit'>Ask</button>";

		$javascriptFiles = array();
		foreach ($LineEditor->getJavascriptFiles() as $fileName) {
			$javascriptFiles[] = $fileName;
		}

		$cssFiles = array();
		$cssFiles[] = $LineEditor->getStylesheet();

		$javascriptHtml = '';
		foreach ($javascriptFiles as $javascriptFile) {
			$javascriptHtml .= "<script src='$javascriptFile'></script>";
		}

		$cssHtml = '';
		foreach ($cssFiles as $cssFile) {
			$cssHtml .= "<link rel='stylesheet' type='text/css' media='screen' href='$cssFile' />";
		}

		$body = "<form>" . (string)$LineEditor . $submitButton . "</form>";

		if (isset($_REQUEST['q'])) {
			$body .= $this->getReponseHtml(implode(' ', explode(',', $_REQUEST['q'])));
		}

		$tokens = array(
			'css' => $cssHtml,
			'body' => $body,
			'javascript' => $javascriptHtml,
		);

		$html = $this->createHtml($template, $tokens);

		echo $html;
	}

	private function createHtml($template, $tokens)
	{
		$html = $template;

		foreach ($tokens as $token => $contents) {
			$html = str_replace('##' . $token . '##', $contents, $html);
		}

		return $html;
	}

	public function suggest($params)
	{
		$language = 'en';

		$sentences = array(
			'nl' => array(
				'waar werd X geboren',
				'wanneer werd X geboren',
			),
			'en' => array(
				'where was X born',
				'when was X born',
				'where did X die',
				'when did X die',
			)

		);

		if ($params['value'] == '') {
			$inputWords = array();
		} else {
			$inputWords = explode(',', $params['value']);
		}

		$wordIndex = count($inputWords) > 0 ? count($inputWords) - 1 : 0;
		$sentenceArrays = $this->getSentenceWords($sentences[$language]);
		$allowedSentences = $this->getAllowedSentences($sentenceArrays, $inputWords);

		$suggests = array();
		foreach ($allowedSentences as $allowedSentence) {

			if (isset($allowedSentence[$wordIndex])) {

				$allowedWord = $allowedSentence[$wordIndex];

				if ($allowedWord == 'X') {

					$actualWord = $inputWords[$wordIndex];

					$suggests = array_merge($suggests, $this->getNamesLike($actualWord));

				} else {
					$suggests[] = $allowedWord;
				}
			}
		}

		header('Content-type: application/json');

		$suggests = array_values(array_unique($suggests));

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
		$names = file_get_contents('/home/patrick/Desktop/names.csv');
		preg_match_all('/^([^\n]*' . $word . '[^\n]*)$/mi', $names, $results);
		$names = $results[1];
		return array_splice($names, 0, 20);
	}

	function getNamesLike_old($word)
	{
		require_once('/data/agentecho/component/Autoload.php');

		$DBPedia = new \agentecho\knowledge\DBPedia(null);
		$Query = new \agentecho\datastructure\SparqlQuery();
		$Query->select("?name");
		$Query->where("{ { ?person rdfs:label ?name } UNION { ?person dbpprop:birthName ?name } }");
		$Query->where("FILTER(lang(?name) = 'en')");
		$Query->where("FILTER regex(?name, '". $word . "', 'i')");

		$results = $DBPedia->processQuery($Query);
		$names = array();
		foreach ($results as $result) {
			$names[] = substr(@iconv('UTF-8', 'cp1252//IGNORE', $result['name']), 0, 40);
		}
		sort($names);

		return $names;
	}

	private function getReponseHtml($sentence)
	{
		$Agent = new AgentEcho();
		$Agent->addGrammar(new EnglishGrammar());
		$Agent->addKnowledgeSource(new DBPedia(__DIR__ . '/../resources/dbpedia.map'));
		$Agent->addElaborator(new DataMapper(__DIR__ . '/../resources/ruleBase1.map'));
		$Conversation = $Agent->startConversation();

		$response = $Conversation->answer($sentence);

		return $response;
	}
}
