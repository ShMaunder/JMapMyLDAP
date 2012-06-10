<?php

defined('JPATH_PLATFORM') or die;

class TPluginsTestcasesHelper
{
	
	/**
	 * Read in the case XML file and parse it to an
	 * array in the form array(category=>case).
	 * 
	 * @param string $file Path to the XML file
	 * 
	 * @return array Array of cases
	 * @since  1.0
	 */
	public static function getXMLCases($file)
	{
		if (!is_file($file)) {
			return false;
		}
		
		$result = array();
		
		// Load the XML file
		$xml = \simplexml_load_file($file, 'SimpleXMLElement');
		
		// Get all the category tags ignoring anything else in the file
		$categories = $xml->xpath('/tests/category');
		
		/* Loop through the categories and each case inside the
		 * category and storing the name attribute to an array.
		 */
		foreach ($categories as $category) {
			$categoryName = (string) $category->attributes()->name;
			$result[$categoryName] = array();
			
			foreach ($category as $case) {
				$caseName = (string) $case->attributes()->name;
				$result[$categoryName][] = $caseName;
			}
		}
		
		return $result;
	}
	

	/**
	 * Process the case by actually testing it then returing
	 * a CaseResult to the subject.
	 * 
	 * @param string $ns       Namespace of cases directory
	 * @param string $category Name of the case category
	 * @param string $case     Case name
	 * 
	 * @return \gamrphp\tests\core\cases\CaseResult
	 * @since  1.0
	 */
	public static function doCase($ns, $category, $case)
	{
		// Ensure the camel case exists
		$category{0} = strtoupper($category{0});
		$case{0} = strtoupper($case{0});
		
		// Build the class name
		$namespace = "$ns$category$case";

		$caseResult = new TPluginsTestcasesCaseresult($category, $case);

		if (!class_exists($namespace)) { 
			// This case doesn't exist so lets return a failure
			$caseResult->setStatus(
				TPluginsTestcasesCaseresult::STATUS_CASE_NOT_FOUND
			);
			return $caseResult;
		}
		
		// Create the case object so we can start the tests
		$case = new $namespace($caseResult);
		
		// We will assume that all cases implement ITestCase
		if (!$case->initialise()) {
			$caseResult->setStatus(TPluginsTestcasesCaseresult::STATUS_INITIATE_FAIL);
			return $caseResult;
		}
		
		$unitTests = $case->runTests();
		if (!count($unitTests)) {
			$caseResult->setStatus(TPluginsTestcasesCaseresult::STATUS_RUN_TEST_FAIL);
			return $caseResult;
		}
		
		// Check the test results against hardcoded test results
		$case->checkResult($unitTests);
		
		return $caseResult;
		
	}
	
	/**
	 * Print the entire test case including test unit results.
	 * 
	 * @param CaseResult $results Case to print out
	 * @param boolean    $header  Print the unit test header
	 * @param boolean    $showAll Print all unit tests including success ones
	 * 
	 * @return void
	 * @since  1.0
	 */
	public static function printCase(
		TPluginsTestcasesCaseresult $results, $header = true, $showAll = true
	) {
		$statusMsg = 'Unknown';
		$continue = false;
		$status = $results->getStatus();
		$case = strtoupper($results->getCase());
		$category = strtoupper($results->getCategory());
		
		/*
		 * Set some english strings for our status'
		 */
		switch($status) {
			
		case TPluginsTestcasesCaseresult::STATUS_SUCCESS:
			$statusMsg = 'Success';
			$continue = true;
			break;
			
		case TPluginsTestcasesCaseresult::STATUS_INITIATE_FAIL:
			$statusMsg = 'Initiate Failed';
			break;
			
		case TPluginsTestcasesCaseresult::STATUS_CASE_NOT_FOUND:
			$statusMsg = 'Case Not Found';
			break;
		
		case TPluginsTestcasesCaseresult::STATUS_RUN_TEST_FAIL:
			$statusMsg = 'RunTest() failed';
			break;
			
		case TPluginsTestcasesCaseresult::STATUS_FAILED:
			$statusMsg = 'Failed';
			$continue = true;
			break;
			
		}
		
		// Initial title for this category and case
		print("JMMLDAP UNIT TEST CASE ($category::$case) - $statusMsg\n");
		
		// We have to exit if the unit tests don't exist (i.e. not carried out)
		if (!$continue) {
			print("\n");
			return;
		}
		
		if ($header) {
			// Print the header for unit testing results
			print("-------------------------------------------------" . 
					"-------------------------------\n");
			print(sprintf(
				"%1$-15s\t%2$-45s\t%3$-15s\n", 
				'Name', 'Message', 'Result'
			));
			print("-------------------------------------------------" . 
					"-------------------------------\n");
		}
		
		// Print the unit tests to screen
		foreach ($results->getUnitResults() as $unitResult) {
			
			/* If showall is enabled then we print out all unit tests
			 * but it showall is disabled then we print out everything
			 * but success status'.
			 */
			if (($showAll) 
				|| (!$showAll 
				&& !$unitResult->getStatus() !== UnitResult::STATUS_SUCCESS)
			) {
				self::printUnit($unitResult);
			}
			
		}
		
		print("\n");
	}
	
	/**
	 * Prints a single Test Unit to screen
	 * 
	 * @param UnitResult $result The test unit to print
	 * 
	 * @return void
	 * @since  1.0
	 */
	public static function printUnit(TPluginsTestcasesUnitresult $result)
	{
		$statusMsg = 'Unknown';
		$name = $result->getName();
		$msg = $result->getLastMessage();
		$status = $result->getStatus();
		
		/*
		 * Set some english strings for our status'
		*/
		switch($status) {
				
		case TPluginsTestcasesUnitresult::STATUS_SUCCESS:
			$statusMsg = 'Success';
			break;
					
		case TPluginsTestcasesUnitresult::STATUS_FAILED:
			$statusMsg = 'Failed';
			break;
				
		case TPluginsTestcasesUnitresult::STATUS_NO_TEST_METHOD:
			$statusMsg = 'No Test Method';
			break;
					
		}
		
		if (!$msg) {
			$msg = "N/A";
		}
		
		// Print out the unit test result
		print(sprintf(
			"%1$-15s\t%2$-45s\t%3$-15s\n",
			$name, $msg, $statusMsg
		));
		
	}
	
	public static function pushTestUnit(&$array, $name, $value)
	{
		if (!is_array($array)) {
			$array = array();
		}
		
		$array[] = new TPluginsTestcasesUnitresult($name, $value);
	}
}