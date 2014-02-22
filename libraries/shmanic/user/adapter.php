<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Abstract user adapter base class.
 *
 * @package     Shmanic.Libraries
 * @subpackage  User
 * @since       2.1
 */
abstract class SHUserAdapter extends SHAdapter implements SHUserInterface
{
	/**
	 * Defines adapter type as user based.
	 *
	 * @var    string
	 * @since  2.1
	 */
	const TYPE = self::TYPE_USER;

	/**
	 * Username for user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $username = null;

	/**
	 * Password for user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $password = null;

	/**
	 * Domain for user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $domain = null;

	/**
	 * Holds wether the user is new.
	 *
	 * @var    Boolean
	 * @since  2.0
	 */
	protected $isNew = false;

	/**
	 * Class constructor.
	 *
	 * @param   array  $credentials  User credentials to use.
	 * @param   mixed  $config       Configuration options for driver.
	 * @param   array  $options      Extra options such as isNew.
	 *
	 * @since   2.0
	 */
	public function __construct(array $credentials, $config = null, array $options = array())
	{
		$this->username = JArrayHelper::getValue($credentials, 'username');
		$this->password = JArrayHelper::getValue($credentials, 'password');
		$this->isNew = JArrayHelper::getValue($options, 'isNew', false, 'boolean');

		if (isset($credentials['domain']))
		{
			$this->domain = (string) preg_replace('/[^A-Z0-9_\.-\s]/i', '', $credentials['domain']);
			$this->domain = ltrim($this->domain, '.');
		}
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the user adapter object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
		}

		return parent::__get($name);
	}

	/**
	 * Returns the domain or the configuration ID used for this specific user.
	 *
	 * @return  string  Domain or Configuration ID.
	 *
	 * @since   2.0
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Returns the name of this adapter.
	 *
	 * @param   string  $name  An optional string to compare against the adapter name.
	 *
	 * @return  string|false  Adapter name or False on non-matching parameter.
	 *
	 * @since   2.0
	 * @deprecated  [2.1] Use SHUserAdapterLdap::getName instead
	 */
	public static function getType($name = null)
	{
		return parent::getName($name);
	}
}
