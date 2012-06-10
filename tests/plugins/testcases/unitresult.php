<?php

defined('JPATH_PLATFORM') or die;

class TPluginsTestcasesUnitresult extends TPluginsTestcasesResult
{
	
	protected $name = null;
	
	protected $result = null;
	
	const STATUS_NO_TEST_METHOD = '3';
	
	function __construct($name, $result = null)
	{
		$this->name = $name;
		$this->result = $result;
		$this->status = self::STATUS_UNSET;
	}
	
	
	public function getName()
	{
		return $this->name;
	}
	
	
	public function setResult($result)
	{
		// If the result is already set, don't set it again
		if (is_null($this->result)) {
			$this->result = $result;
		}
	}
	
	public function getResult()
	{
		return $this->result;
	}
}