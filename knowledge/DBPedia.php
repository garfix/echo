<?php

namespace agentecho\knowledge;

use agentecho\component\events\LogEvent;
use agentecho\datastructure\Constant;
use agentecho\datastructure\RelationList;
use agentecho\component\DataMapper;
use agentecho\datastructure\SparqlQuery;
use agentecho\datastructure\Relation;

/**
 * An adapter for DBPedia.
 *
 * Docs:
 * - SPARQL 1.1 spec:       http://www.w3.org/TR/sparql11-query/
 * - SPARQL cheat sheet:    http://www.slideshare.net/LeeFeigenbaum/sparql-cheat-sheet
 *
 * Online:
 * - http://dbpedia.org/sparql
 */
class DBPedia extends KnowledgeSource
{
	static $cacheResults = true;
	static $id = 0;

	private $dataMapFile = null;
	private $Mapper = null;

	public function __construct()
	{
		$this->dataMapFile = __DIR__ . '/../resources/dbpedia.map';
	}

	private function  queryDBPedia($query)
	{
		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;

		if ($result === false) {

			$url = 'http://dbpedia.org/sparql';
			$params = array(
				'default-graph-uri' => 'http://dbpedia.org',
				'query' => $query,
				'format' => 'application/json',
			);

			$json = @file_get_contents($url . '?' . http_build_query($params));

			if ($json !== false) {
				$result = json_decode($json, true);
				if (self::$cacheResults) {
					$this->cacheResult($query, $result);
				}
			}
		}

		if (isset($result['results']['bindings'][0]['callret-0'])) {

			// single value
			$value = $result['results']['bindings'][0]['callret-0']['value'];

		} elseif (isset($result['results']['bindings'])) {

			// a result set
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
		$json = json_encode($cache);
		file_put_contents($path, $json);
	}

	public function answer(RelationList $Question)
	{
		// turn the expanded question into a set of database relations
		$Relations = $this->getDataMapper()->mapRelations($Question);
		$this->send(new LogEvent(array('relations' => $Relations)));

		// convert the database relations into a query
		$Query = $this->createDatabaseQuery($Relations);

		$this->send(new LogEvent(array('query' => $Query)));

		$resultSets = $this->processQuery($Query);

		return $resultSets;
	}

	private function getDataMapper()
	{
		if ($this->Mapper === null) {
			$this->Mapper = new DataMapper($this->dataMapFile);
		}
		return $this->Mapper;
	}

	private function createDatabaseQuery(RelationList $Relations)
	{
		$Query = new SparqlQuery();

		foreach ($Relations->getRelations() as $Relation) {

			$this->convertRelationIntoClauses($Relation, $Query);
		}

		return $Query;
	}

	private function convertRelationIntoClauses(Relation $Relation, SparqlQuery $Query)
	{
		$predicate = $Relation->getPredicate();

		switch ($predicate) {
			case 'true';
				break;
			case 'born_in':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				// link to the place id
				$Query->where("?{$subject} <http://dbpedia.org/ontology/birthPlace> _:placeId");
				// link place id to place name
				$Query->where("_:placeId rdfs:label ?{$object}");
				// place name should be in english
				$Query->where("FILTER(lang(?{$object}) = 'en')");
				// place should be city
				$Query->where("_:placeId rdf:type <http://dbpedia.org/class/yago/City108524735>");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'born_at':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/birthDate> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'die_in':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				// link to the place id
				$Query->where("?{$subject} <http://dbpedia.org/ontology/deathPlace> _:placeId");
				// link place id to place name
				$Query->where("_:placeId rdfs:label ?{$object}");
				// place name should be in english
				$Query->where("FILTER(lang(?{$object}) = 'en')");
				// place should be city
				$Query->where("_:placeId rdf:type <http://dbpedia.org/class/yago/City108524735>");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'die_at':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/deathDate> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'name':
				$subject = (string)$Relation->getArgument(0)->getName();
				if ($Relation->getArgument(1) instanceof Constant) {
					$object = (string)$Relation->getArgument(1)->getName();
					$ucName = ucwords($object);
					$Query->where("{ { ?{$subject} rdfs:label '$ucName'@en } UNION { ?{$subject} dbpprop:birthName '$ucName'@en } }");
				} else {
					$object = (string)$Relation->getArgument(1)->getName();
					$Query->where("{ { ?{$subject} rdfs:label ?{$object} } UNION { ?{$subject} dbpprop:birthName ?{$object} } }");
					$Query->where("FILTER(lang(?{$object}) = 'en')");
					$Query->select("?{$object}");
				}
				$Query->select("?{$subject}");
				break;
			case 'label':
				$subject = (string)$Relation->getArgument(0)->getName();
				if ($Relation->getArgument(1) instanceof Constant) {
					$object = (string)$Relation->getArgument(1)->getName();
					$ucName = ucwords($object);
					$Query->where("{ ?{$subject} rdfs:label '$ucName'@en }");
				} else {
					$object = (string)$Relation->getArgument(1)->getName();
					$Query->where("{ ?{$subject} rdfs:label ?{$object} }");
					$Query->where("FILTER(lang(?{$object}) = 'en')");
					$Query->select("?{$object}");
				}
				$Query->select("?{$subject}");
				break;
			case 'time_property':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("{ { ?{$subject} <http://dbpedia.org/ontology/birthDate> ?{$object} } UNION { ?{$subject} <http://dbpedia.org/ontology/deathDate> ?{$object} } }");
				break;
			case 'author':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$object} <http://dbpedia.org/ontology/author> ?{$subject}");
				break;
			case 'influence':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				// the relation seems backward in DBPedia
				$Query->where("?{$object} <http://dbpedia.org/property/influences> ?{$subject}");
				break;
			case 'child':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/child> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			case 'marry':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("{ { ?{$subject} dbpprop:spouse ?{$object} } UNION { ?{$object} dbpprop:spouse ?{$subject} } }");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
				break;
			default:
				die('Predicate not defined: ' . $predicate);
				break;
		}
	}

	private function processQuery(SparqlQuery $Query)
	{
		$result = $this->queryDBPedia((string)$Query);
		return $result;
	}
}