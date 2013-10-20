<?php
/**
 * Adapted from the Joomla Database 2.5 and Platform Testing.
 * Due to a lack of sqlite driver in 2.5.x series, we have to use a real
 * sql database. Totally uncool!
 *
 * These tests are forced to use MySQLI (the currently only supported database)
 *
 * @package    Joomla.Test
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once 'PHPUnit/Extensions/Database/DataSet/XmlDataSet.php';
require_once 'PHPUnit/Extensions/Database/DataSet/QueryDataSet.php';
require_once 'PHPUnit/Extensions/Database/DataSet/MysqlXmlDataSet.php';

/**
 * Abstract test case class for database testing.
 *
 * @package  Joomla.Test
 * @since    12.1
 */
abstract class TestCaseDatabase extends PHPUnit_Extensions_Database_TestCase
{
	public static $database;

	public static $dbo;

	private static $options = array('driver' => 'mysqli');

	/**
	 * @var factoryState
	 */
	protected $factoryState = array ();

	/**
	 * @var errorState
	 */
	protected $savedErrorState;

	/**
	 * @var actualError
	 */
	protected static $actualError;

	/**
	 * Saves the current state of the JError error handlers.
	 *
	 * @return	void
	 */
	protected function saveErrorHandlers()
	{
		$this->savedErrorState = array ();
		$this->savedErrorState[E_NOTICE] = JError :: getErrorHandling(E_NOTICE);
		$this->savedErrorState[E_WARNING] = JError :: getErrorHandling(E_WARNING);
		$this->savedErrorState[E_ERROR] = JError :: getErrorHandling(E_ERROR);
	}

	public static function setUpBeforeClass()
	{
		jimport('joomla.database.database');
		jimport('joomla.database.table');

		// First let's look to see if we have a DSN defined or in the environment variables.
		if (defined('JTEST_DATABASE_MYSQLI_DSN') || getenv('JTEST_DATABASE_MYSQLI_DSN'))
		{
			$dsn = defined('JTEST_DATABASE_MYSQLI_DSN') ? JTEST_DATABASE_MYSQLI_DSN : getenv('JTEST_DATABASE_MYSQLI_DSN');
		}
		else
		{
			return;
		}

		// First let's trim the mysql: part off the front of the DSN if it exists.
		if (strpos($dsn, 'mysql:') === 0)
		{
			$dsn = substr($dsn, 6);
		}

		// Split the DSN into its parts over semicolons.
		$parts = explode(';', $dsn);

		// Parse each part and populate the options array.
		foreach ($parts as $part)
		{
			list ($k, $v) = explode('=', $part, 2);

			switch ($k)
			{
				case 'host':
					self::$options['host'] = $v;
					break;
				case 'dbname':
					self::$options['database'] = $v;
					break;
				case 'user':
					self::$options['user'] = $v;
					break;
				case 'pass':
					self::$options['password'] = $v;
					break;
			}
		}

		if (!is_object(self::$dbo))
		{
			try
			{
				if (class_exists('JDatabaseDriver'))
				{
					self::$dbo = JDatabaseDriver::getInstance(self::$options);
				}
				else
				{
					self::$dbo = JDatabase::getInstance(self::$options);
				}
			}
			catch (Exception $e)
			{
				self::$dbo = null;
			}
		}

		self::$database = JFactory::$database;
		JFactory::$database = self::$dbo;
	}

	public static function tearDownAfterClass()
	{
		//JFactory::$database = self::$database;
	}

	/**
	 * Sets the JError error handlers.
	 *
	 * @param	array	araay of values and options to set the handlers
	 *
	 * @return	void
	 */
	protected function setErrorHandlers($errorHandlers)
	{
		$mode = null;
		$options = null;

		foreach ($errorHandlers as $type => $params) {
			$mode = $params['mode'];
			if (isset ($params['options'])) {
				JError :: setErrorHandling($type, $mode, $params['options']);
			} else {
				JError :: setErrorHandling($type, $mode);
			}
		}
	}

	/**
	 * Sets the JError error handlers to callback mode and points them at the test
	 * logging method.
	 *
	 * @return	void
	 */
	protected function setErrorCallback($testName)
	{
		$callbackHandlers = array (
			E_NOTICE => array (
				'mode' => 'callback',
				'options' => array (
					$testName,
					'errorCallback'
				)
			),
			E_WARNING => array (
				'mode' => 'callback',
				'options' => array (
					$testName,
					'errorCallback'
				)
			),
			E_ERROR => array (
				'mode' => 'callback',
				'options' => array (
					$testName,
					'errorCallback'
				)
			),

		);
		$this->setErrorHandlers($callbackHandlers);
	}

	/**
	 * Receives the callback from JError and logs the required error information for the test.
	 *
	 * @param	JException	The JException object from JError
	 *
	 * @return	bool	To not continue with JError processing
	 */
	static function errorCallback($error)
	{
		return false;
	}

	/**
	 * Saves the Factory pointers
	 *
	 * @return void
	 */
	protected function saveFactoryState()
	{
		$this->savedFactoryState['application'] = JFactory :: $application;
		$this->savedFactoryState['config'] = JFactory :: $config;
		$this->savedFactoryState['session'] = JFactory :: $session;
		$this->savedFactoryState['language'] = JFactory :: $language;
		$this->savedFactoryState['document'] = JFactory :: $document;
		$this->savedFactoryState['acl'] = JFactory :: $acl;
		//$this->savedFactoryState['database'] = JFactory::$database;
		$this->savedFactoryState['mailer'] = JFactory :: $mailer;
		$this->savedFactoryState['shconfig'] = SHFactory::$config;
		$this->savedFactoryState['shdispatcher'] = SHFactory::$dispatcher;
	}

	/**
	 * Sets the Factory pointers
	 *
	 * @return void
	 */
	protected function restoreFactoryState()
	{
		JFactory :: $application = $this->savedFactoryState['application'];
		JFactory :: $config = $this->savedFactoryState['config'];
		JFactory :: $session = $this->savedFactoryState['session'];
		JFactory :: $language = $this->savedFactoryState['language'];
		JFactory :: $document = $this->savedFactoryState['document'];
		JFactory :: $acl = $this->savedFactoryState['acl'];
		//JFactory::$database = $this->savedFactoryState['database'];
		JFactory :: $mailer = $this->savedFactoryState['mailer'];
		SHFactory::$config = $this->savedFactoryState['shconfig'];
		SHFactory::$dispatcher = $this->savedFactoryState['shdispatcher'];
	}

	/**
	 * Sets the connection to the database
	 *
	 * @return connection
	 */
	protected function getConnection()
	{
		$dsn = 'mysql:host=' . self::$options['host'] . ';dbname=' . self::$options['database'];

		$pdo = new PDO($dsn, self::$options['user'], self::$options['password']);

		// Try to load the Test Schema
		$pdo->exec(file_get_contents(JPATH_TESTS . '/schema/shplatform.sql'));
		$pdo->exec(file_get_contents(JPATH_TESTS . '/schema/shldap.sql'));

		return $this->createDefaultDBConnection($pdo, self::$options['database']);
	}
	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return xml dataset
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(JPATH_TESTS . '/stubs/test.xml');
	}
}
