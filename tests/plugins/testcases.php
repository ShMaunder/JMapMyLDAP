<?php

defined('JPATH_PLATFORM') or die;

class TPluginsTestcases extends TCorePlugin
{
	
	/**
	 * Namespace to the cases directory
	 * 
	 * @var string
	 */
	protected $caseNS = null;
	
	/**
	 * Path to XML file
	 * 
	 * @var string
	 */
	protected $xml = null;
	
	/**
	 * Class constructor.
	 *
	 * @param string $params Parameters for the testcases test plugin
	 *
	 * @since 1.0
	 */
	function __construct($params = array())
	{
		parent::__construct($params);
		
		// Make some adjustments to the parameters
		$this->xml = str_replace('TESTS_PATH', JPATH_TESTS, $this->xml);
	}
	
	/**
	 * Start the TestCases test plugin and return if any errors
	 * were found.
	 *
	 * @return boolean True if no errors or False if errors found
	 * @since  1.0
	 */
	public function initialise()
	{

		// Default to no errors and only set to false if one is found
		$return = true;
		
		if (is_null($this->xml)) {
			$this->xml = TESTS_PATH . '/cases/cases.xml';
		}
		
		// Get all the categories and cases from the XML file
		if (!$categories = TPluginsTestcasesHelper::getXMLCases($this->xml)) {
			return false;
		}

		$caseResults = array();
		
		foreach ($categories as $category=>$cases) {
				
			// Ignore any categories that has no cases
			if (!is_array($cases) || !count($cases)) {
				continue;
			}
				
			foreach ($cases as $case) {
		
				// Lets test this case and get a CaseResult object for it
				$caseResults[] = TPluginsTestcasesHelper::doCase($this->caseNS, $category, $case);
		
			}
				
		}

		// Print out results and check if there was an error
		foreach ($caseResults as $caseResult) {
				
			if ($caseResult->getStatus() != TPluginsTestcasesCaseresult::STATUS_SUCCESS) {
				// There was an error in the unit testing
				$return = false;
			}
				
			TPluginsTestcasesHelper::printCase($caseResult, true);
				
		}
		
		return $return;
	}
	
}