<?php

class Microplanner
{
	/**
	 * Turns an intention (with meaning and speech act) into a syntax tree.
	 *
	 * This process consists of these context tasks (Building Natural Language Generation Systems, p. 49):
	 * - Lexicalisation (choosing words and syntactic constructions)
	 * - Referring expression Generation (what expressions refer to entities)
	 * and this structure task:
	 * - Aggregation (mapping semantic structures to linguistic structures)
	 *
	 * @param array $semantics
	 * @return array A syntax tree
	 */
	public function plan(array $semantics)
	{
		$tree = array(
			'part-of-speech' => 'S'
		);

		foreach ($semantics as $triple) {
			list ($subject, $predicate, $object) = $triple;
			if ($predicate == 'name') {
				$np = array(
					'part-of-speech' => 'NP',
					'constituents' => array(
						array(
							'part-of-speech' => 'pronoun',
# todo: referring expression
							'word' => $subject
						)
					)
				);
				$vp = array(
					'part-of-speech' => 'VP',
					'constituents' => array(
						array(
							'part-of-speech' => 'verb',
							'word' => 'be'
						),
						array(
							'part-of-speech' => 'NP',
							'constituents' => array(
								array(
									'part-of-speech' => 'proper-noun',
									'word' => $object
								)
							)
						)
					)
				);
				$tree['constituents'] = array($np, $vp);
			}
		}
		return $tree;
	}
}