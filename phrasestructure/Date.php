<?php

namespace agentecho\phrasestructure;

/**
 * A conjunction of two entities.
 */
class Date extends PhraseStructure implements EntityStructure
{
	protected $data = array(
        'year' => null,
        'month' => null,
		'day' => null
    );

    public function setYear($year)
    {
        $this->data['year'] = $year;
    }

    public function getYear()
    {
        return $this->data['year'];
    }

    public function setMonth($month)
    {
        $this->data['month'] = $month;
    }

    public function getMonth()
    {
        return $this->data['month'];
    }

	public function setDay($day)
	{
		$this->data['day'] = $day;
	}

	public function getDay()
	{
		return $this->data['day'];
	}
}