<?php

require_once(__DIR__ . '/SimpleGrammar.php');

class DutchGrammar extends SimpleGrammar
{
	public function getLexicon()
	{
		return array(
			'auteur' => 'noun',
			'ben' => 'verb',
			'beïnvloed' => 'verb',
			'boek' => 'verb',
			'de' => array('article', 'determiner'),
			'die' => 'determiner',
			'door' => 'preposition',
			'dochter' => 'noun',
			'een' => array('article', 'determiner'),
			'had' => 'verb',
			'geboren' => 'verb',
			'het' => 'article',
			'hoeveel' => 'wh-word',
			'ik' => 'pronoun',
			'kinderen' => 'noun',
			'van' => 'preposition',
			'vlucht' => 'noun',
			'was' => 'aux',
			'werd' => 'aux',
			'waar' => 'wh-word',
			'wanneer' => 'wh-word',
		);
	}

	public function getWord2PhraseStructure()
	{
		return array(
			'preposition' => array(
				'van' => array('preposition' => '*belong-to'),
				'door' => array('preposition' => '*actor'),
			),
			'verb' => array(
				'beïnvloed' => array('predicate' => '*influence'),
				'boek' => array('predicate' => '*book'),
				'ben' => array('predicate' => '*be'),
				'had' => array('predicate' => '*have'),
				'geboren' => array('predicate' => '*give-birth'),
			),
			'noun' => array(
				'auteur' => array('isa' => '*author'),
				'vlucht' => array('isa' => '*flight'),
				'kinderen' => array('isa' => '*child'),
				'dochter' => array('isa' => '*daughter'),
			),
			'pronoun' => array(
				'ik' => array('referring-expression' => '*current-speaker'),
			),
			'determiner' => array(
				'die' => array('determiner' => '*that'),
				'de' => array('determiner' => '*the'),
				'een' => array('determiner' => '*a'),
			),
			'wh-word' => array(
				'wanneer' => array('question' => '*time'),
				'waar' => array('question' => '*location'),
				'hoeveel' => array('question' => '*nature-of', 'determiner' => '*many'),
			)
		);
	}
}
