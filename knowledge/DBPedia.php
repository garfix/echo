<?php

namespace agentecho\knowledge;

use \agentecho\Settings;
use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Sentence;
use \agentecho\phrasestructure\Determiner;
use \agentecho\phrasestructure\Entity;

/**
 * An adapter for DBPedia.
 */
class DBPedia extends KnowledgeSource
{
	static $cacheResults = true;
	static $id = 0;

	public function isProperNoun($identifier)
	{
		$triples = array(
			array('?object', 'rdfs:label', "'$identifier'@en")
		);
		$select = 'COUNT(?object)';
		$result = $this->query($triples, $select);
		return $result;
	}

	public function check($phraseSpecification, $sentenceType)
	{
		if (Settings::$debugKnowledge) r($phraseSpecification);

//		$objectId = self::getVariableId();

		$triples = array();
		$select = '';
		$this->interpret($phraseSpecification, $sentenceType, $triples, $select, null);

		if (Settings::$debugKnowledge) r($triples);

		$result = $this->query($triples, $select);

		if (Settings::$debugKnowledge) { r($result); echo "\n"; }

		return $result;
	}

	public function answerQuestion(Sentence $Sentence)
	{
		$triples = array();
		$select = '';
		$sentenceType = $Sentence->getType();
		$this->interpretPhrase($Sentence, $sentenceType, $triples, $select, null);

		$result = $this->query($triples, $select);

		if ($sentenceType == 'wh-question') {
			if (count($result == 1)) {
				if (is_array($result)) {
					$result = reset($result);
				}
				if (is_array($result)) {
					$result = reset($result);
				}
			}
		}
		if ($sentenceType == 'imperative') {
			$values = array();
			foreach ($result as $resultVal) {
				$values[] = reset($resultVal);
			}
			$result = $values;
		}

		return $result;
	}

	public function answerQuestionAboutObject($phraseSpecification, $sentenceType)
	{
		if (Settings::$debugKnowledge) r($phraseSpecification);

		$triples = array();
		$select = '';
		$this->interpret($phraseSpecification, $sentenceType, $triples, $select, null);

		if (Settings::$debugKnowledge) r($triples);

		$result = $this->query($triples, $select);

		if ($sentenceType == 'wh-question') {
			if (count($result == 1)) {
				if (is_array($result)) {
					$result = reset($result);
				}
				if (is_array($result)) {
					$result = reset($result);
				}
			}
		}
		if ($sentenceType == 'imperative') {
			$values = array();
			foreach ($result as $resultVal) {
				$values[] = reset($resultVal);
			}
			$result = $values;
		}

		if (Settings::$debugKnowledge) { r($result); echo "\n"; }

		return $result;
	}

	private function interpretPhrase(PhraseStructure $Phrase, $sentenceType, &$triples, &$select, $parentId)
	{
		$subjectId = $Phrase->getHashCode();

		// yes-no-question
		if ($sentenceType == 'yes-no-question') {
			$select = '1';
		}

		// imperative 'name'
		if ($Phrase instanceof Sentence) {

			/** @var Sentence $Sentence */
			$Sentence = $Phrase;

			if ($sentenceType == 'imperative') {

				/** @var Relation $Relation  */
				$Relation = $Sentence->getRelation();

                if ($Relation->getPredicate() == 'name') {

                    $select = '?name';
                    $arg2id = $Relation->getArgument2()->getHashCode();
                    $triples[] = array('?' . $arg2id, 'rdfs:label', '?name');
                    $triples[] = array('FILTER(lang(?name) = "en")');
                }
			}
		}

		// how many?
		if ($Phrase instanceof Determiner) {

            /** @var Determiner $Determiner */
       		$Determiner = $Phrase;

			if ($Determiner->isQuestion()) {
				if ($Determiner->getCategory() == 'many') {
#todo: probably needs some parent id
					$select = 'COUNT(?' . $subjectId . ')';
				}
			}
		}

#todo: needs rewrite: i am not going to make a class for Location, but for Modifier, or some such
//		if (isset($s['location']['question'])) {
//			$triples[] = array('?' . $s['location']['id'], 'rdfs:label', '?location');
//			$select = '?location';
//		}
#todo may we combine these, or is this similarity just an exception?
//		if (isset($s['time']['question'])) {
//			$select = '?' . $s['time']['id'];
//		}

		// http://dbpedia.org/ontology/birthPlace
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'bear') &&
//			isset($s['location']) &&
//			isset($s['arg2'])
//		) {
//			$themeId = $s['arg2']['id'];
//			$locationId = $s['location']['id'];
//			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/birthPlace>', '?' . $locationId);
//		}

		// http://dbpedia.org/ontology/deathPlace
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'die') &&
//			isset($s['location']) &&
//			isset($s['arg1'])
//		) {
//			$themeId = $s['arg1']['id'];
//			$locationId = $s['location']['id'];
//			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/deathPlace>', '?' . $locationId);
//		}

		// http://dbpedia.org/ontology/birthDate
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'bear') &&
//			isset($s['time']) &&
//			isset($s['arg2'])
//		) {
//			$themeId = $s['arg2']['id'];
//			$timeId = $s['time']['id'];
//			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/birthDate>', '?' . $timeId);
//		}

		// rdfs:label
        if ($Phrase instanceof Entity) {

            /** @var Entity $Entity */
       		$Entity = $Phrase;

            if ($name = $Entity->getName()) {
                $triples[] = array('?' . $subjectId, 'rdfs:label', "'" . ucwords($name) . "'@en");
            }

    		// http://dbpedia.org/property/children
            if ($Entity->getCategory() == 'child') {
                if ($Determiner = $Entity->getDeterminer()) {
                    if ($Object = $Determiner->getObject()) {
                        $objectId = $Object->getHashCode();
                        $triples[] = array('?' . $objectId, '<http://dbpedia.org/property/children>', '?' . $subjectId);
                    }
                }
            }


        }

//		// http://dbpedia.org/ontology/author
//		if (
//			isset($s['category']) && ($s['category'] == 'author') &&
//			isset($s['of'])
//		) {
//			$objectId = $s['of']['id'];
//			$triples[] = array('?' . $objectId, '<http://dbpedia.org/ontology/author>', '?' . $subjectId);
//		}





//		// http://dbpedia.org/ontology/influencedBy
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'influence') &&
//			isset($s['agent']) &&
//			isset($s['experiencer'])
//		) {
//			$actorId = $s['agent']['id'];
//			$patientId = $s['experiencer']['id'];
//			$triples[] = array('?' . $patientId, '<http://dbpedia.org/ontology/influencedBy>', '?' . $actorId);
//		}
//
//		// http://dbpedia.org/ontology/child (1)
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'have') &&
//			isset($s['arg1']) &&
//			isset($s['arg2']) &&
//			($s['arg2']['category'] == 'child')
//		) {
//			$possessor = $s['arg1']['id'];
//			$posession = $s['arg2']['id'];
//			$triples[] = array('?' . $possessor, '<http://dbpedia.org/ontology/child>', '?' . $posession);
//		}
//
//		// http://dbpedia.org/ontology/child (2)
//		if (
//			isset($s['predicate']) && ($s['predicate'] == 'be') &&
//			isset($s['arg1']) &&
//			isset($s['arg2']['of']) &&
//			($s['arg2']['category'] == 'daughter')
//		) {
//			$childId = $s['arg1']['id'];
//			$parentId = $s['arg2']['of']['id'];
//			$triples[] = array('?' . $parentId, '<http://dbpedia.org/ontology/child>', '?' . $childId);
//		}

		// interpret child elements
        foreach ($Phrase->getChildPhrases() as $ChildPhrase) {
            $this->interpretPhrase($ChildPhrase, $sentenceType, $triples, $select, $subjectId);
        }
	}

	private function interpret($phraseSpecification, $sentenceType, &$triples, &$select, $parentId)
	{
		$s = $phraseSpecification;

		if (isset($s['id'])) {
			$subjectId = $s['id'];
		} else {
			$subjectId = $parentId;
		}

		// yes-no-question
		if ($sentenceType == 'yes-no-question') {
			$select = '1';
		}

		// imperative 'name'
		if ($sentenceType == 'imperative' &&
			isset($s['predicate']) && ($s['predicate'] == 'name')) {

			$select = '?name';
			$arg2id = $s['arg2']['id'];
			$triples[] = array('?' . $arg2id, 'rdfs:label', '?name');
			$triples[] = array('FILTER(lang(?name) = "en")');
		}

		if (isset($s['determiner']['question'])) {
			if (($s['determiner']['question'] == true) && ($s['determiner']['category'] == 'many')) {
				$select = 'COUNT(?' . $s['id'] . ')';
			}
		}

		if (isset($s['location']['question'])) {
			$triples[] = array('?' . $s['location']['id'], 'rdfs:label', '?location');
			$select = '?location';
		}
#todo may we combine these, or is this similarity just an exception?
		if (isset($s['time']['question'])) {
			$select = '?' . $s['time']['id'];
		}

		// http://dbpedia.org/ontology/birthPlace
		if (
			isset($s['predicate']) && ($s['predicate'] == 'bear') &&
			isset($s['location']) &&
			isset($s['arg2'])
		) {
			$themeId = $s['arg2']['id'];
			$locationId = $s['location']['id'];
			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/birthPlace>', '?' . $locationId);
		}

		// http://dbpedia.org/ontology/deathPlace
		if (
			isset($s['predicate']) && ($s['predicate'] == 'die') &&
			isset($s['location']) &&
			isset($s['arg1'])
		) {
			$themeId = $s['arg1']['id'];
			$locationId = $s['location']['id'];
			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/deathPlace>', '?' . $locationId);
		}

		// http://dbpedia.org/ontology/birthDate
		if (
			isset($s['predicate']) && ($s['predicate'] == 'bear') &&
			isset($s['time']) &&
			isset($s['arg2'])
		) {
			$themeId = $s['arg2']['id'];
			$timeId = $s['time']['id'];
			$triples[] = array('?' . $themeId, '<http://dbpedia.org/ontology/birthDate>', '?' . $timeId);
		}

		// rdfs:label
		if (isset($s['name'])) {
			$triples[] = array('?' . $subjectId, 'rdfs:label', "'" . ucwords($s['name']) . "'@en");
		}

		// http://dbpedia.org/ontology/author
		if (
			isset($s['category']) && ($s['category'] == 'author') &&
			isset($s['of'])
		) {
			$objectId = $s['of']['id'];
			$triples[] = array('?' . $objectId, '<http://dbpedia.org/ontology/author>', '?' . $subjectId);
		}

		// http://dbpedia.org/property/children
		if (
			isset($s['category']) && ($s['category'] == 'child') &&
			isset($s['determiner']['object'])
		) {
			$objectId = $s['determiner']['object']['id'];
			$triples[] = array('?' . $objectId, '<http://dbpedia.org/property/children>', '?' . $subjectId);
		}

		// http://dbpedia.org/ontology/influencedBy
		if (
			isset($s['predicate']) && ($s['predicate'] == 'influence') &&
			isset($s['agent']) &&
			isset($s['experiencer'])
		) {
			$actorId = $s['agent']['id'];
			$patientId = $s['experiencer']['id'];
			$triples[] = array('?' . $patientId, '<http://dbpedia.org/ontology/influencedBy>', '?' . $actorId);
		}

		// http://dbpedia.org/ontology/child (1)
		if (
			isset($s['predicate']) && ($s['predicate'] == 'have') &&
			isset($s['arg1']) &&
			isset($s['arg2']) &&
			($s['arg2']['category'] == 'child')
		) {
			$possessor = $s['arg1']['id'];
			$posession = $s['arg2']['id'];
			$triples[] = array('?' . $possessor, '<http://dbpedia.org/ontology/child>', '?' . $posession);
		}

		// http://dbpedia.org/ontology/child (2)
		if (
			isset($s['predicate']) && ($s['predicate'] == 'be') &&
			isset($s['arg1']) &&
			isset($s['arg2']['of']) &&
			($s['arg2']['category'] == 'daughter')
		) {
			$childId = $s['arg1']['id'];
			$parentId = $s['arg2']['of']['id'];
			$triples[] = array('?' . $parentId, '<http://dbpedia.org/ontology/child>', '?' . $childId);
		}

		// interpret child elements
		foreach ($s as $key => $value) {
			if (is_array($value)) {
				$this->interpret($s[$key], $sentenceType, $triples, $select, $subjectId);
			}
		}
	}

	/**
	 * @param mixed $query Either a query string or an array of clauses.
	 */
	private function query($where, $select = '*')
	{
		if (is_array($where)) {
			$clauses = $where;
			$triples = array();
			foreach ($clauses as $clause) {
				$triples[] = implode(' ', $clause);
			}
$triples = array_unique($triples);
			$query = "SELECT " . $select . " WHERE {\n\t" . implode(" .\n\t", $triples) . "\n}";
		}

		if (Settings::$debugKnowledge) r($query);

		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;
//r($query);
//r($result);
		if ($result === false) {

			$url = 'http://dbpedia.org/sparql';
			$params = array(
				'default-graph-uri' => 'http://dbpedia.org',
				'query' => $query,
				'format' => 'application/json',
			);

			$json = file_get_contents($url . '?' . http_build_query($params));
			$result = json_decode($json, true);

			if (self::$cacheResults) {
				$this->cacheResult($query, $result);
			}
		}

		if (isset($result['results']['bindings'][0]['callret-0'])) {
			$value = $result['results']['bindings'][0]['callret-0']['value'];
		} elseif (isset($result['results']['bindings'])) {
			$value = array();
			foreach ($result['results']['bindings'] as $binding) {
				$row = array();
				foreach ($binding as $key => $data) {
					$row[$key] = $data['value'];
				}
				$value[] = $row;
			}

		} else {
			$value = null;
		}

		return $value;
	}

	private function querySingleRow($query)
	{
		$result = $this->query($query);
		return $result ? $result[0] : null;
	}

	private function querySingleCell($query)
	{
		$result = $this->querySingleRow($query);
		if ($result) {
			$var = reset($result);
			return $var['value'];
		} else {
			return null;
		}
	}

	private static function getVariableId()
	{
		return 'v' . ++self::$id;
	}

	private function getResultFromCache($query)
	{
		$dir = __DIR__ . '/../../cache/';
		$path = $dir . sha1($query);
		if (file_exists($path)) {
			$json = file_get_contents($path);
			return json_decode($json, true);
		} else {
			return false;
		}
	}

	private function cacheResult($query, $cache)
	{
		$dir = __DIR__ . '/../../cache/';
		$path = $dir . sha1($query);
		if (!file_exists($dir)) {
			mkdir($dir);
		}
		$sha1 = sha1($query);
		$json = json_encode($cache);
		file_put_contents($path, $json);
	}
}