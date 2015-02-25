<?php
/**
 * Orginally forked from the Joomla LDAP (JLDAP 11.1) for enhanced search and increased
 * functionality with partial backward compatibility in the helper file.
 *
 * PHP Version 5.3
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * An LDAP authentication and modification class for LDAP operations.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdap
{
	/**
	 * Size limit for some supported LDAP operations
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const SIZE_LIMIT = 0;

	/**
	 * Time limit for some supported LDAP operations
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const TIME_LIMIT = 0;

	/**
	 * Default filter when one is not specified
	 *
	 * @var    string
	 * @since  2.0
	 */
	const DEFAULT_FILTER = '(objectclass=*)';

	/**
	 * The placeholder for username replacement.
	 *
	 * @var    string
	 * @since  2.0
	 */
	const USERNAME_REPLACE = '[username]';

	/**
	 * Use the result object in place of an array when returning data results.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	const USE_RESULT_OBJECT = true;

	/**
	 * No authentication option.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const AUTH_NONE = 0;

	/**
	 * Authenticate as username and password.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const AUTH_USER = 1;

	/**
	 * Authenticate as proxy user.
	 *
	 * @var    integer
	 * @since  2.0
	 */
	const AUTH_PROXY = 2;

	/**
	 * Extra configuration values that have no class properties.
	 *
	 * @var    array
	 * @since  2.0
	 */
	public $extras = array();

	/**
	 * Debug stack
	 *
	 * @var    Array
	 * @since  2.0
	 */
	protected $debug = array();

	/**
	 * Optional encryption options for passwords.
	 *
	 * @var    Array
	 * @since  2.0
	 */
	protected $encryption_options = array();

	/**
	 * Use LDAP version 3
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $use_v3 = false;

	/**
	 * Negotiate TLS (encrypted communications)
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $negotiate_tls = false;

	/**
	 * Use referrals (server transfers)
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $use_referrals = false;

	/**
	 * Hostname of LDAP server (multiple values supported; see PHP documentation)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $host = null;

	/**
	 * Port of LDAP server
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $port = null;

	/**
	 * Proxy username
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $proxy_username = null;

	/**
	 * Proxy user password
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $proxy_password = null;

	/**
	 * Proxy password encrypted
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $proxy_encryption = false;

	/**
	 * Fullname attribute (e.g. fullname / name)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $ldap_fullname = null;

	/**
	 * Email attribute (e.g. mail)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $ldap_email = null;

	/**
	 * UID attribute (e.g. uid / sAMAccountName)
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $ldap_uid = null;

	/**
	 * Allow anonymous binding.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $allow_anon = false;

	/**
	 * Base DN to use for searching (e.g. dc=acme,dc=local / o=company).
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $base_dn = null;

	/**
	 * User DN or Filter Query (e.g. (sAMAccountName=[username]) / cn=[username],dc=acme,dc=local).
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $user_qry = null;

	/**
	 * The last successful user distinguished name.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $last_user_dn = null;

	/**
	 * Filter to filter by user objects.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $all_user_filter = '(objectclass=user)';

	/**
	 * Password attribute (e.g. userPassword).
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $ldap_password = null;

	/**
	 * Password hash type.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $password_hash = null;

	/**
	 * Password salt value (optional).
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $password_salt = null;

	/**
	 * Password hash prefix.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $password_prefix = false;

	/**
	 * Holds the domain ID or name.
	 *
	 * @var    string
	 * @since  2.0
	 */
	protected $domain = null;

	/**
	 * Set to the current status/level of user bind such as none, proxy or user.
	 *
	 * @var     integer
	 * @string  2.0
	 */
	protected $bind_status = self::AUTH_NONE;

	/**
	 * LDAP resource handler
	 *
	 * @var    mixed
	 * @since  1.0
	 */
	protected $resource = null;

	/**
	 * Find the correct Ldap parameters based on the authorised and configuration
	 * specified. If found then return the successful Ldap object.
	 *
	 * Note: you can use SHLdap::lastUserDn for the user DN instead of rechecking again.
	 *
	 * @param   integer|string  $id          Optional configuration record ID.
	 * @param   Array           $authorised  Optional authorisation/authentication options (authenticate, username, password).
	 * @param   JRegistry       $registry    Optional override for platform configuration registry.
	 *
	 * @return  SHLdap  An Ldap object on successful authorisation or False on error.
	 *
	 * @since   2.0
	 * @throws  InvalidArgumentException  Invalid configurations
	 * @throws  SHExceptionStacked        User or configuration issues (may not be important)
	 */
	public static function getInstance($id = null, array $authorised = array(), JRegistry $registry = null)
	{
		// Get the platform registry config from the factory if required
		$registry = is_null($registry) ? SHFactory::getConfig() : $registry;

		// Get the optional authentication/authorisation options
		$authenticate = SHUtilArrayhelper::getValue($authorised, 'authenticate', self::AUTH_NONE);
		$username = SHUtilArrayhelper::getValue($authorised, 'username', null);
		$password = SHUtilArrayhelper::getValue($authorised, 'password', null);

		// Get all the Ldap configs that are enabled and available
		$configs = SHLdapHelper::getConfig($id, $registry);

		// Check if only one configuration result was found
		if ($configs instanceof JRegistry)
		{
			// Wrap this around an array so we can use the same code below
			$configs = array($configs);
		}

		// Keep a record of any exceptions called and only log them after
		$errors = array();

		// Loop around each of the Ldap configs until one authenticates
		foreach ($configs as $config)
		{
			try
			{
				// Get a new SHLdap object
				$ldap = new SHLdap($config);

				// Check if the authenticate/authentication is successful
				if ($ldap->authenticate($authenticate, $username, $password))
				{
					// This is the correct configuration so return the new client
					return $ldap;
				}
			}
			catch (Exception $e)
			{
				// Add the error to the stack
				$errors[] = $e;
			}

			unset($ldap);
		}

		// Failed to find any configs to match
		if (count($errors) > 1)
		{
			// More than one config caused issues, use the stacked exception
			throw new SHExceptionStacked(JText::_('LIB_SHLDAP_ERR_10411'), 10411, $errors);
		}
		else
		{
			// Just rethrow the one exception
			throw $errors[0];
		}
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the ldap object.
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
			case 'ldap_fullname':
			case 'ldap_email':
			case 'ldap_uid':
			case 'last_user_dn':
			case 'all_user_filter':
			case 'base_dn':
			case 'bind_status':
			case 'ldap_password':
			case 'password_hash':
			case 'password_prefix':
			case 'password_salt':
			case 'domain':
			case 'debug':
				return $this->$name;
				break;

			case 'bindStatus':
				return $this->bind_status;
				break;

			case 'lastUserDn':
				return $this->last_user_dn;
				break;

			case 'allUserFilter':
				return $this->all_user_filter;
				break;

			case 'baseDn':
				return $this->base_dn;
				break;

			case 'passwordHash':
				return $this->password_hash;
				break;

			case 'passwordPrefix':
				return $this->password_prefix;
				break;

			case 'passwordSalt':
				return $this->password_salt;
				break;

			case 'keyName':
				return $this->ldap_fullname;
				break;

			case 'keyEmail':
				return $this->ldap_email;
				break;

			case 'keyUid':
				return $this->ldap_uid;
				break;

			case 'keyPassword':
				return $this->ldap_password;
				break;

			case 'info':
				return $this->host . ':' . $this->port;
				break;

			case 'use_search':
				// Deprecated attribute for BC
				return (preg_match('/(?<!\S)[\(]([\S]+)[\)](?!\S)/', $this->user_qry)) ? true : false;
				break;
		}

		return null;
	}

	/**
	 * Class Constructor.
	 *
	 * @param   object  $configObj  An object of configuration variables.
	 *
	 * @since   2.0
	 */
	public function __construct($configObj = null)
	{
		if (is_null($configObj))
		{
			// Parameters will need setting later
			$configArr = array();
		}
		elseif ($configObj instanceof JRegistry)
		{
			// JRegistry object needs to be converted to an array
			$configArr = $configObj->toArray();
		}
		elseif (is_array($configObj))
		{
			// The parameter was an array already
			$configArr = $configObj;
		}
		else
		{
			// Unknown format
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_990'), 990);
		}

		// Assign the configuration to their respected class properties only if they exist
		foreach ($configArr as $k => $v)
		{
			if (property_exists($this, $k))
			{
				$this->$k = $v;
			}
			else
			{
				$this->extras[$k] = $v;
			}
		}

		// Check the Ldap extension is loaded
		if (!extension_loaded('ldap'))
		{
			// Ldap extension is not loaded
			throw new RunTimeException(JText::_('LIB_SHLDAP_ERR_991'), 991);
		}

		// Reset resource & debug
		$this->resource = null;
		$this->debug = array();

		// Unencrypt the proxy user if required
		if ($this->proxy_encryption && !empty($this->proxy_password))
		{
			if (!empty($this->encryption_options))
			{
				$this->encryption_options = (array) json_decode($this->encryption_options);
			}

			// There is password encryption lets decrypt (this is only basic)
			$crypt = SHFactory::getCrypt($this->encryption_options);
			$this->proxy_password = $crypt->decrypt($this->proxy_password);
		}
	}

	/**
	 * Inform any listening loggers of the debug message and add to debug stack.
	 *
	 * @param   string  $message  String to push to stack
	 *
	 * @return  void
	 *
	 * @since  2.0
	 */
	public function addDebug($message)
	{
		// Add the debug message to any listening loggers
		SHLog::add($message, 101, JLog::DEBUG, 'ldap');

		$this->debug[] = $message;
	}

	/**
	 * Attempts to Ldap authorise/authenticate with the parameters specified.
	 *
	 * @param   integer  $authenticate  Authenticate the username and password supplied with the Ldap object.
	 * @param   string   $username      Authorisation/authentication username.
	 * @param   string   $password      Authentication password.
	 *
	 * @return  boolean  True on success or False on Proxy Failure.
	 *
	 * @since   2.0
	 * @throws  Exception               Configuration error
	 * @throws  SHLdapException         Ldap specific error
	 * @throws  SHExceptionInvaliduser  User invalid error
	 */
	public function authenticate($authenticate = self::AUTH_NONE, $username = null, $password = null)
	{
		if (!$this->isConnected())
		{
			// Start the connection procedure (throws an error if fails)
			$this->connect();
		}

		/*
		 * If no authentication is required, then check whether a username
		 * has been specified. If not, then we can just return here. However,
		 * if a username is specified then we want to authorise it instead.
		 */
		if (($authenticate === self::AUTH_NONE) && is_null($username))
		{
			return true;
		}

		// Check if we only want a proxy user.
		elseif ($authenticate === self::AUTH_PROXY)
		{
			return $this->proxyBind();
		}

		// Assume we need to authenticate or authorise the specified user.
		else
		{
			// If a DN is returned, then this user is successfully authenticated/authorised
			$dn = $this->getUserDn($username, $password, ($authenticate === self::AUTH_USER) ? true : false);
		}

		// Successfully authenticated and retrieved User DN.
		return true;
	}

	/**
	 * Attempt connection to an LDAP server and returns the result.
	 *
	 * @return  boolean  True on Success.
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException  Invalid configuration or arguments specified.
	 * @throws  SHLdapException
	 */
	public function connect()
	{
		// A host parameter must be specified to connect
		if (empty($this->host))
		{
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10001'), 10001);
		}

		// If there is a connection already, then close it before proceeding
		$this->close();

		$this->addDebug("Attempting connection to LDAP with host {$this->host}");

		/*
		 * In most cases, even if we cannot connect, we won't
		 * be able to find out until we have done our first
		 * bind! This is because it will allocate a resource
		 * whether it was able to connect to a server or not.
		 */
		$this->resource = ldap_connect($this->host, $this->port);

		if (!$this->isConnected())
		{
			// Failed to connect
			throw new SHLdapException(
				$this->getErrorCode(), 10002, JText::_('LIB_SHLDAP_ERR_10002')
			);
		}

		$this->addDebug(
			"Successfully connected to {$this->host}. Setting the following parameters:" .
			($this->use_v3 ? ' ldapV3' : null) . ($this->use_referrals ? ' Referrals' : null) .
			($this->negotiate_tls ? ' TLS.' : null)
		);

		// Attempt to configure LDAP version 3
		if ($this->use_v3)
		{
			if (!ldap_set_option($this->resource, LDAP_OPT_PROTOCOL_VERSION, 3))
			{
				// Failed to set LDAP version 3
				throw new SHLdapException($this->getErrorCode(), 10003, JText::_('LIB_SHLDAP_ERR_10003'));
			}
		}

		// Attempt to set the referrals option
		if (!ldap_set_option($this->resource, LDAP_OPT_REFERRALS, intval($this->use_referrals)))
		{
			// Failed to set referrals
			throw new SHLdapException($this->getErrorCode(), 10004, JText::_('LIB_SHLDAP_ERR_10004'));
		}

		// Attempt to configure Start TLS
		if ($this->negotiate_tls)
		{
			if (!@ldap_start_tls($this->resource))
			{
				// Failed to start TLS
				throw new SHLdapException($this->getErrorCode(), 10005, JText::_('LIB_SHLDAP_ERR_10005'));
			}
		}

		// Default the bind level to none
		$this->bind_status = self::AUTH_NONE;

		// Connecting has been successful
		$this->addDebug('Successfully connected.');

		return true;
	}

	/**
	 * Checks whether a resource is defined in the LDAP resource variable.
	 * Note: this isn't reliable as an object is created when a connection is attempted.
	 *
	 * @return  boolean  True if connected otherwise returns False.
	 *
	 * @since   2.0
	 */
	public function isConnected()
	{
		return is_resource($this->resource);
	}

	/**
	 * Close the LDAP connection.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function close()
	{
		if ($this->isConnected())
		{
			// Close the current connection to Ldap and reset the resource variable
			ldap_close($this->resource);
			$this->resource = null;
			$this->addDebug('Closed connection.');
		}

		// Default the bind level to none
		$this->bind_status = self::AUTH_NONE;
	}

	/**
	 * Checks the LDAP connection and Bind status of this Ldap object. If either
	 * are not correct, then Ldap operations wont be allowed and will throw an exception.
	 *
	 * @return  boolean  True on allowed.
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	protected function operationAllowed()
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			throw new RuntimeException(JText::_('LIB_SHLDAP_ERR_10006'), 10006);
		}

		if ($this->bind_status === self::AUTH_NONE)
		{
			// There is no binded user
			throw new RuntimeException(JText::_('LIB_SHLDAP_ERR_10007'), 10007);
		}
	}

	/**
	 * Set the allow anonymous binding flag.
	 *
	 * @param   boolean  $value  True to allow anonymous binds or False to disable
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function allowAnonymous($value = true)
	{
		$this->allow_anon = (bool) $value;
	}

	/**
	 * Binds using a connect username and password.
	 * Note: Anonymous binds are always allowed here.
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function proxyBind()
	{
		// Default the bind level to none
		$this->bind_status = self::AUTH_NONE;

		// Use direct password
		$password = $this->proxy_password;

		if (@ldap_bind($this->resource, $this->proxy_username, $password))
		{
			// Successfully binded so set the level
			$this->bind_status = self::AUTH_PROXY;
			unset($password);

			return true;
		}

		// Unsuccessful bind
		$this->addDebug('Unsuccessful proxy bind');
		unset($password);

		return false;
	}

	/**
	 * Binds to the LDAP directory and returns the operation result.
	 * Note: Anonymous bind can be disabled by passing allowAnonymous(false).
	 *
	 * @param   string  $username  Bind username (anonymous bind if left blank)
	 * @param   string  $password  Bind password (anonymous bind if left blank)
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   1.0
	 */
	public function bind($username = null, $password = null)
	{
		// Default the bind level to none
		$this->bind_status = self::AUTH_NONE;

		// Check if this is an anonymous bind attempt
		if (empty($username) || empty($password))
		{
			if (!$this->allow_anon)
			{
				// Anonymous binding disabled
				$this->addDebug("Anonymous bind rejected {$username}");

				return false;
			}

			// Anonymous bind allowed
			$this->addDebug("Anonymous bind attempted {$username}");
		}

		if (@ldap_bind($this->resource, $username, $password))
		{
			// Successfully binded so set the level
			$this->bind_status = self::AUTH_USER;

			return true;
		}

		// Unsuccessful bind
		$this->addDebug("Unsuccessful bind for {$username}");

		return false;
	}

	/**
	 * Compare an entry and return the result
	 *
	 * @param   string  $dn         The distinguished name of the attribute to compare
	 * @param   string  $attribute  The attribute name/key
	 * @param   string  $value      The compared value of the attribute (case insensitive)
	 *
	 * @return  boolean   True if value matches otherwise returns False.
	 *
	 * @since   1.0
	 * @throws  SHLdapException
	 */
	public function compare($dn, $attribute, $value)
	{
		$this->operationAllowed();

		// Do the Ldap compare operation
		$result = @ldap_compare($this->resource, $dn, $attribute, $value);

		if ($result === -1)
		{
			// A error in the Ldap compare operation occurred
			throw new SHLdapException($this->getErrorCode(), 10131, JText::_('LIB_SHLDAP_ERR_10131'));
		}

		return $result;
	}

	/**
	 * Search directory and subtrees using a base dn and a filter, then returns
	 * the attributes in an array.
	 *
	 * @param   string  $dn          A base dn
	 * @param   string  $filter      Ldap filter to restrict results
	 * @param   array   $attributes  Array of attributes to return (empty array returns all)
	 *
	 * @return  SHLdapResult  Ldap Results.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function search($dn = null, $filter = null, $attributes = array())
	{
		$this->operationAllowed();

		if (is_null($dn))
		{
			// Use the base distinguished name in place of null value
			$dn = $this->base_dn;
		}

		if (is_null($filter))
		{
			// Use the default filter in place of null value
			$filter = self::DEFAULT_FILTER;
		}

		// Execute the Ldap search operation
		$result = @ldap_search($this->resource, $dn, $filter, $attributes, 0, self::SIZE_LIMIT, self::TIME_LIMIT);

		if ($result === false)
		{
			// An Ldap error has occurred
			throw new SHLdapException($this->getErrorCode(), 10102, JText::_('LIB_SHLDAP_ERR_10102'));
		}

		// Some results were found, lets import the results
		$result = $this->getEntries($result);

		return new SHLdapResult($result);
	}

	/**
	 * Read directory using given dn and filter, then returns the attributes
	 * in an array.
	 *
	 * @param   string  $dn          Dn of object to read
	 * @param   string  $filter      Ldap filter to restrict results
	 * @param   array   $attributes  Array of attributes to return (empty array returns all)
	 *
	 * @return  SHLdapResult  Ldap Results.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function read($dn, $filter = null, $attributes = array())
	{
		$this->operationAllowed();

		if (is_null($filter))
		{
			// Use the default filter in place of null value
			$filter = self::DEFAULT_FILTER;
		}

		// Execute the Ldap read operation
		$result = @ldap_read($this->resource, $dn, $filter, $attributes, 0, self::SIZE_LIMIT, self::TIME_LIMIT);

		if ($result === false)
		{
			// An Ldap error has occurred
			throw new SHLdapException($this->getErrorCode(), 10112, JText::_('LIB_SHLDAP_ERR_10112'));
		}

		// Some results were found, lets import the results
		$result = $this->getEntries($result);

		return new SHLdapResult($result);
	}

	/**
	 * Process result object (usually from a LDAP search or read), then return the
	 * result entries in an array. For each entry, only attributes with values are
	 * pushed onto the entry array.
	 * Note: ldap_get_entries is not used due to the 1000 record limit
	 *
	 * @param   resource  $result  The result object from a returned search or read
	 *
	 * @return  array  An array of entries
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException
	 */
	public function getEntries($result)
	{
		if (!is_resource($result))
		{
			// The result parameter must be a resource
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10121'), 10121);
		}

		// Store all entries inside the array
		$entries = array();

		// For each entry in results from the first entry till there are none left
		for ($entry = @ldap_first_entry($this->resource, $result); $entry != false; $entry = @ldap_next_entry($this->resource, $entry))
		{
			// New entry, therefore, new array to store the data
			$entries[] = array();

			// Get the entry attributes that were requested and store in an array
			$attributes = @ldap_get_attributes($this->resource, $entry);

			/*
			 * For each entry attribute, check there is a value from the result
			 * and if and only if there is a value, push it onto the current entry's
			 * array. This means that if a attribute has no values associated with it,
			 * the key will NOT be included.
			 *
			 * Note: the count key is removed during this process.
			 */
			foreach ($attributes as $name => $value)
			{
				// Check the value is a valid array before checking for any valid result values
				if (is_array($value) && $value['count'] > 0)
				{
					// Remove the count key before pushing it to the entry's array
					unset($value['count']);

					// Push the attribute key and value to the entry's array
					$entries[count($entries) - 1][$name] = $value;
				}
			}

			// Always add the distinguished name to the object regardless of the attribute result
			$entries[count($entries) - 1]['dn'] = @ldap_get_dn($this->resource, $entry);
		}

		return $entries;
	}

	/**
	 * Modifies an existing entry (i.e. attributes) at the object-level in
	 * the Ldap directory.
	 *
	 * @param   string  $dn          The distinguished name of the entity
	 * @param   array   $attributes  An array of attribute values to modify
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function modify($dn, $attributes)
	{
		$this->operationAllowed();

		// Do the Ldap modify operation
		$result = @ldap_modify($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify operation failed
			throw new SHLdapException($this->getErrorCode(), 10141, JText::_('LIB_SHLDAP_ERR_10141'));
		}

		return $result;
	}

	/**
	 * Add one or more attributes to a already existing specified dn.
	 *
	 * @param   string  $dn          The dn which to add the attributes
	 * @param   array   $attributes  An array of attributes to add
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function addAttributes($dn, $attributes)
	{
		$this->operationAllowed();

		// Do the Ldap modify add operation
		$result = @ldap_mod_add($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify add operation failed
			throw new SHLdapException($this->getErrorCode(), 10171, JText::_('LIB_SHLDAP_ERR_10171'));
		}

		return $result;
	}

	/**
	 * Deletes one or more attributes from a specified distinguished name.
	 *
	 * @param   string  $dn          The dn which contains the attributes to remove
	 * @param   array   $attributes  An array of attributes to remove
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function deleteAttributes($dn, $attributes)
	{
		$this->operationAllowed();

		// Do the Ldap modify delete operation
		$result = @ldap_mod_del($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify delete operation failed
			throw new SHLdapException($this->getErrorCode(), 10161, JText::_('LIB_SHLDAP_ERR_10161'));
		}

		return $result;
	}

	/**
	 * Replaces one or more attributes from a specified distinguished name.
	 *
	 * @param   string  $dn          The distinguished name which contains the attributes to replace
	 * @param   array   $attributes  An array of attribute values to replace
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function replaceAttributes($dn, $attributes)
	{
		$this->operationAllowed();

		// Do the Ldap modify replace operation
		$result = @ldap_mod_replace($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify replace operation failed
			throw new SHLdapException($this->getErrorCode(), 10151, JText::_('LIB_SHLDAP_ERR_10151'));
		}

		return $result;
	}

	/**
	 * Add a new entry in the LDAP directory.
	 *
	 * @param   string  $dn          The distinguished name where to put the object
	 * @param   array   $attributes  An array of arrays describing the object to add
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function add($dn, $attributes)
	{
		$this->operationAllowed();

		// Do the Ldap add operation
		$result = @ldap_add($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap add operation failed
			throw new SHLdapException($this->getErrorCode(), 10191, JText::_('LIB_SHLDAP_ERR_10191'));
		}

		return $result;
	}

	/**
	 * Delete a entry from the LDAP directory.
	 *
	 * @param   string  $dn  The distinguished name of the object to delete
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function delete($dn)
	{
		$this->operationAllowed();

		// Do the Ldap delete operation
		$result = @ldap_delete($this->resource, $dn);

		if ($result === false)
		{
			// Ldap delete operation failed
			throw new SHLdapException($this->getErrorCode(), 10181, JText::_('LIB_SHLDAP_ERR_10181'));
		}

		return $result;
	}

	/**
	 * Rename the entry
	 *
	 * @param   string   $dn            The distinguished name of the entry at the moment
	 * @param   string   $newRdn        The RDN of the new entry (e.g. cn=newvalue)
	 * @param   string   $newParent     The full distinguished name of the parent (null by default)
	 * @param   boolean  $deleteOldRdn  Delete the old values (true by default)
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.0
	 * @throws  SHLdapException   The Ldap operation failed.
	 * @throws  RuntimeException  Ldap either not binded or connected.
	 */
	public function rename($dn, $newRdn, $newParent = null, $deleteOldRdn = true)
	{
		$this->operationAllowed();

		// Do the Ldap rename operation
		$result = @ldap_rename($this->resource, $dn, $newRdn, $newParent, $deleteOldRdn);

		if ($result === false)
		{
			// Ldap rename operation failed
			throw new SHLdapException($this->getErrorCode(), 10201, JText::_('LIB_SHLDAP_ERR_10201'));
		}

		return $result;
	}

	/**
	 * Get a users Ldap distinguished name with optional bind authentication.
	 *
	 * @param   string   $username      Authenticating username
	 * @param   string   $password      Authenticating password
	 * @param   boolean  $authenticate  Attempt to authenticate the user (i.e.
	 *						bind the user with the password supplied)
	 *
	 * @return  string  User DN.
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException  Invalid argument in config related error
	 * @throws  SHLdapException           Ldap specific error.
	 * @throws  SHExceptionInvaliduser    User invalid error.
	 */
	public function getUserDn($username = null, $password = null, $authenticate = false)
	{
		if (empty($this->user_qry))
		{
			// No user query specified, cannot proceed
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10301'), 10301);
		}

		$replaced = str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);

		/*
		 * A basic detection check for LDAP filter.
		 * (i.e. distinguished names do not start and end with brackets).
		 */
		$useSearch = (preg_match('/(?<!\S)[\(]([\S]+)[\)](?!\S)/', $this->user_qry)) ? true : false;

		$this->addDebug(
			"Attempt to retrieve user distinguished name using '{$replaced}' " .
			($useSearch ? ' with search.' : ' with direct bind.')
		);

		// Get a array of distinguished names from either the search or direct bind methods.
		$DNs = $useSearch ? $this->getUserDnBySearch($username) : $this->getUserDnDirectly($username);

		if (empty($DNs))
		{
			/*
			 * Cannot find the specified username. We are going to throw
			 * a special user not found error to try to split between
			 * configuration errors and invalid errors. However, this might
			 * still be a configuration error.
			 */
			throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10302'), 10302, $username);
		}

		// Check if we have to authenticate the distinguished name with a password
		if ($authenticate)
		{
			// Attempt to bind each distinguished name with the specified password then return it
			foreach ($DNs as $dn)
			{
				if ($this->bind($dn, $password))
				{
					// Successfully binded with this distinguished name
					$this->addDebug("Successfully authenticated {$username} with distinguished name {$dn}.");
					$this->last_user_dn = $dn;

					return $dn;
				}
			}

			if ($useSearch)
			{
				// User found, but was unable to bind with the supplied password
				throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10303'), 10303, $username);
			}
			else
			{
				// Unable to bind directly to the given distinguished name parameters
				throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10304'), 10304, $username);
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
				if ($this->proxyBind())
				{
					foreach ($DNs as $dn)
					{
						try
						{
							$read = $this->read($dn, null, array('dn'));
						}
						catch (Exception $e)
						{
							// We don't need to worry about the exception too much
							$this->addDebug("Failed to read direct bind without auth DN {$dn}.");
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
						throw new SHExceptionInvaliduser(JText::_('LIB_SHLDAP_ERR_10305'), 10305, $username);
					}
				}
				else
				{
					// Unable to check Ldap directory, so have to assume the first is correct
					$result = $DNs[0];
				}
			}

			$this->addDebug("Using distinguished name {$result} for user {$username}.");
			$this->last_user_dn = $result;

			return $result;
		}
	}

	/**
	 * Get a user's dn by attempting to search for it in the directory.
	 *
	 * This method uses the query as a filter to find where the user is located in the directory
	 *
	 * @param   string  $username  Authenticating username.
	 *
	 * @return  array  An array containing user DNs.
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException  Invalid argument in config related error
	 * @throws  SHLdapException           Ldap search error
	 */
	public function getUserDnBySearch($username)
	{
		// Fixes special usernames and provides simple protection against ldap injections
		$username 	= SHLdapHelper::escape($username);
		$search 	= str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);

		if (empty($this->base_dn))
		{
			// No base distinguished name specified, cannot proceed.
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10321'), 10321);
		}

		// Bind using the proxy user so the user can be found in the Ldap directory.
		if (!$this->proxyBind())
		{
			// Failed to bind with proxy user
			throw new InvalidArgumentException(JText::_('LIB_SHLDAP_ERR_10322'), 10322);
		}

		// Search the directory for the user
		$result = $this->search(null, $search, array($this->ldap_uid));

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
	 * @param   string  $username  Authenticating username.
	 *
	 * @return  array  An array containing distinguished names.
	 *
	 * @since   1.0
	 * @throws  InvalidArgumentException  Invalid argument in config related error
	 */
	public function getUserDnDirectly($username)
	{
		$return = array();

		// Fixes special usernames and provides protection against distinguished name injection
		$username = SHLdapHelper::escape($username, true);

		// Replace the username placeholder with the authenticating username
		$search = str_replace(self::USERNAME_REPLACE, $username, $this->user_qry);

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
	 * Returns the last LDAP error code number.
	 *
	 * @return  integer  Error code number
	 *
	 * @since   2.0
	 */
	public function getErrorCode()
	{
		return (int) ldap_errno($this->resource);
	}

	/**
	 * Returns the last LDAP error message.
	 *
	 * @return  string  Error message.
	 *
	 * @since   1.0
	 */
	public function getErrorMsg()
	{
		return ldap_error($this->resource);
	}

	/**
	 * Converts an LDAP error ID to a string.
	 *
	 * @param   integer  $id  Ldap error ID.
	 *
	 * @return  string  Error message.
	 *
	 * @since   2.0
	 */
	public static function errorToString($id)
	{
		return ldap_err2str($id);
	}

	/**
	 * Splits DN into its component parts.
	 *
	 * @param   string   $dn      Distinguished name of an LDAP entity.
	 * @param   integer  $attrib  Use 0 to return only values, use 1 to get attributes as well.
	 *
	 * @return  array
	 *
	 * @since   2.0
	 */
	public static function explodeDn($dn, $attrib = 0)
	{
		return ldap_explode_dn($dn, $attrib);
	}

	/**
	 * Sets the PHP LDAP debug level to highest.
	 *
	 * @return  null
	 *
	 * @since   2.0
	 */
	public static function fullDebug()
	{
		ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);
	}

	/**
	 * Class Destructor.
	 *
	 * @since   2.0
	 */
	public function __destruct()
	{
		// Close the LDAP connection
		$this->close();
	}
}
