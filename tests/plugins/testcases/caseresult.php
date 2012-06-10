<?php

defined('JPATH_PLATFORM') or die;

class TPluginsTestcasesCaseresult extends TPluginsTestcasesResult
{
	
	protected $category = null;
	
	protected $case = null;
	
	/**
	 * Holds an array of unit results
	 * 
	 * @var array
	 */
	protected $unitResults = array();
	
	
	const STATUS_INITIATE_FAIL = '2';
	
	const STATUS_CASE_NOT_FOUND = '3';
	
	const STATUS_RUN_TEST_FAIL = '4';
	
	function __construct($category, $case) 
	{
		$this->category = $category;
		$this->case = $case;
		$this->status = self::STATUS_UNSET;
	}
	
	
	public function setUnitResults($results)
	{
		$this->unitResults = $results;
	}
	
	/**
	 * @return array
	 */
	public function getUnitResults()
	{
		return $this->unitResults;
	}
	
	public function getCase()
	{
		return $this->case;
	}
	
	public function getCategory()
	{
		return $this->category;
	}
	
}