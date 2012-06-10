<?php

defined('JPATH_PLATFORM') or die;

abstract class TPluginsTestcasesTestcase
{
	
	protected $caseResult = null;
	
	function __construct(TPluginsTestcasesCaseresult &$caseResult)
	{
		$this->caseResult =& $caseResult;
	}
	
	public function getCaseResult()
	{
		return $this->caseResult;
	}
	
	public function checkResult($results)
	{
		$error = false;
		
		/* Loop around each unit test to confirm the 
		 * result is correct by calling the method
		 * for each test. For example, if a unit test was
		 * called 'connect' then it will try to call the 
		 * method 'testconnect()'.
		 */
		foreach ($results as &$result) {
			$unitName = sprintf('test%s', $result->getName());
			
			if (method_exists($this, $unitName)) {
				// Fire the method to test the test result
				$this->$unitName($result);
				
				// Check the result and set the case error flag if required
				if ($result->getStatus() != TPluginsTestcasesUnitresult::STATUS_SUCCESS) {
					$error = true;
				}
				
			} else {
				// There is no method to test if the test result was correct
				$result->setStatus(TPluginsTestcasesUnitresult::STATUS_NO_TEST_METHOD);
				$error = true; 
			}
		}
		
		if ($error === false) {
			$this->caseResult->setStatus(TPluginsTestcasesCaseresult::STATUS_SUCCESS);
		} else {
			$this->caseResult->setStatus(TPluginsTestcasesCaseresult::STATUS_FAILED);
		}

		// As we have now finished save the unit results for later
		$this->caseResult->setUnitResults($results);
		
	}
	
}