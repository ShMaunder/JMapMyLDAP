<?php

defined('JPATH_PLATFORM') or die;

class TPluginsCodesniffer extends TCorePlugin
{

	/**
	 * Path to the CodeSniffer program files
	 *
	 * @var   string
	 * @since 1.0
	 */
	protected $path = null;

	/**
	 * Type of report to produce (e.g. full, summary)
	 * 
	 * @var   string
	 * @since 1.0
	 */
	protected $type = null;
	
	/**
	 * Tab width to print
	 * 
	 * @var   integer
	 * @since 1.0
	 */
	protected $width = 80;
	
	/**
	 * Array of files to inspect
	 * 
	 * @var   array
	 * @since 1.0
	 */
	protected $files = null;
	
	/**
	 * Save report to a file path
	 * 
	 * @var   string
	 * @since 1.0
	 */
	protected $reportFile = null;
	
	/**
	 * Holds the main PHP CodeSniffer object
	 *
	 * @var   \PHP_CodeSniffer
	 * @since 1.0
	 */
	protected $phpcs = null;

	/**
	 * Holds number of errors found
	 *
	 * @var   integer
	 * @since 1.0
	 */
	protected $errors = 0;

	/**
	 * Class constructor.
	 *
	 * @param string $params Parameters for the codesniffer test plugin
	 *
	 * @since 1.0
	 */
	function __construct($params = array())
	{
		// Save the parameters to the class members
		parent::__construct($params);
		
		// Make some adjustments to the parameters
		$this->files = str_replace('ROOT_PATH', JPATH_ROOT, $this->files);
		$this->files = explode(';', $this->files);
		$this->path = str_replace('TESTS_PATH', JPATH_TESTS, $this->path);
	}
	
	/**
	 * Start the CodeSniffer test plugin and return if any errors
	 * were found.
	 * 
	 * @return boolean True if no errors or False if errors found
	 * @since  1.0
	 */
	public function initialise()
	{
		if ($this->process()) {
			// Output the report either to a file or screen
			$this->getReport($this->type, $this->reportFile, $this->width);
			return !$this->hasErrors();
		}
	}

	/**
	 * Initialise PEAR CodeSniffer then process the specified
	 * PHP files for code style errors.
	 *
	 * @return boolean True on success or False on failure
	 * @since  1.0
	 */
	public function process()
	{

		$base = $this->path;

		if (!$this->checkRequirements()) {
			return false;
		}
		
		if (!is_file($base . '/../CodeSniffer.php') === true) {
			return false;
		}

		include_once $base . '/../CodeSniffer.php';
		
		// Get the attributes (i.e. parameters) for codesniffer
		$values = $this->getAttributes();

		// We need to add the specified files to the attributes
		$values['files'] = $this->files;
		
		// Check for a valid standard
		if (\PHP_CodeSniffer::isInstalledStandard($values['standard']) === false) {
			echo 'ERROR: the "'.$values['standard'].
			'" coding standard is not installed. ';
			return false;
		}
		
		$this->phpcs = new \PHP_CodeSniffer(
			$values['verbosity'],
			$values['tabWidth'],
			$values['encoding'],
			$values['interactive']
		);
		
		// Set ignore patterns if they were specified.
		if (empty($values['ignored']) === false) {
			$this->phpcs->setIgnorePatterns($values['ignored']);
		}
		
		try {
			// Time to do the sniff process
			$this->phpcs->process(
				$values['files'],
				$values['standard'],
				$values['sniffs'],
				$values['local']
			);
		} catch(\PHP_CodeSniffer_Exception $e) {
			echo $e->getMessage();
			return false;
		}
		
		return true;

	}

	/**
	 * Gets the CodeSniffer error report.
	 *
	 * @param string  $type       Type of report (e.g. summary, full)
	 * @param string  $reportFile Save the report to a file (turns off screen output)
	 * @param integer $width      Width of the report
	 *
	 * @return void
	 * @since  1.0
	 */
	public function getReport($type = 'summary', $reportFile = null, $width = 80)
	{
		$reporting      = new \PHP_CodeSniffer_Reporting();
		$violations 	= $this->phpcs->getFilesErrors();

		// Generate and output report
		$this->errors = $reporting->printReport(
			$type, $violations, false, $reportFile, $width
		);
	}

	/**
	 * Generates some attributes (i.e. parameters) for
	 * CodeSniffer.
	 *
	 * @return array Array of attributes
	 * @since  1.0
	 */
	protected function getAttributes()
	{
		$attributes = array();
		$attributes['standard'] 		= 'PEAR';
		$attributes['verbosity'] 		= 0;
		$attributes['tabWidth'] 		= 4;
		$attributes['encoding'] 		= 'iso-8859-1';
		$attributes['interactive'] 		= false;
		$attributes['ignored']			= false;
		$attributes['sniffs']			= array();
		$attributes['local']			= false;
		$attributes['files']			= array();

		return $attributes;
	}

	/**
	 * Check if code sniffer can run on this PHP server.
	 *
	 * @return boolean True on success or False on failure
	 * @since  1.0
	 */
	public function checkRequirements()
	{
		if (version_compare(PHP_VERSION, '5.1.2') === -1) {
			echo 'ERROR: PHP_CodeSniffer requires PHP version 5.1.2 or greater.' . PHP_EOL;
			return false;
		}

		if (extension_loaded('tokenizer') === false) {
			echo 'ERROR: PHP_CodeSniffer requires the tokenizer extension to be enabled.' . PHP_EOL;
			return false;
		}

		return true;
	}

	/**
	 * Return if any errors were found with the code style.
	 *
	 * @return boolean True if errors found or False if no errors
	 * @since  1.0
	 */
	public function hasErrors()
	{
		return $this->errors > 0;
	}
}