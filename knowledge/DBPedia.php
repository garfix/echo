<?php

namespace agentecho\knowledge;

use agentecho\datastructure\Constant;
use agentecho\phrasestructure\Sentence;
use agentecho\phrasestructure\Entity;
use agentecho\phrasestructure\Clause;
use agentecho\datastructure\PredicationList;
use agentecho\component\DataMapper;
use agentecho\datastructure\SparqlQuery;
use agentecho\datastructure\Predication;
use agentecho\exception\DataMappingFailedException;

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

	public function __construct($dataMapFile)
	{
		$this->dataMapFile = $dataMapFile;
	}

#todo Note! This function is not currently used
	/**
	 * @param $identifier
	 * @return bool
	 */
	public function isProperNoun($identifier)
	{
		$resources = $this->getResourcesByName($identifier);
		return !empty($resources);
	}

	/**
	 * Returns the URI's of all resources in DBPedia that can be identified by $name
	 * as either a label or a birth name.
	 *
	 * @param $name
	 * @return array
	 */
	private function getResourcesByName($name)
	{
		static $resources = array();

		if (!isset($resources[$name])) {

			$where = array(" { { ?object rdfs:label '$name'@en } UNION { ?object dbpprop:birthName '$name'@en } }");
			$resources[$name] = $this->querySingleColumn($where, '?object');

		}

		return $resources[$name];
	}

	/**
	 * @param mixed $query Either a query string or an array of clauses.
	 */
	private function query(array $where, $select = '*')
	{
		$query = "SELECT " . $select . " WHERE {\n\t" . implode(" .\n\t", $where) . "\n}";

		$value = $this->queryDBPedia($query);

		return $value;
	}

	private function  queryDBPedia($query)
	{
		$result = self::$cacheResults ? $this->getResultFromCache($query) : false;
# $result = false;

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

	private function querySingleRow($query, $select = '*')
	{
		$result = $this->query($query, $select);
		return $result ? $result[0] : null;
	}

	private function querySingleCell($query, $select = '*')
	{
		$result = $this->querySingleRow($query, $select);
		if ($result) {
			$var = reset($result);
			return is_array($var) ? $var['value'] : $var;
		} else {
			return null;
		}
	}

	private function querySingleColumn($query, $select = '*')
	{
		$return = array();
		$result = $this->query($query, $select);
		if ($result) {
			foreach ($result as $row) {
				$return[] = reset($row);
			}
		}
		return $return;
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

	public function answer(PredicationList $Question)
	{
$b = (string)$Question;
		// turn the expanded question into a set of database relations
		$Relations = $this->getDataMapper()->mapPredications($Question);


		// convert the database relations into a query
		$Query = $this->createDatabaseQuery($Relations);

$sparql = (string)$Query;

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

	private function createDatabaseQuery(PredicationList $Relations)
	{
		$Query = new SparqlQuery();

		foreach ($Relations->getPredications() as $Relation) {

			$this->convertRelationIntoClauses($Relation, $Query);
		}

		return $Query;
	}

	private function convertRelationIntoClauses(Predication $Relation, SparqlQuery $Query)
	{
		$predicate = $Relation->getPredicate();

		switch ($predicate) {
			case 'true';
				break;
			case 'born_at':
				$subject = (string)$Relation->getArgument(0)->getName();
				$object = (string)$Relation->getArgument(1)->getName();
				$Query->where("?{$subject} <http://dbpedia.org/ontology/birthDate> ?{$object}");
				$Query->select("?{$subject}");
				$Query->select("?{$object}");
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
				$Query->where("_:place dbpedia-owl:city _:placeId");
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
				$Query->where("_:place dbpedia-owl:city _:placeId");
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
				$Query->where("?{$object} <http://dbpedia.org/ontology/influencedBy> ?{$subject}");
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
				die('Relation not defined: ' . $predicate);
				break;
		}
	}

	private function processQuery(SparqlQuery $Query)
	{
		$result = $this->queryDBPedia((string)$Query);
		return $result;
	}
}