<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Sentence;

/**
 * A determiner
 */
class RelativeClause extends PhraseStructure
{
	protected $data = array(
		'complementizer' => null,
        'Clause' => null,
	);

#	public function setComplementizer(Preposition $Complementizer)
	public function setComplementizer($complementizer)
	{
		$this->data['complementizer'] = $complementizer;
	}

    public function getComplementizer()
    {
        return $this->data['complementizer'];
    }

    public function setClause(Clause $Clause)
    {
        $this->data['Clause'] = $Clause;
    }

    public function getClause()
    {
        return $this->data['Clause'];
    }
}