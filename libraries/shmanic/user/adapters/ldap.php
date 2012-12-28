<?php

defined('JPATH_PLATFORM') or die;

SHImport('ldap');

class SHUserAdaptersLdap implements SHUserAdapter
{
	/**
	 * Ldap client library.
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
	 * Ldap username for Ldap user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $username = null;

	/**
	 * Ldap password for Ldap user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $password = null;

	/**
	 * Ldap domain for Ldap user.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $domain = null;

	/**
	 * Ldap attributes for this user (cached).
	 *
	 * @var    array
	 * @since  2.0
	 */
	private $_attributes = array();

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
	 * Log errors.
	 *
	 * @var    Boolean
	 * @since  2.0
	 */
	protected $logging = true;

	/**
	 * Class constructor.
	 *
	 * @param   array  $credentials  Ldap credentials to use for this object (this is not a proxy user).
	 * @param   mixed  $config       Ldap configuration options such as host, proxy user and core attributes.
	 * @param   array  $options      Extra options such as logging.
	 *
	 * @since   2.0
	 */
	public function __construct(array $credentials, $config = null, array $options = array())
	{
		$this->username = JArrayHelper::getValue($credentials, 'username');
		$this->password = JArrayHelper::getValue($credentials, 'password');

		// TODO: filter domain a bit
		$this->domain = JArrayHelper::getValue($credentials, 'domain');

		$this->logging = JArrayHelper::getValue($options, 'logging', true, 'boolean');

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
			case 'client':
				return $this->$name;
				break;

			case 'ldap':
				return $this->client;
				break;

			case 'type':
				return 'ldap';
				break;

			case 'isLdapCompatible':
				return true;
				break;
		}

		return null;
	}

	// @throws  SHLdapException
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
					$this->client->getUserDN($this->username, $this->password, true);
				}

				// Dn has already been discovered so lets return it
				return $this->_dn;
			}

			/*
			 * If the Ldap parameter override has been set then directly instantiate
			 * the Ldap library otherwise use pre-configured platform configurations
			 * through the Ldap library.
			 */
			if (!is_null($this->_config))
			{
				$this->client = new SHLdap($this->_config);
				$this->client->connect();
				$this->_dn = $this->client->getUserDN($this->username, $this->password, $authenticate);
			}
			else
			{
				$this->client = SHLdap::getInstance(
					$this->domain, array(
						'username' => $this->username,
						'password' => $this->password,
						'authenticate' => ($authenticate ? SHLdap::AUTH_USER : SHLdap::AUTH_NONE))
				);

				$this->_dn = $this->client->lastUserDn;
			}

			// Emulate dn as an attribute
			$this->_attributes['dn'] = $this->_dn;
		}
		catch (Exception $e)
		{
			// Save the exception for later if required and re-throw
			$this->_dn = $e;

			// If logging is enabled then do it
			!$this->logging ? : SHLog::add($e, 0, JLog::ERROR, 'ldap');

			throw $e;
		}

		return $this->_dn;

	}

	// @throws  SHLdapException
	public function getAttributes($input = null, $null = false)
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
			$inputFilled = array_fill_keys($needToFind, null);

			/*
			 * Combines the current cached attributes with the input attributes with null values.
			 * This will stop the input values from being re-queried on another method call even
			 * if they don't exist.
			 */
			$this->_attributes = (array_merge($inputFilled, $this->_attributes));

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
				// Insert the fake email by replacing the username placeholder with the username from ldap
				$email = str_replace(SHLdap::USERNAME_REPLACE, $this->_attributes[$this->client->ldap_uid][0], $this->client->ldap_email);
				$this->_attributes[$this->client->ldap_email] = array($email);
			}

			if (!SHLdapHelper::triggerEvent(
				'onLdapAfterRead', array(&$this, &$this->_attributes, array('dn' => $this->_dn, 'source' => __METHOD__))
			))
			{
				// Cancelled login due to plug-in - should this really be thrown though?
				throw new SHLdapException(null, 10342, JText::_('LIB_SHLDAP_ERR_10342'));
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

		// Returns only the specified inputs unless all attributes are wanted
		return is_null($input) ? $return : array_replace($inputFilled, array_intersect_key($return, $inputFilled));

	}

	public function getUid($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
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

	public function getFullname($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
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

	public function getEmail($key = false, $default = null)
	{
		if ($key)
		{
			// Only return the key id
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

	public function updateCredential($password = null, $options = array())
	{
		if (!is_null($password))
		{
			$this->password = $password;
		}
	}

	/**
	 * Set changes to the attributes within an Ldap distinguished name object.
	 * This method compares the current attribute values against a new changed
	 * set of attribute values and commits the differences.
	 *
	 * @param   array  $attributes  An array of the new/changed attributes for the object.
	 *
	 * @return  boolean  True on success or False on error.
	 *
	 * @since   2.0
	 */
	public function setAttributes(array $attributes)
	{
		if ($this->_dn instanceof Exception)
		{
			// Do not retry. Ldap configuration or user has problems.
			throw $this->_dn;
		}

		if (!count($attributes))
		{
			// There is nothing to change
			return false;
		}

		// If the proxy write is disabled then we should just try to authenticate now
		if (!$this->client->proxyWrite)
		{
			$this->getId(true);
		}

		// Get the current attributes
		$current = $this->getAttributes(array_keys($attributes), false);

		$deleteEntries 		= array();
		$addEntries 		= array();
		$replaceEntries		= array();

		foreach ($attributes as $key => $value)
		{
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

						// TODO: Investigate why I reversed this?? Makes no sense.
						//$addEntries[$key] = array_reverse($new);

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

				switch ($return)
				{
					case 1:
						$replaceEntries[$key] = $value;
						break;

					case 2:
						$addEntries[$key] = $value;
						break;

					case 3:
						$deleteEntries[$key] = array();
						break;

				}
			}
		}

		// We can now commit the changes to the LDAP server for this DN (order MATTERS!).
		$operations	= array('delete' => $deleteEntries, 'add' => $addEntries, 'replace' => $replaceEntries);
		$results 	= array();

		// Check whether we need to be binded as proxy to write to ldap
		if ($this->client->proxyWrite && $this->client->bindStatus !== SHLdap::AUTH_PROXY)
		{
			if (!$this->client->proxyBind())
			{
				// Failed to map as a proxy user
				return false;
			}
		}

		foreach ($operations as $operation => $commit)
		{
			// Check there are some attributes to process for this commit
			if (count($commit))
			{
				$method = "{$operation}Attributes";

				try
				{
					// Commit the Ldap attribute operating
					$this->client->$method($this->_dn, $commit);

					$priority = JLog::INFO;

					// Change the attribute field for this commit
					$this->_attributes = array_merge($this->_attributes, $commit);
				}
				catch (Exception $e)
				{
					// An error happened trying to commit the change
					$priority = JLog::ERROR;
				}

				// Log for audit
				SHLog::add(
					JText::sprintf(
						'LDAP %1$s Attribute (%2$s): %3$s',
						$operation,
						$this->_dn,
						var_export($commit, true)
					), 0, $priority, 'ldap'
				);

				// Add the result to the results array
				$results[] = ($priority === JLog::INFO ? true : false);
			}
		}

		// Check if any of the operations failed
		if (!in_array(false, $results, true))
		{
			return true;
		}

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

}
