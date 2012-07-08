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
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * An LDAP authentication and modification class for all LDAP operations.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapBase extends JObject
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
	 * Debug stack
	 *
	 * @var    Array
	 * @since  2.0
	 */
	protected $debug = array();

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
	 * LDAP resource handler
	 *
	 * @var    mixed
	 * @since  1.0
	 */
	protected $resource = null;

	/**
	 * Constructor
	 *
	 * @param   object  $configObj  An object of configuration variables
	 *
	 * @since   1.0
	 * @throws  Exception
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
			throw new Exception(JText::_('LIB_SHLDAPBASE_ERR_990'), 990);
		}

		// Passes the array back to the parent for class property assignment
		parent::__construct($configArr);

		// For front-end error translation
		JFactory::getLanguage()->load('lib_jldap2', JPATH_BASE);

		// Check the Ldap extension is loaded
		if (!extension_loaded('ldap'))
		{
			// Ldap extension is not loaded
			throw new Exception(JText::_('LIB_SHLDAPBASE_ERR_991'), 990);
		}

		// Reset resource & debug
		$this->resource = null;
		$this->debug = array();

	}

	/**
	 * Add to the debug stack.
	 *
	 * @param   string  $message  String to push to stack
	 *
	 * @return  void
	 *
	 * @since  2.0
	 */
	public function addDebug($message)
	{
		$this->debug[] = $message;
	}

	/**
	 * Returns the attribute key for fullname.
	 *
	 * @return  string  Attribute key.
	 *
	 * @since   2.0
	 */
	public function getFullname()
	{
		return $this->ldap_fullname;
	}

	/**
	 * Returns the attribute key for email.
	 *
	 * @return  string  Email key.
	 *
	 * @since   2.0
	 */
	public function getEmail()
	{
		return $this->ldap_email;
	}

	/**
	 * Returns the attribute key for uid.
	 *
	 * @return  string  Uid Key.
	 *
	 * @since   2.0
	 */
	public function getUid()
	{
		return $this->ldap_uid;
	}

	/**
	 * Attempt connection to an LDAP server and returns the result.
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   1.0
	 */
	public function connect()
	{

		// A host parameter must be specified to connect
		if (empty($this->host))
		{
			$this->setError(new SHLdapException(null, 10001, JText::_('LIB_SHLDAPBASE_ERR_10001')));
			return false;
		}

		// If there is a connection already, then close it before progressing
		$this->close();

		try
		{

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
					$this->getErrorCode(), 10002, JText::sprintf('LIB_SHLDAPBASE_ERR_10002', $this->host . ':' . $this->port)
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
					throw new SHLdapException($this->getErrorCode(), 10003, JText::_('LIB_SHLDAPBASE_ERR_10003'));
				}
			}

			// Attempt to set the referrals option
			if (!ldap_set_option($this->resource, LDAP_OPT_REFERRALS, intval($this->use_referrals)))
			{
				// Failed to set referrals
				throw new SHLdapException($this->getErrorCode(), 10004, JText::_('LIB_SHLDAPBASE_ERR_10004'));
			}

			// Attempt to configure Start TLS
			if ($this->negotiate_tls)
			{
				if (!@ldap_start_tls($this->resource))
				{
					// Failed to start TLS
					throw new SHLdapException($this->getErrorCode(), 10005, JText::_('LIB_SHLDAPBASE_ERR_10005'));
				}
			}

		}
		catch (SHLdapException $e)
		{
			$this->setError($e);
			return false;
		}
		catch (Exception $e)
		{
			$this->setError(new SHLdapException(null, 10000, $e->getMessage()));
			return false;
		}

		// Connecting has been successful
		$this->addDebug('Successfully connected.');
		return true;
	}

	/**
	* Checks whether a resource is defined in the LDAP resource variable.
	* Note: this isn't reliable as an object is created when a connection is attempted.
	*
	* @return  boolean  Returns True on success or False on failure.
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
	}

	/**
	 * Compare an entry and return the result
	 *
	 * @param   string  $dn         The distinguished name of the attribute to compare
	 * @param   string  $attribute  The attribute name/key
	 * @param   string  $value      The compared value of the attribute (case insensitive)
	 *
	 * @return  mixed   Returns True if value matches other returns False. -1 on error.
	 *
	 * @since   1.0
	 */
	public function compare($dn, $attribute, $value)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return -1;
		}

		// Do the Ldap compare operation
		$result = @ldap_compare($this->resource, $dn, $attribute, $value);

		if ($result === -1)
		{
			// A error in the Ldap compare operation occurred
			$this->setError(new SHLdapException($this->getErrorCode(), 10131, JText::_('LIB_SHLDAPBASE_ERR_10131')));
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
	 * @return  array|false          Array of attributes and corresponding values or False on error
	 *
	 * @since   1.0
	 */
	public function search($dn, $filter, $attributes = array())
	{
		try
		{

			if (!$this->isConnected())
			{
				// There is no Ldap connection
				throw new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006'));
			}

			// Execute the Ldap search operation
			$result = @ldap_search($this->resource, $dn, $filter, $attributes, 0, self::SIZE_LIMIT, self::TIME_LIMIT);

			if ($result === false)
			{
				// An Ldap error has occurred
				throw new SHLdapException($this->getErrorCode(), 10102, JText::_('LIB_SHLDAPBASE_ERR_10102'));
			}

			if ($result)
			{
				// Some results were found, lets import the results
				return $this->getEntries($result);
			}
			else
			{
				// No results found
				return array();
			}

		}
		catch (SHLdapException $e)
		{
			$this->setError($e);
			return false;
		}
		catch (Exception $e)
		{
			$this->setError(new SHLdapException(null, 10100, $e->getMessage()));
			return false;
		}
	}

	/**
	 * Read directory using given dn and filter, then returns the attributes
	 * in an array.
	 *
	 * @param   string  $dn          Dn of object to read
	 * @param   string  $filter      Ldap filter to restrict results
	 * @param   array   $attributes  Array of attributes to return (empty array returns all)
	 *
	 * @return  array|false          Array of attributes and corresponding values or False on error
	 *
	 * @since   1.0
	 */
	public function read($dn, $filter, $attributes = array())
	{
		try
		{

			if (!$this->isConnected())
			{
				// There is no Ldap connection
				throw new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006'));
			}

			// Execute the Ldap read operation
			$result = @ldap_read($this->resource, $dn, $filter, $attributes, 0, self::SIZE_LIMIT, self::TIME_LIMIT);

			if ($result === false)
			{
				// An Ldap error has occurred
				throw new SHLdapException($this->getErrorCode(), 10112, JText::_('LIB_SHLDAPBASE_ERR_10112'));
			}

			if ($result)
			{
				// Some results were found, lets import the results
				return $this->getEntries($result);
			}
			else
			{
				// No results found
				return array();
			}

		}
		catch (SHLdapException $e)
		{
			$this->setError($e);
			return false;
		}
		catch (Exception $e)
		{
			$this->setError(new SHLdapException(null, 10110, $e->getMessage()));
			return false;
		}
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
	 */
	public function getEntries($result)
	{
		if (!is_resource($result))
		{
			// The result parameter must be a resource
			throw new Exception(JText::_('LIB_SHLDAPBASE_ERR_10121'), 10121);
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

		// Use direct password
		$password = $this->proxy_password;

		if ($this->proxy_encryption)
		{
			// There is password encryption, lets decrypt first
			jimport('joomla.utilities.simplecrypt');
			$crypt = new JSimpleCrypt;
			$password = $crypt->decrypt($password);
			unset($crypt);
		}

		if (@ldap_bind($this->resource, $this->proxy_username, $password))
		{
			// Successful bind
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
			// Successful bind
			return true;
		}

		// Unsuccessful bind
		$this->addDebug("Unsuccessful bind for {$username}");
		return false;

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
	 * Modifies an existing entry (i.e. attributes) at the object-level in
	 * the Ldap directory.
	 *
	 * @param   string  $dn          The distinguished name of the entity
	 * @param   array   $attributes  An array of attribute values to modify
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function modify($dn, $attributes)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap modify operation
		$result = @ldap_modify($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10141, JText::_('LIB_SHLDAPBASE_ERR_10141')));
		}

		return $result;
	}

	/**
	 * Add one or more attributes to a already existing specified dn.
	 *
	 * @param   string  $dn          The dn which to add the attributes
	 * @param   array   $attributes  An array of attributes to add
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function addAttributes($dn, $attributes)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap modify add operation
		$result = @ldap_mod_add($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify add operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10171, JText::_('LIB_SHLDAPBASE_ERR_10171')));
		}

		return $result;
	}

	/**
	 * Deletes one or more attributes from a specified distinguished name.
	 *
	 * @param   string  $dn          The dn which contains the attributes to remove
	 * @param   array   $attributes  An array of attributes to remove
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function deleteAttributes($dn, $attributes)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap modify delete operation
		$result = @ldap_mod_del($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify delete operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10161, JText::_('LIB_SHLDAPBASE_ERR_10161')));
		}

		return $result;
	}

	/**
	 * Replaces one or more attributes from a specified distinguished name.
	 *
	 * @param   string  $dn          The distinguished name which contains the attributes to replace
	 * @param   array   $attributes  An array of attribute values to replace
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function replaceAttributes($dn, $attributes)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap modify replace operation
		$result = @ldap_mod_replace($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap modify replace operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10151, JText::_('LIB_SHLDAPBASE_ERR_10151')));
		}

		return $result;
	}

	/**
	 * Add a new entry in the LDAP directory.
	 *
	 * @param   string  $dn          The distinguished name where to put the object
	 * @param   array   $attributes  An array of arrays describing the object to add
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function add($dn, $attributes)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap add operation
		$result = @ldap_add($this->resource, $dn, $attributes);

		if ($result === false)
		{
			// Ldap add operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10191, JText::_('LIB_SHLDAPBASE_ERR_10191')));
		}

		return $result;
	}

	/**
	 * Delete a entry from the LDAP directory.
	 *
	 * @param   string  $dn  The distinguished name of the object to delete
	 *
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function delete($dn)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap delete operation
		$result = @ldap_delete($this->resource, $dn);

		if ($result === false)
		{
			// Ldap delete operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10181, JText::_('LIB_SHLDAPBASE_ERR_10181')));
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
	 * @return  boolean  Returns True on success or False on failure.
	 *
	 * @since   2.0
	 */
	public function rename($dn, $newRdn, $newParent = null, $deleteOldRdn = true)
	{
		if (!$this->isConnected())
		{
			// There is no Ldap connection
			$this->setError(new SHLdapException(null, 10006, JText::_('LIB_SHLDAPBASE_ERR_10006')));
			return false;
		}

		// Do the Ldap rename operation
		$result = @ldap_rename($this->resource, $dn, $newRdn, $newParent, $deleteOldRdn);

		if ($result === false)
		{
			// Ldap rename operation failed
			$this->setError(new SHLdapException($this->getErrorCode(), 10201, JText::_('LIB_SHLDAPBASE_ERR_10201')));
		}

		return $result;
	}

	/**
	 * Destructor
	 *
	 * @since   2.0
	 */
	public function __destruct()
	{
		// Close the LDAP connection
		$this->close();
	}
}
