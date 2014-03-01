<?php

abstract class SHAdapterMap
{
	const TYPE_USER = 1;

	const TYPE_GROUP = 2;

	const TYPE_CONTACT = 3;

	/**
	 * Instance based cache to prevent double lookups for same user.
	 *
	 * @var    array
	 * @since  2.1
	 */
	protected static $userCache = array();

	public static function getUserLink($username = null)
	{
		return self::getUser($username);
	}

	public static function getUser($username = null)
	{
		if (isset(self::$userCache[$username]))
		{
			return self::$userCache[$username];
		}

		if ($link = self::lookupFromAdapterId(self::TYPE_USER, $username))
		{
			self::$userCache[$username] = $link;

			return self::$userCache[$username];
		}

		// TODO: currently is badly inefficient
		// BC for 2.0
		if ($type = SHUserHelper::getTypeParam($username))
		{
			$domain = SHUserHelper::getDomainParam($username);

			return array('adapter' => $type, 'domain' => $domain);
		}

		return array('adapter' => false, 'domain' => null);
	}

	public static function setUser(SHUserAdapter $adapter, $JUser)
	{
		if (is_numeric($JUser))
		{
			$id = $JUser;
		}
		elseif ($JUser instanceof JUser)
		{
			$id = $JUser->id;
		}
		else
		{
			$id = JUserHelper::getUserId($JUser);
		}

		if (!is_numeric($id))
		{
			//TODO: lang string
			throw new RuntimeException('Invalid User ID', 101);
		}

		// Check if we can retrieve link from cache
		if (isset(self::$userCache[$adapter->loginuser]) && (self::$userCache[$adapter->loginuser]['joomla_id'] === $id))
		{
			$link = self::$userCache[$adapter->loginuser];
		}
		else
		{
			$link = self::lookupFromJoomlaId(self::TYPE_USER, $id);
		}

		if ($link)
		{
			// Check if we actually need to update
			if (($link['adapter'] == $adapter->getName())
				&& ($link['domain'] == $adapter->getDomain())
				&& ($link['username'] == $adapter->loginuser)
				&& ($link['joomla_id'] == $id))
			{
				return;
			}

			// Commit the link update
			self::update($link['id'], $adapter->getName(), $adapter->getDomain(), $adapter->loginuser, $id);

		}
		else
		{
			// Commit the link insert
			self::insert(self::TYPE_USER, $adapter->getName(), $adapter->getDomain(), $adapter->loginuser, $id);
		}

		unset(self::$userCache[$adapter->loginuser]);
	}

	public static function update($id, $adapterName, $domain, $adapterId, $joomlaId)
	{
		$db = JFactory::getDbo();

		$db->setQuery(
			$db->getQuery(true)
				->update($db->quoteName('#__sh_adapter_map'))
				->set($db->quoteName('adapter_name') . ' = ' . $db->quote($adapterName))
				->set($db->quoteName('domain') . ' = ' . $db->quote($domain))
				->set($db->quoteName('adapter_id') . ' = ' . $db->quote($adapterId))
				->set($db->quoteName('joomla_id') . ' = ' . $db->quote($joomlaId))
				->where($db->quoteName('id') . ' = ' . $db->quote($id))
		);

		return $db->execute();
	}

	public static function insert($type, $adapterName, $domain, $adapterId, $joomlaId)
	{
		$db = JFactory::getDbo();

		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__sh_adapter_map'))
				->columns(
					array(
						$db->quoteName('type'),
						$db->quoteName('adapter_name'),
						$db->quoteName('domain'),
						$db->quoteName('adapter_id'),
						$db->quoteName('joomla_id')
					)
				)
				->values(
					$db->quote($type) . ', ' .
					$db->quote($adapterName) . ', ' .
					$db->quote($domain) . ', ' .
					$db->quote($adapterId) . ', ' .
					$db->quote($joomlaId)
				)
		);

		return $db->execute();
	}

	public static function lookup($key, $type, $value)
	{
		$db = JFactory::getDbo();

		$db->setQuery(
			$db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__sh_adapter_map'))
				->where($db->quoteName('type') . ' = ' . $db->quote($type))
				->where($db->quoteName($key) . ' = ' . $db->quote($value))
		);

		if ($results = $db->loadAssoc())
		{
			$results['adapter'] = $results['adapter_name'];

			if ($type === self::TYPE_USER)
			{
				$results['username'] = $results['adapter_id'];
			}

			return $results;
		}
	}

	public static function lookupFromJoomlaId($type, $joomlaId)
	{
		return self::lookup('joomla_id', $type, $joomlaId);
	}

	public static function lookupFromAdapterId($type, $adapterId)
	{
		return self::lookup('adapter_id', $type, $adapterId);
	}
}
