<?php
/**
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
 * An LDAP result class to help with array management for LDAP results.
 *
 * @package     Shmanic.Libraries
 * @subpackage  Ldap
 * @since       1.0
 */
final class SHLdapResult
{
	/**
	 * Holds the results array.
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $_results = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $results  Results array.
	 *
	 * @since  1.0
	 */
	public function __construct($results)
	{
		if (is_array($results))
		{
			// Emulate the DN correctly
			for ($i = 0; $i < count($results); $i++)
			{
				if (isset($results[$i]['dn']))
				{
					/* Special case for distinguished name because it doesn't
					 * have an array of values - uses string. Lets wrap it inside
					 * an array for compatibility.
					 */
					$results[$i]['dn'] = array($results[$i]['dn']);
				}
			}

			$this->_results = $results;
		}
	}

	/**
	 * Returns the results array if it contains any elements.
	 *
	 * @param   mixed  $default  Default value to return.
	 *
	 * @return  mixed  Array of entries or Default.
	 *
	 * @since   1.0
	 */
	public function getResults($default = false)
	{
		return count($this->_results) ? $this->_results : $default;
	}

	/**
	 * Counts the number of entries in the results array.
	 *
	 * @return  integer  Number of entries.
	 *
	 * @since   1.0
	 */
	public function countEntries()
	{
		return count($this->_results);
	}

	/**
	 * Returns the entry from the results array.
	 *
	 * @param   integer  $entry    Entry ID.
	 * @param   mixed    $default  Default value to return.
	 *
	 * @return  mixed  Array of attributes at entry or Default.
	 *
	 * @since   1.0
	 */
	public function getEntry($entry, $default = false)
	{
		return SHUtilArrayhelper::getValue($this->_results, $entry, $default);
	}

	/**
	 * Counts the number of attributes at the entry.
	 *
	 * @param   integer  $entry  Entry ID.
	 *
	 * @return  integer  Number of attributes.
	 *
	 * @since   1.0
	 */
	public function countAttributes($entry)
	{
		return count($this->getEntry($entry, array()));
	}

	/**
	 * Returns the attribute from the entry.
	 *
	 * @param   integer  $entry      Entry ID.
	 * @param   string   $attribute  Attribute name.
	 * @param   mixed    $default    Default value to return.
	 *
	 * @return  mixed  Array of values at attribute or Default.
	 *
	 * @since   1.0
	 */
	public function getAttribute($entry, $attribute, $default = false)
	{
		if (($getEntry = $this->getEntry($entry, false)) === false)
		{
			// No such entry exists
			return $default;
		}

		$values = SHUtilArrayhelper::getValue($getEntry, $attribute, $default);

		return $values;
	}

	/**
	 * Returns the distinguished name from the entry.
	 *
	 * @param   integer  $entry    Entry ID.
	 * @param   mixed    $default  Default value to return.
	 *
	 * @return  mixed  Distinguished name or Default.
	 *
	 * @since   2.0
	 */
	public function getDN($entry, $default = false)
	{
		return $this->getValue($entry, 'dn', 0, $default);
	}

	/**
	 * Returns an array of attribute names (i.e. keys) for a specified entry.
	 *
	 * @param   integer  $entry    Entry ID.
	 * @param   mixed    $default  Default value to return.
	 *
	 * @return  mixed  Array of attribute names or Default.
	 *
	 * @since   2.0
	 */
	public function getAttributeKeys($entry, $default = false)
	{
		$keys = array();

		$entry = $this->getEntry($entry);

		// For each attribute, push it onto the array
		foreach (array_keys($entry) as $key)
		{
			$keys[] = $key;
		}

		if (!count($keys))
		{
			// No attributes pushed, return default
			return $default;
		}

		return $keys;
	}

	/**
	 * Returns the attribute index from the entry.
	 *
	 * @param   integer  $entry    Entry ID.
	 * @param   integer  $index    Attribute index.
	 * @param   mixed    $default  Default value to return.
	 *
	 * @return  mixed  Array of values at attribute index or Default.
	 *
	 * @since   2.0
	 */
	public function getAttributeAtIndex($entry, $index, $default = false)
	{
		// Converts the attribute index to the attribute name
		if (($attribute = $this->getAttributeKeyAtIndex($entry, $index, false)) === false)
		{
			// The attribute index is non-existent
			return $default;
		}

		return $this->getAttribute($entry, $attribute, $default);
	}

	/**
	 * Converts and returns the attribute name to a attribute key.
	 *
	 * @param   integer  $entry    Entry ID.
	 * @param   integer  $index    Attribute index.
	 * @param   mixed    $default  Default value to return.
	 *
	 * @return  string  Attribute name or Default.
	 *
	 * @since   2.0
	 */
	public function getAttributeKeyAtIndex($entry, $index, $default = false)
	{
		$array = array_keys($this->getEntry($entry));

		return SHUtilArrayhelper::getValue(
			$array, $index, $default
		);
	}

	/**
	 * Converts and returns the attribute name to a attribute index.
	 *
	 * @param   integer  $entry      Entry ID.
	 * @param   string   $attribute  Attribute name.
	 * @param   mixed    $default    Default value to return.
	 *
	 * @return  integer  Attribute index or Default.
	 *
	 * @since   2.0
	 */
	public function getAttributeIndex($entry, $attribute, $default = false)
	{
		$index = array_search($attribute, array_keys($this->getEntry($entry)));

		if ($index === false)
		{
			// The attribute name is non-existent
			return $default;
		}

		return $index;
	}

	/**
	 * Counts the number of values at the attribute.
	 *
	 * @param   integer  $entry      Entry ID.
	 * @param   string   $attribute  Attribute name.
	 *
	 * @return  integer  Number of values.
	 *
	 * @since   1.0
	 */
	public function countValues($entry, $attribute)
	{
		return count($this->getAttribute($entry, $attribute, array()));
	}

	/**
	 * Returns the string value of the specified value ID.
	 *
	 * @param   integer  $entry      Entry ID.
	 * @param   string   $attribute  Attribute name.
	 * @param   integer  $value      Value ID.
	 * @param   mixed    $default    Default value to return.
	 *
	 * @return  string   Value or Default
	 *
	 * @since   1.0
	 */
	public function getValue($entry, $attribute, $value, $default = false)
	{
		if (($getAttribute = $this->getAttribute($entry, $attribute, false)) === false)
		{
			// No such attribute exists
			return $default;
		}

		return SHUtilArrayhelper::getValue($getAttribute, $value, $default);
	}
}
