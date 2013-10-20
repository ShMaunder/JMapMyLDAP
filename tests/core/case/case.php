<?php

/**
 * Abstract test case class for standard testing.
 *
 * @package  Joomla.Test
 * @since    12.1
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{
	protected $factoryState = array();

	public function setUp()
	{
		// Fake the application (most tests do not rely on this)
		JFactory::$application = $this->getMockApplication();

		parent::setUp();
	}

	/**
	 * Gets a mock application object.
	 *
	 * @return  JApplication
	 *
	 * @since   12.1
	 */
	public function getMockApplication()
	{
		// Attempt to load the real class first.
		class_exists('JApplication');

		return TestMockApplication::create($this);
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
		$this->savedFactoryState['database'] = JFactory::$database;
		$this->savedFactoryState['mailer'] = JFactory :: $mailer;
		$this->savedFactoryState['shconfig'] = SHFactory::$config;
		$this->savedFactoryState['shdispatcher'] = SHFactory::$dispatcher;
	}

	/**
	 * Restores the Factory pointers
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
		JFactory::$database = $this->savedFactoryState['database'];
		JFactory :: $mailer = $this->savedFactoryState['mailer'];
		SHFactory::$config = $this->savedFactoryState['shconfig'];
		SHFactory::$dispatcher = $this->savedFactoryState['shdispatcher'];
	}
}
