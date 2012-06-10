<?php

defined('JPATH_PLATFORM') or die;

class TCoreApplication 
{
	
	/**
	 * Initiate the testing modules.
	 * 
	 * @param string $testXMLPath Optional path to the tests XML
	 * 
	 * @return array[boolean] All tests carried out in form [Name=>Success]
	 * @since  1.0
	 */
	public function initiate($testXMLPath = null)
	{
		
		$results = array();
		
		// Get the tests from the XML
		$tests = $this->getTestXML($testXMLPath);

		foreach (array_keys($tests) as $testName) {

			$class_name = $tests[$testName]['class'];
			
			// If the test class doesn't exist, then try the next one
			if (!class_exists($class_name)) {echo $class_name;
				continue;
			}
			
			$params = $tests[$testName]['params'];

			$plugin = new $class_name($params);
			
			// If the plugin initialise is present then run it
			if (method_exists($plugin, 'initialise')) {
				$results[$testName] = $plugin->initialise();
			}
			
		}

		return $results;
		
	}
	
	/**
	 * Read the specified XML file and parse an array of test
	 * plugins to execute including namespace location and 
	 * parameters.
	 * 
	 * Return array - array[plugin_name][namespace|params]
	 * 
	 * @param string $file Path to XML file
	 * 
	 * @return array Array of test plugins
	 * @since  1.0
	 */
	public function getTestXML($file = null)
	{
		
		if (is_null($file)) {
			$file = JPATH_TESTS . '/tests.xml';
		}
		
		if (!is_file($file)) {
			return false;
		}

		$result = array();
		
		// Load the XML file
		$xml = simplexml_load_file($file, 'SimpleXMLElement');
		
		// Get all the test tags ignoring anything else in the file
		$tests = $xml->xpath('/tests/test');
		
		/* Loop through each test tag then save both the name, namespace
		 * and parameters to an array 
		 */
		foreach ($tests as $test) {
			
			$decodedParams 	= null;
			$testName 		= (string) $test->attributes()->name;
			$testClass 		= (string) $test->attributes()->class;
			
			/* Get the json_encoded parameters from the XML file
			 * then decode them into an array.
			 */
			if ($rawParams = (string) $test->attributes()->params) {
				$decodedParams = (array)json_decode($rawParams);
			}
			
			if (is_null($decodedParams)) {
				// Makes things easier later by casting as empty array
				$decodedParams = array();
			}
			
			$result[$testName] 				= array();
			$result[$testName]['class']	= $testClass;
			$result[$testName]['params'] 	= $decodedParams;
			
		}

		return $result;
		
	}
	
	/**
	 * Print out the summary of the entire test.
	 * 
	 * @param array $results Array of results in form [Name=>Success]
	 * 
	 * @return void
	 * @since  1.0
	 */
	public function printSummary($results)
	{
		print("JMMLDAP TEST SUMMARY\n");
		print("-------------------------------\n");
		print(sprintf("%1$-15s\t%2$-15s\n", 'Test', 'Result'));
		print("-------------------------------\n");
		foreach ($results as $key=>$value) {
			print(
				sprintf("%1$-15s\t%2$-15s\n", $key, $value ? "Success" : "Failed")
			);
		}
	}

	
}