<?php

namespace agentecho\phrasestructure;

use \agentecho\phrasestructure\PhraseStructure;
use \agentecho\phrasestructure\Entity;

/**
 * A conjunction of two entities.
 */
class Conjunction extends PhraseStructure
{
	protected $data = array(
        'Left' => null,
        'Right' => null
    );

    public function setLeftEntity(Entity $Left)
    {
        $this->data['Left'] = $Left;
    }

    public function getLeftEntity()
    {
        return $this->data['Left'];
    }

    public function setRightEntity(Entity $Right)
    {
        $this->data['Right'] = $Right;
    }

    public function getRightEntity()
    {
        return $this->data['Right'];
    }
}