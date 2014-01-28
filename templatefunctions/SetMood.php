<?php

namespace agentecho\templatefunctions;

use agentecho\datastructure\Atom;
use agentecho\datastructure\Relation;
use agentecho\datastructure\RelationList;
use agentecho\datastructure\Variable;

/**
 * @author Patrick van Bergen
 */
class SetMood
{
	function invoke(array $parameters, RelationList $Question)
	{
		$Relations = new RelationList();

		$Mood = $Question->getRelationByPredicate('mood');

		$SentenceRelation = $Question->getRelationByPredicate('sentence');
		if ($SentenceRelation) {

			/** @var Variable $SentenceEvent */
			$SentenceEvent = $SentenceRelation->getArgument(0);

			$Question->removeRelation($Mood);
			$DeclarativeMood = new Relation();
			$DeclarativeMood->setPredicate('mood');
			$A0 = $SentenceEvent->createClone();
			$A1 = new Atom('Declarative');
			$DeclarativeMood->setArgument(0, $A0);
			$DeclarativeMood->setArgument(1, $A1);
			$Relations->addRelation($DeclarativeMood);
		}

		return $Relations;
	}
}
