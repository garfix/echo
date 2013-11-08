<?php

namespace agentecho\phrasestructure;

use agentecho\datastructure\Atom;
use \agentecho\phrasestructure\PhraseStructure;

/**
 * A determiner
 */
class Determiner extends PhraseStructure
{
	const THE = 'the';
	const MANY = 'many';

	protected $data = array(
		'category' => null,
        'question' => false,
        // object is an Entity (as in the determiner "Byron's children")
        'Object' => null,
		'Unit' => null,
	);

	/**
	 * @param string $category Use one of the constants of this class.
	 */
	public function setCategory($category)
	{
		$this->data['category'] = $category;
	}

    public function getCategory()
    {
        return $this->data['category'];
    }

    /**
     * Mark this determiner as an unknown that needs to be resolved (a question).
     */
    public function setQuestion($question = true)
    {
        $this->data['question'] = $question;
    }

    public function isQuestion()
    {
        return $this->data['question'];
    }

    public function setObject(Entity $Object)
   	{
   		$this->data['Object'] = $Object;
   	}

   public function getObject()
   {
       return $this->data['Object'];
   }

	public function setUnit(Atom $Unit)
	{
		$this->data['Unit'] = $Unit;
	}

	public function getUnit()
	{
	    return $this->data['Unit'];
	}
}