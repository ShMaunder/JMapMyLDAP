<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  User.Adapter
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * Implementation of an LDAP user adapter
 *
 * @package     Shmanic.Libraries
 * @subpackage  User.Adapter
 * @since       2.0
 */
class SHUserAdapterLdap extends SHUserAdapter
{
	/**
	 * Name of this adapter implementation.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const NAME = 'LDAP';

	/**
	 * Ldap client library (also known as driver).
	 *
	 * @var    SHLdap
	 * @since  2.0
	 */
	protected $client = null;

	/**
	 * Ldap distinguished name for Ldap user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	private $_dn = null;

	/**
	 * Ldap attributes for this user (cached).
	 *
	 * @var    array
	 * @since  2.0
	 */
	private $_attributes = array();

	/**
	 * Ldap attribute proposed changes.
	 *
	 * @var    array
	 * @since  2.0
	 */
	private $_changes = array();

	/**
	 * Null user attributes (i.e. attributes which don't exist in Ldap but attempted).
	 *
	 * @var    array
	 * @since  2.0
	 */
	private $_nullAttributes = array();

	/**
	 * Use Ldap plugins to discover attributes.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	private $_usePlugins = true;

	/**
	 * Ldap object configuration array.
	 *
	 * @var    array
	 * @since  2.0
	 */
	private $_config = null;

	/**
	 * User parameters from LDAP client.
	 *
	 * @var    array
	 * @since  2.1
	 */
	private $_userParams = array();

	/**
	 * Class constructor.
	 *
	 * @param   array  $credentials  Ldap credentials to use for this object (this is not a proxy user).
	 * @param   mixed  $config       Ldap configuration options such as host, proxy user and core attributes.
	 * @param   array  $options      Extra options such as isNew.
	 *
	 * @since   2.0
	 */
	public function __construct(array $credentials, $config = null, array $options = array())
	{
		parent::__construct($credentials, $config, $options);

		// Register a callback for validating LDAP parameters
		SHUtilValidate::getInstance()->register(__CLASS__ . '::validate');

		if (is_array($config) && count($config))
		{
			// Check if Ldap plugins should be disabled when collecting attributes later
			if (isset($config['disable_use_of_plugins']))
			{
				$this->_usePlugins = false;
				unset($config['disable_use_of_plugins']);
			}

			// Override the Ldap parameters with this later on
			$this->_config = $config;
		}

		// If the user is new then the user creation script needs to provide a dn for the new object
		if ($this->isNew)
		{
			$this->_dn = JArrayHelper::getValue($credentials, 'dn');

			/*
			 * If the Ldap parameter override has been set then directly instantiate
			 * the Ldap library otherwise use pre-configured platform configurations
			 * through the Ldap library.
			 */
			$client = SHFactory::getLdapClient($this->domain, $this->_config);
			$this->client = $client[0];
			$this->client->connect();
			$this->client->proxyBind();

			// We need to check that this ldap client config has the required user based parameters
			$this->_userParams = (array) $this->client->userParams;

			// Check whether the user already exists
			if ($this->_checkUserExists())
			{
				$this->state = self::STATE_EXISTS;
				throw new RuntimeException(JText::sprintf('LIB_SHUSERADAPTERSLDAP_ERR_10909', $this->username), 10909);
			}

			// Emulate dn as an attribute
			$this->_attributes['dn'] = array($this->_dn);

			$this->state = self::STATE_NEW;
		}
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the ldap adapter object.
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
			/*
			 * These SHLdap variables are deprecated since 2.1.
			 * Use SHFactory::getLdapClient instead.
			 */
			case 'client':
			case 'usersource':
			case 'driver':
			case 'ldap':
				return $this->client;
				break;
		}

		return parent::__get($name);
	}

	/**
	 * Gets the Ldap user's distinguished name and optionally authenticate with the password supplied
	 * depending on the parameters specified.
	 *
	 * @param   boolean  $authenticate  True to authenticate with password.
	 *
	 * @return  string  Distinguished name of user.
	 *
	 * @since   2.0
	 * @throws  Exception
	 * @throws  SHLdapException
	 * @throws  SHExceptionInvaliduser
	 * @throws  SHExceptionStacked        User or configuration issues (may not be important)
	 */
	public function getId($authenticate)
	{
		try
		{
			if ($this->_dn instanceof Exception)
			{
				// Do not retry. Ldap configuration or user has problems.
				throw $this->_dn;
			}
			elseif (!is_null($this->_dn))
			{
				// Check if this user should be authenticated
				if ($authenticate && $this->client->bindStatus !== SHLdap::AUTH_USER)
				{
					// Bind with the user now
					$this->_getDn(true);
				}

				// Dn has already been discovered so lets return it
				return $this->_dn;
			}

			/*
			 * If the Ldap parameter override has been set then directly instantiate
			 * the Ldap library otherwise use pre-configured platform configurations
			 * through the Ldap library.
			 */
			$clients = SHFactory::getLdapClient($this->domain, $this->_config);

			// Keep a record of any exceptions called
			$errors = array();

			// We have to get the correct LDAP client for this user
			foreach ($clients as $client)
			{
				if (!$client->isConnected())
				{
					// Start the connection procedure (throws an error if it fails)
					$client->connect();
				}

				try
				{
					$this->client = $client;

					// We need to check that this ldap client config has the required user based parameters
					$this->_userParams = (array) $this->client->userParams;

					// If a DN is returned, then this user is successfully authenticated/authorised
					$this->_dn = $this->_getDn($authenticate);

					// Emulate dn as an attribute
					$this->_attributes['dn'] = array($this->_dn);

					$this->state = self::STATE_EXISTS;

					return $this->_dn;
				}
				catch (Exception $e)
				{
					// Add the error to the stack
					$errors[] = $e;
					$this->client = null;
				}
			}

			// Failed to find any configs to match
			if (count($errors) > 1)
			{
				// More than one config caused issues, use the stacked exception
				throw new SHExceptionStacked(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10915'), 10915, $errors);
			}
			elseif (count($errors > 0))
			{
				// Just rethrow the one exception
				throw $errors[0];
			}
		}
		catch (Exception $e)
		{
			// Save the exception for later if required and re-throw
			$this->_dn = $e;

			throw $e;
		}

		return $this->_dn;
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
		if (is_null($this->domain))
		{
			// Lets pull the domain from the SHLdap object
			$this->getId(false);
			$this->domain = $this->client->domain;
		}

		return parent::getDomain();
	}

	/**
	 * Return specified user attributes from LDAP.
	 *
	 * @param   string|array  $input    Optional string or array of attributes to return.
	 * @param   boolean       $null     Include null or non existent values.
	 * @param   boolean       $changes  Use the attribute changes (before change commit).
	 *
	 * @return  mixed  Ldap attribute results.
	 *
	 * @since   2.0
	 * @throws  SHLdapException
	 */
	public function getAttributes($input = null, $null = false, $changes = false)
	{
		if (is_null($this->_dn))
		{
			$this->getId(false);
		}
		elseif ($this->_dn instanceof Exception)
		{
			// Do not retry. Ldap configuration or user has problems.
			throw $this->_dn;
		}

		$needToFind = array();
		$inputFilled = array();

		if (!is_null($input))
		{
			// Have to make sure that unless its null then its in an array
			$input = is_string($input) ? array($input) : $input;

			$inputFilled = array_fill_keys($input, null);

			// This array is what we must find (i.e. not in the cached variable)
			$needToFind = (array_keys(array_diff_key($inputFilled, $this->_attributes)));

			/*
			 * Combines the current cached attributes with the input attributes with null values.
			 * This will stop the input values from being re-queried on another method call even
			 * if they don't exist.
			 */
			$this->_attributes = (array_merge($inputFilled, $this->_attributes));
		}

		/*
		 * We use the "plugin get attributes" method for efficiency purposes. On the
		 * first execution of this method, we attempt to gather Ldap user attributes
		 * that are required from this call in addition to what the Ldap plugins require.
		 *
		 * This means we should only have to call for the user attributes once from Ldap.
		 */
		if ($this->_usePlugins)
		{
			// Only run the sequence once
			$this->_usePlugins = false;

			/*
			 * -- Get the Ldap user attributes via plugins --
			 * This section will get an array of user detail attributes for the user
			 * using Ldap plugins to help with discovery of required Ldap attributes.
			 */
			$extras = SHFactory::getDispatcher('ldap')->trigger(
				'onLdapBeforeRead', array(&$this, array('dn' => $this->_dn, 'source' => __METHOD__))
			);

			// For each of the LDAP plug-ins returned, merge their extra attributes.
			foreach ($extras as $extra)
			{
				$needToFind = array_merge($needToFind, $extra);
			}

			// Add both of the uid and fullname to the set of attributes to get.
			$needToFind[] = $this->client->ldap_fullname;
			$needToFind[] = $this->client->ldap_uid;

			// Check for a fake email
			$fakeEmail = (strpos($this->client->ldap_email, (SHLdap::USERNAME_REPLACE)) !== false) ? true : false;

			// Add the email attribute only if not a fake email is supplied.
			if (!$fakeEmail)
			{
				$needToFind[] = $this->client->ldap_email;
			}

			// Re-order array to ensure an LDAP read is successful and no duplicates exist.
			$needToFind = array_values(array_unique($needToFind));

			// Swap the attribute names to array keys ready for the result
			$filled = array_fill_keys($needToFind, null);

			/*
			 * Combines the current cached attributes with the input attributes with null values.
			 * This will stop the input values from being re-queried on another method call even
			 * if they don't exist.
			 */
			$this->_attributes = (array_merge($filled, $this->_attributes));

			// Get Ldap user attributes
			$result	= $this->client->read($this->_dn, null, $needToFind);

			if ($result->countEntries())
			{
				// Merge the extra attributes to the cache ready for returning
				$this->_attributes = array_replace($this->_attributes, array_intersect_key($result->getEntry(0), $this->_attributes));
			}

			/*
			 * Save any attributes that weren't found in Ldap and then make it unique
			 * so theres no duplicates in the null attributes list.
			 */
			$unreturnedVals = array_diff($needToFind, array_keys($result->getEntry(0, array())));
			$this->_nullAttributes = array_merge(array_diff($unreturnedVals, $this->_nullAttributes), $this->_nullAttributes);

			if ($fakeEmail)
			{
				// Inject the fake email by replacing the username placeholder with the username from ldap
				$email = str_replace(SHLdap::USERNAME_REPLACE, $this->_attributes[$this->client->ldap_uid][0], $this->client->ldap_email);
				$this->_attributes[$this->client->ldap_email] = array($email);

				// As the last instruction from the fakeEmail condition added email to null, lets remove it
				if (($index = array_search($this->client->ldap_email, $this->_nullAttributes)) !== false)
				{
					unset ($this->_nullAttributes[$index]);
				}
			}

			if (SHLdapHelper::triggerEvent(
				'onLdapAfterRead', array(&$this, &$this->_attributes, array('dn' => $this->_dn, 'source' => __METHOD__))
			) === false)
			{
				// Cancelled login due to plug-in
				throw new RuntimeException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10912'), 10912);
			}

			// Blank need to find as there isn't anything more need finding
			$needToFind = array();
		}

		// Check if extra attributes are required
		if (count($needToFind))
		{
			$result = $this->client->read($this->_dn, null, $needToFind);

			if ($result->countEntries())
			{
				// Merge the extra attributes to the cache ready for returning
				$this->_attributes = array_replace($this->_attributes, array_intersect_key($result->getEntry(0), $this->_attributes));
			}

			/*
			 * Save any attributes that weren't found in Ldap and then make it unique
			 * so theres no duplicates in the null attributes list.
			 */
			$unreturnedVals = array_diff($needToFind, array_keys($result->getEntry(0, array())));
			$this->_nullAttributes = array_merge(array_diff($unreturnedVals, $this->_nullAttributes), $this->_nullAttributes);
		}
		else
		{
			// If there are no attributes then get them all from LDAP
			if (!count($this->_attributes))
			{
				$this->_attributes = $this->client->read($this->_dn, null)->getEntry(0, array());
			}
		}

		$return = $this->_attributes;

		// Remove null values from the attributes if we dont want them
		if (!$null)
		{
			$return = array_diff_key($this->_attributes, array_flip($this->_nullAttributes));
			$inputFilled = array_diff_key($inputFilled, array_flip($this->_nullAttributes));
		}

		// Include staged changes to the attributes
		$return = $changes ? array_merge($return, $this->_changes) : $return;

		// Returns only the specified inputs unless all attributes are wanted
		return is_null($input) ? $return : array_replace($inputFilled, array_intersect_key($return, $inputFilled));
	}

	/**
	 * Return the users unique identifier from Ldap.
	 *
	 * @param   boolean  $key      If true returns the key of the UID instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getUid($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
			$this->getId(false);

			return $this->client->keyUid;
		}

		// Find the Ldap attribute uid key
		$key = $this->client->keyUid;

		if ($value = $this->getAttributes($key))
		{
			if (isset($value[$key][0]))
			{
				// Uid (username) found so lets return it
				return $value[$key][0];
			}
		}

		return $default;
	}

	/**
	 * Return the users full name from Ldap.
	 *
	 * @param   boolean  $key      If true returns the key of the full name instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getFullname($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
			$this->getId(false);

			return $this->client->keyName;
		}

		// Find the Ldap attribute name key
		$key = $this->client->keyName;

		if ($value = $this->getAttributes($key))
		{
			if (isset($value[$key][0]))
			{
				// Fullname found so lets return it
				return $value[$key][0];
			}
		}

		return $default;
	}

	/**
	 * Return the users email from Ldap.
	 *
	 * @param   boolean  $key      If true returns the key of the Email instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getEmail($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
			$this->getId(false);

			return $this->client->keyEmail;
		}

		// Find the Ldap attribute email key
		$key = $this->client->keyEmail;

		if ($value = $this->getAttributes($key))
		{
			if (isset($value[$key][0]))
			{
				// Email found so lets return it
				return $value[$key][0];
			}
		}

		return $default;
	}

	/**
	 * Return the users password from Ldap.
	 *
	 * @param   boolean  $key      If true returns the key of the password instead of value.
	 * @param   mixed    $default  The default value.
	 *
	 * @return  mixed  Either the Key, Value or Default value.
	 *
	 * @since   2.0
	 */
	public function getPassword($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
			$this->getId(false);

			return $this->client->keyPassword;
		}

		// Find the Ldap attribute password key
		$key = $this->client->keyPassword;

		if ($value = $this->getAttributes($key))
		{
			if (isset($value[$key][0]))
			{
				// Password found so lets return it
				return $value[$key][0];
			}
		}

		return $default;
	}

	/**
	 * Sets the users password.
	 *
	 * @param   string  $new           New password.
	 * @param   string  $old           Current password.
	 * @param   string  $authenticate  Authenticate the old password before setting new.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 */
	public function setPassword($new, $old = null, $authenticate = false)
	{
		if (is_null($this->_dn))
		{
			$this->getId($authenticate);
		}
		elseif ($this->_dn instanceof Exception)
		{
			// Do not retry. Ldap configuration or user has problems.
			throw $this->_dn;
		}

		$hash = strtolower($this->client->passwordHash);
		$key = $this->getPassword(true);

		// Check if we need to authenticate and if so then do it
		if ($authenticate)
		{
			if (empty($old))
			{
				throw new InvalidArgumentException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10917', 10917));
			}

			if (!$this->client->bind($this->_dn, $old))
			{
				// Incorrect old password
				throw new InvalidArgumentException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10918', 10918));
			}
		}

		$password = $this->_genPassword($new);

		// Commit the Ldap password operation
		$this->client->replaceAttributes($this->_dn, array($key => $password));

		// Update the password inside this adapter
		$this->updateCredential($new);

		return true;
	}

	/**
	 * Generates the correct password string for pushing to LDAP.
	 *
	 * @param   string  $password  Plain text password
	 *
	 * @return  string  Correctly encoded password for LDAP.
	 *
	 * @since   2.0
	 */
	private function _genPassword($password)
	{
		$hash = strtolower($this->client->passwordHash);
		$key = $this->getPassword(true);

		if ($hash === 'unicode')
		{
			// Active Directory Unicode
			return preg_replace('/./', '$0' . "\000", "\"{$password}\"");
		}
		else
		{
			// Standard Joomla hash supported
			return JUserHelper::getCryptedPassword(
				$password, $this->client->passwordSalt, $hash, $this->client->passwordPrefix
			);
		}
	}

	/**
	 * Updates the adapters stored password (changes only the adapter's internal password variable).
	 *
	 * @param   string  $password  New password to update.
	 * @param   array   $options   Optional array of options.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function updateCredential($password = null, $options = array())
	{
		if (!is_null($password))
		{
			$this->password = $password;

			if ($this->_dn instanceof Exception)
			{
				// Remove any exceptions in the DN so it can be retried on getId
				$this->_dn = null;
			}
		}
	}

	/**
	 * Sets new attributes for the user but doesnt commit to the driver.
	 *
	 * @param   array  $attributes  An array of the new/changed attributes for the object.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function setAttributes(array $attributes)
	{
		if (!empty($attributes))
		{
			$this->_changes = array_merge($this->_changes, $attributes);
		}
	}

	/**
	 * Set changes to the attributes within an Ldap distinguished name object.
	 * This method compares the current attribute values against a new changed
	 * set of attribute values and commits the differences.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  array  Array of commits, exceptions and status.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	public function commitChanges($options = array())
	{
		if ($this->_dn instanceof Exception)
		{
			// Do not retry. Ldap configuration or user has problems.
			throw $this->_dn;
		}

		if ($this->isNew)
		{
			// We only want to create the user
			return $this->create($options);
		}

		if (empty($this->_changes))
		{
			// There is nothing to commit
			return array('status' => true, 'nochanges' => true);
		}

		// If the user write is enabled then we should just try to authenticate now
		if ($userWrite = JArrayHelper::getValue($options, 'userWrite', false, 'boolean'))
		{
			$this->getId(true);
		}

		// Get the current attributes
		$current = $this->getAttributes(array_keys($this->_changes), false);

		// Get the UID/username of the user
		$uid = $this->getUid(false);

		$deleteEntries 		= array();
		$addEntries 		= array();
		$replaceEntries		= array();

		// Loop around all changes
		foreach ($this->_changes as $key => $value)
		{
			if ($key === 'dn')
			{
				continue;
			}

			$return = 0;

			// Check this attribute for multiple values
			if (is_array($value))
			{
				/* This is a multiple value attriute and to preserve
				 * order we must replace the whole thing if changes
				 * are required.
				 */
				$modification = false;
				$new = array();
				$count = 0;

				for ($i = 0; $i < count($value); ++$i)
				{
					if ($return = self::_checkFieldHelper($current, $key, $count, $value[$i]))
					{
						$modification = true;
					}

					if ($return !== 3 && $value[$i])
					{
						// We don't want to save deletes
						$new[] = $value[$i];
						++$count;
					}
				}

				if ($modification)
				{
					// We want to delete it first
					$deleteEntries[$key] = array();

					if (count($new))
					{
						// Now lets re-add them
						$addEntries[$key] = $new;
					}
				}
			}
			else
			{
				/* This is a single value attribute and we now need to
				 * determine if this needs to be ignored, added,
				 * modified or deleted.
				 */
				$return = self::_checkFieldHelper($current, $key, 0, $value);

				// Check if this is a password attribute as the replace needs to be forced
				if ($key === $this->getPassword(true))
				{
					$replaceEntries[$key] = array($value);
				}
				else
				{
					switch ($return)
					{
						case 1:
							$replaceEntries[$key] = array($value);
							break;

						case 2:
							$addEntries[$key] = array($value);
							break;

						case 3:
							$deleteEntries[$key] = array();
							break;
					}
				}
			}
		}

		// We can now commit the changes to the LDAP server for this DN (order MATTERS!).
		$operations	= array('delete' => $deleteEntries, 'add' => $addEntries, 'replace' => $replaceEntries);

		// Check whether we need to be binded as proxy to write to ldap
		if (!$userWrite && $this->client->bindStatus !== SHLdap::AUTH_PROXY)
		{
			if (!$this->client->proxyBind())
			{
				// Failed to map as a proxy user
				throw new RuntimeException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10901'), 10901);
			}
		}

		$results = array('status' => true);
		$commits = array();

		if (isset($this->_changes['dn']) && ($this->_changes['dn'] != $this->_dn))
		{
			// TODO: Need to rename the DN using SHLdap::rename()
			throw new InvalidArgumentException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10922'), 10922);
		}

		foreach ($operations as $operation => $commit)
		{
			// Remove password from this commit
			unset ($commit[$this->getPassword(true)]);

			$method = "{$operation}Attributes";

			// Check there are some attributes to process for this commit
			if (count($commit))
			{
				try
				{
					// Commit the Ldap attribute operation
					$this->client->$method($this->_dn, $commit);

					// Successful commit so say so
					$commits[$operation] = array(
						'status' => JLog::INFO,
						'info' => JText::sprintf(
							'LIB_SHUSERADAPTERSLDAP_INFO_10924',
							$operation,
							$uid,
							preg_replace('/\s+/', ' ', var_export($commit, true))
						)
					);

					// Change the attribute field for this commit
					$this->_attributes = array_merge($this->_attributes, $commit);

					if ($operation == 'add')
					{
						// Add operation means we need to remove attribute keys from nullAttributes
						foreach (array_keys($commit) as $k)
						{
							if (($index = array_search($k, $this->_nullAttributes)) !== false)
							{
								unset ($this->_nullAttributes[$index]);
							}
						}
					}
					elseif ($operation == 'delete')
					{
						// Delete operation means we need to add attribute keys to nullAttributes
						foreach (array_keys($commit) as $k)
						{
							if (array_search($k, $this->_nullAttributes) === false)
							{
								$this->_nullAttributes[] = $k;
							}
						}
					}
				}
				catch (Exception $e)
				{
					// An error happened trying to commit the change so lets log it
					$commits[$operation] = array(
						'status' => JLog::ERROR,
						'info' => JText::sprintf(
							'LIB_SHUSERADAPTERSLDAP_ERR_10926',
							$operation,
							$uid,
							preg_replace('/\s+/', ' ', var_export($commit, true))
						),
						'exception' => $e
					);

					$results['status'] = false;
				}
			}
		}

		// Clear the changes even if they failed
		$this->_changes = array();

		// Save the commits for potential audit
		$results['commits'] = $commits;

		return $results;
	}

	/**
	 * Creates the user in the LDAP directory.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function create($options = array())
	{
		$this->state = self::STATE_FAILED;

		if ($this->_dn instanceof Exception)
		{
			// Do not retry. Ldap configuration or user has problems.
			throw $this->_dn;
		}

		// Ensure proxy binded
		if ($this->client->bindStatus !== SHLdap::AUTH_PROXY)
		{
			if (!$this->client->proxyBind())
			{
				// Failed to map as a proxy user
				throw new RuntimeException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10901'), 10901);
			}
		}

		// Remove the DN if exists in the attribute list
		unset($this->_changes['dn']);

		/*
		 * Automatically add in the username and password if they do not exist.
		 */
		if (!isset($this->_changes[$this->getUid(true)]))
		{
			$this->_changes[$this->getUid(true)] = array($this->username);
		}

		if (!isset($this->_changes['password']))
		{
			// Do not array the password so it can be hashed later
			$this->_changes['password'] = $this->password;
		}

		/*
		 * Replace any attributes that have been given generic keywords
		 * such as username, password and put them into the ldap attribute format.
		 */
		if (isset($this->_changes['username']) && !is_array($this->_changes['username']))
		{
			$username = $this->_changes['username'];
			unset($this->_changes['username']);
			$this->_changes[$this->getUid(true)] = array($username);
		}

		if (isset($this->_changes['email']) && !is_array($this->_changes['email']))
		{
			$email = $this->_changes['email'];
			unset($this->_changes['email']);
			$this->_changes[$this->getEmail(true)] = array($email);
		}

		if (isset($this->_changes['fullname']) && !is_array($this->_changes['fullname']))
		{
			$fullname = $this->_changes['fullname'];
			unset($this->_changes['fullname']);
			$this->_changes[$this->getFullname(true)] = array($fullname);
		}

		if (isset($this->_changes['password']) && !is_array($this->_changes['password']))
		{
			$password = $this->_changes['password'];
			unset($this->_changes['password']);
			$password = $this->_genPassword($password);
			$this->_changes[$this->getPassword(true)] = array($password);
		}

		$this->client->add($this->_dn, $this->_changes);

		$this->_changes = array();
		$this->isNew = false;

		$this->state = self::STATE_CREATED;

		return true;
	}

	/**
	 * Deletes the user from the LDAP directory.
	 *
	 * @param   array  $options  Optional array of options.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function delete($options = array())
	{
		$this->getId(false);

		// Ensure proxy binded
		if ($this->client->bindStatus !== SHLdap::AUTH_PROXY)
		{
			if (!$this->client->proxyBind())
			{
				// Failed to map as a proxy user
				throw new RuntimeException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10901'), 10901);
			}
		}

		$this->client->delete($this->_dn);

		$this->_dn = new RuntimeException(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10906'), 10906);

		return true;
	}

	/**
	 * Retrieves the groups this user is a member of.
	 *
	 * @param   mixed  $default  The default value.
	 *
	 * @return  SHGroupAdaptersLdap[]  Array of group adapters.
	 *
	 * @since   2.1
	 */
	public function getGroups($default = null)
	{
		//TODO: complete this method
		return array();
	}

	/**
	 * Adds the specified group to the user.
	 *
	 * @param   string  $id  Group name or group distinguished name.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.1
	 */
	public function addGroup($id)
	{
		//TODO: complete this method
		return true;
	}

	/**
	 * Removes the specified group from the user.
	 *
	 * @param   string  $id  Group name or group distinguished name.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.1
	 */
	public function removeGroup($id)
	{
		//TODO: complete this method
		return true;
	}

	/**
	 * Returns all available domains for this adapter type.
	 *
	 * @return  array  An array of domain names.
	 *
	 * @since   2.1
	 */
	public static function getDomains()
	{
		return SHLdapHelper::getConfigIDs();
	}

	/**
	 * Get a users Ldap distinguished name with optional bind authentication.
	 *
	 * @param   boolean  $authenticate  Attempt to authenticate the user (i.e.
	 *						bind the user with the password supplied)
	 *
	 * @return  string  User DN.
	 *
	 * @since   2.1
	 * @throws  InvalidArgumentException  Invalid argument in config related error
	 * @throws  SHLdapException           Ldap specific error.
	 * @throws  SHExceptionInvaliduser    User invalid error.
	 */
	private function _getDn($authenticate = false)
	{
		$replaced = str_replace(SHLdap::USERNAME_REPLACE, $this->username, $this->_userParams['user_query']);

		/*
		 * A basic detection check for LDAP filter.
		 * (i.e. distinguished names do not start and end with brackets).
		 */
		$useSearch = (preg_match('/(?<!\S)[\(]([\S]+)[\)](?!\S)/', $this->_userParams['user_query'])) ? true : false;

		SHLog::add(
			"Attempt to retrieve user distinguished name using '{$replaced}' " .
			($useSearch ? ' with search.' : ' with direct bind.'), 102, JLog::DEBUG, 'ldap'
		);

		// Get a array of distinguished names from either the search or direct bind methods.
		$DNs = $useSearch ? $this->_getDnBySearch() : $this->_getDnDirect();

		if (empty($DNs))
		{
			/*
			 * Cannot find the specified username. We are going to throw
			 * a special user not found error to try to split between
			 * configuration errors and invalid errors. However, this might
			 * still be a configuration error.
			 */
			throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10302'), 10302, $this->username);
		}

		// Check if we have to authenticate the distinguished name with a password
		if ($authenticate)
		{
			// Attempt to bind each distinguished name with the specified password then return it
			foreach ($DNs as $dn)
			{
				if ($this->client->bind($dn, $this->password))
				{
					// Successfully binded with this distinguished name
					SHLog::add(
						"Successfully authenticated {$this->username} with distinguished name {$dn}.",
						102, JLog::DEBUG, 'ldap'
					);

					return $dn;
				}
			}

			if ($useSearch)
			{
				// User found, but was unable to bind with the supplied password
				throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10303'), 10303, $this->username);
			}
			else
			{
				// Unable to bind directly to the given distinguished name parameters
				throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10304'), 10304, $this->username);
			}
		}
		else
		{
			$result = false;

			if ($useSearch)
			{
				/* We can be sure the distinguished name(s) exists in the Ldap
				 * directory. However, we cannot be sure if the correct
				 * distinguished name is returned for the specified user without
				 * authenticating. Therefore, we have to assume the first (and
				 * hopefully only) distinguished name is correct.
				 * If the correct configuration has been given and the Ldap
				 * directory is well organised, this will always be correct.
				 */
				$result = $DNs[0];
			}
			else
			{
				/* Unlike searching, binding directly means we cannot be sure
				 * if the distinguished name(s) exists in the Ldap directory.
				 * Therefore, lets attempt to bind with a proxy user, then Ldap
				 * read each distinguished name's entity to check if it exists.
				 * If binding with the proxy user fails, then we have no option
				 * but to assume the first distinguished name exists.
				 */
				if ($this->client->proxyBind())
				{
					foreach ($DNs as $dn)
					{
						try
						{
							$read = $this->client->read($dn, null, array('dn'));
						}
						catch (Exception $e)
						{
							// We don't need to worry about the exception too much
							SHLog::add("Failed to read direct bind without auth DN {$dn}.", 102, JLog::DEBUG, 'ldap');
							continue;
						}

						// Check if the distinguished name entity exists
						if ($read->countEntries() > 0)
						{
							// It exists so we assume this is the correct distinguished name.
							$result = $dn;
							break;
						}
					}

					if ($result === false)
					{
						// Failed to find any of the distinguished name(s) in the Ldap directory.
						throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10305'), 10305, $this->username);
					}
				}
				else
				{
					// Unable to check Ldap directory, so have to assume the first is correct
					$result = $DNs[0];
				}
			}

			SHLog::add("Using distinguished name {$result} for user {$this->username}.", 102, JLog::DEBUG, 'ldap');

			return $result;
		}
	}

	/**
	 * Get a user's dn by attempting to search for it in the directory.
	 *
	 * This method uses the query as a filter to find where the user is located in the directory
	 *
	 * @return  array  An array containing user DNs.
	 *
	 * @since   2.1
	 * @throws  InvalidArgumentException  Invalid argument in config related error
	 * @throws  SHLdapException           Ldap search error
	 */
	private function _getDnBySearch()
	{
		// Fixes special usernames and provides simple protection against ldap injections
		$username 	= SHLdapHelper::escape($this->username);
		$search 	= str_replace(SHLdap::USERNAME_REPLACE, $username, $this->_userParams['user_query']);

		// We can either use a specific user base dn or use SHLdap's default
		$baseDn = (isset($this->_userParams['user_base_dn']) && !empty($this->_userParams['user_base_dn']))
			? $this->_userParams['user_base_dn'] : null;

		// Bind using the proxy user so the user can be found in the Ldap directory.
		if (!$this->client->proxyBind())
		{
			// Failed to bind with proxy user
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10322'), 10322);
		}

		// Search the directory for the user
		$result = $this->client->search($baseDn, $search, array($this->_userParams['user_uid']));

		$return 	= array();
		$count 		= $result->countEntries();

		// Store the distinguished name for each user found
		for ($i = 0; $i < $count; ++$i)
		{
			$return[] = $result->getValue($i, 'dn', 0);
		}

		return $return;
	}

	/**
	 * Get a user's distinguished name by attempting to replace the username keyword
	 * in the query. Supports multiple distinguished names in a list.
	 *
	 * @return  array  An array containing distinguished names.
	 *
	 * @since   2.1
	 */
	private function _getDnDirect()
	{
		$return = array();

		// Fixes special usernames and provides protection against distinguished name injection
		$username = SHLdapHelper::escape($this->username, true);

		// Replace the username placeholder with the authenticating username
		$search = str_replace(SHLdap::USERNAME_REPLACE, $username, $this->_userParams['user_query']);

		// Splits each of the distinguished names into indivdual elements
		$DNs = explode(';', $search);

		// We need to find the correct distinguished name from the set of elements
		foreach ($DNs as $dn)
		{
			// Remove whitespacing from the distinguished name and check there is a length > 1
			if ($dn = trim($dn))
			{
				$return[] = $dn;
			}
		}

		return $return;
	}

	/**
	 * This method is used as a helper to the makeChanges() method. It checks
	 * whether a field/attribute is up-to-date in the Ldap directory (not live).
	 * The method returns whether it is:
	 * 0: up-to-date, no action required;
	 * 1: attribute exists, but value must be updated;
	 * 2: attribute doesnt exist, needs creating;
	 * 3: attribute exists, but is no longer required and needs deleting.
	 *
	 * @param   array    $current   The current (or old) set of attributes to compare.
	 * @param   string   $key       Key of the attribute.
	 * @param   integer  $interval  The attribute number (in case of multiple values per key).
	 * @param   string   $value     The new attribute value.
	 *
	 * @return  integer  See method description.
	 *
	 * @since   2.0
	 */
	private static function _checkFieldHelper(array $current, $key, $interval, $value)
	{
		// Check if the LDAP attribute exists
		if (array_key_exists($key, $current))
		{
			if (isset($current[$key][$interval]))
			{
				if ($current[$key][$interval] == $value)
				{
					// Same value - no need to update
					return 0;
				}

				if (is_null($value) || !$value)
				{
					// We don't want to include a blank or null value
					return 3;
				}
			}

			if (is_null($value) || !$value)
			{
				// We don't want to include a blank or null value
				return 0;
			}

			return 1;
		}
		else
		{
			if (!is_null($value) && $value)
			{
				// We need to create a new LDAP attribute
				return 2;
			}
			else
			{
				// We don't want to include a blank or null value
				return 0;
			}
		}
	}

	/**
	 * Checks whether a user exists in the LDAP directory.
	 *
	 * @return  boolean  True if user exists.
	 *
	 * @since   2.0
	 */
	private function _checkUserExists()
	{
		try
		{
			$this->_getDn(false);

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Validates the user parameters from the LDAP configuration.
	 *
	 * @param   JRegistry  &$config  LDAP configuration.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 * @throws  InvalidArgumentException  Thrown when user parameters are invalid
	 */
	public static function validate(JRegistry &$config)
	{
		try
		{
			$params = (array) json_decode($config->get('userParams', '{}'));

			if (!empty($params))
			{
				if (!isset($params['user_query']) || empty($params['user_query']))
				{
					throw new Exception(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10933'));
				}
				elseif (!isset($params['user_uid']) || empty($params['user_uid']))
				{
					throw new Exception(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10934'));
				}
				elseif (!isset($params['user_fullname']) || empty($params['user_fullname']))
				{
					throw new Exception(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10935'));
				}
				elseif (!isset($params['user_email']) || empty($params['user_email']))
				{
					throw new Exception(JText::_('LIB_SHUSERADAPTERSLDAP_ERR_10936'));
				}

				// Backwards compatibility with 2.0
				$config->set('user_qry', $params['user_query']);
				$config->set('ldap_uid', $params['user_uid']);
				$config->set('ldap_fullname', $params['user_fullname']);
				$config->set('ldap_email', $params['user_email']);
				$config->set('ldap_password', JArrayHelper::getValue($params, 'user_password'));
				$config->set('password_hash', JArrayHelper::getValue($params, 'user_password_hash'));
				$config->set('password_prefix', JArrayHelper::getValue($params, 'user_password_prefix'));
				$config->set('all_user_filter', JArrayHelper::getValue($params, 'user_mass_query'));
			}
			else
			{
				// Forward compatibility with 2.1
				$params = array(
					'user_query' => $config->get('user_qry'),
					'user_uid' => $config->get('ldap_uid'),
					'user_fullname' => $config->get('ldap_fullname'),
					'user_email' => $config->get('ldap_email'),
					'user_password' => $config->get('ldap_password'),
					'user_password_hash' => $config->get('password_hash'),
					'user_password_prefix' => $config->get('password_prefix'),
					'user_mass_query' => $config->get('all_user_filter'),
				);

				$config->set('user_params', json_encode($params));
			}
		}
		catch (Exception $e)
		{
			throw new InvalidArgumentException(
				JText::sprintf('LIB_SHUSERADAPTERSLDAP_ERR_10931', $e->getMessage(), $config->get('domain')), 10931
			);
		}
	}
}

/**
 * Deprecated class name for SHUserAdapterLdap.
 *
 * @package     Shmanic.Libraries
 * @subpackage  User.Adapter
 * @since       2.0
 *
 * @deprecated  [2.1] Use SHUserAdapterLdap instead
 */
class SHUserAdaptersLdap extends SHUserAdapterLdap
{
}
