<?php

namespace agentecho\component;

use agentecho\phrasestructure\PhraseStructure;
use agentecho\phrasestructure\Sentence;
use agentecho\datastructure\SentenceContext;
use agentecho\grammar\Grammar;

/**
 * This class creates turns a phrase structure into surface text.
 */
class Producer
{
	/**
	 * Turns an object structure of a phrase or sentence into surface text,
	 *
	 * @param PhraseStructure $Structure
	 * @return string
	 */
	public function produce(PhraseStructure $Structure, Grammar $Grammar)
	{
		if ($Structure instanceof Sentence) {
			$phraseSpecification = array('head' => $this->buildPhraseStructure($Structure));
		} else {
			$phraseSpecification = array('head' => array('sem' => $this->buildPhraseStructure($Structure)));
		}

        $SentenceContext = new SentenceContext();
		$SentenceContext->setPhraseSpecification($phraseSpecification);
        $SentenceContext->RootObject = $Structure;

        return $Grammar->generate($SentenceContext);
	}

	/**
	 * Turns a phrase object structure into an array structure.
	 *
	 * @param PhraseStructure $PhraseStructure
	 * @return array
	 */
	private function buildPhraseStructure(PhraseStructure $PhraseStructure)
	{
		$structure = array();
		$structure['type'] = strtolower(basename(str_replace('\\', '/', get_class($PhraseStructure))));

		foreach ($PhraseStructure->getAttributes() as $name => $value) {
			if ($value instanceof PhraseStructure) {
				$structure[strtolower($name)] = $this->buildPhraseStructure($value);
			} else {
				$structure[$name] = $value;
			}
		}

		return $structure;
	}
}