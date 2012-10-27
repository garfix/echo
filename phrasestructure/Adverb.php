<?php

namespace agentecho\phrasestructure;

/**
 * A modifier for a Clause or ...
 *
 * todo: I am not happy with the name of this structure. It's an adverb, but it should have a more 'semantic' name
 *
 */
class Adverb extends PhraseStructure
{
	protected $data = array(
		'category' => null,
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
}