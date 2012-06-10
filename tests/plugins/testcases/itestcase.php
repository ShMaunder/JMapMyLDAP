<?php

defined('JPATH_PLATFORM') or die;

interface TPluginsTestcasesItestcase
{
	
	/**
	 * This method will be used to initiate anything that
	 * is required as a prerequisite of the test. Such things
	 * may include initialising objects or checking classes
	 * exist.
	 * 
	 * If this method fails then the test's case status is 
	 * set to CaseResult::STATUS_INITIATE_FAIL
	 * 
	 * @return boolean True on success or False on failure
	 * @since  1.0
	 */
	public function initialise();
	
	/**
	 * This method will run the tests and generate results. 
	 * These results are stored inside an array of UnitResults
	 * which is returned. This method however shouldn't 
	 * be used to check the result - the test[CaseName]() method
	 * is used for this purpose.
	 * 
	 * @return array[UnitResult] An array of UnitResults
	 * @since  1.0
	 */
	public function runTests();
	
}