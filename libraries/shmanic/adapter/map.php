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

	/**
	 * Gets a user link from the adapter map table.
	 *
	 * @param   string|integer  $user  Either a username or Joomla user ID.
	 * @param   boolean         $isId  If the user input is a user ID then set to true.
	 *
	 * @return  array           Array of links for user or empty array if none found.
	 *
	 * @since   2.1
	 */
	public static function getUser($user = null, $isId = false)
	{
		// Use I_ for joomla_id and U_ for usernames
		$cacheKey = ($isId ? 'I_' : 'U_') . $user;

		if (isset(self::$userCache[$cacheKey]))
		{
			return self::$userCache[$cacheKey];
		}

		if ($isId)
		{
			$links = self::lookupFromJoomlaId(self::TYPE_USER, $user);
		}
		else
		{
			$links = self::lookupFromUsername($user);
		}

		if ($links)
		{
			self::$userCache[$cacheKey] = $links;

			if (!$isId)
			{
				// There should be only ever one Joomla_Id for a user therefore we can cache that now
				foreach ($links as $link)
				{
					self::$userCache['I_' . $link['joomla_id']] = array($link);
				}
			}

			return self::$userCache[$cacheKey];
		}

		// Backwards compatibility for 2.0 (*inefficient methods*)
		if ($type = SHUserHelper::getTypeParam($user))
		{
			$domain = SHUserHelper::getDomainParam($user);

			return array(
				array(
					'type' => self::TYPE_USER, 'adapter' => $type,
					'domain' => $domain, 'adapter_id' => null, 'joomla_id' => null
				)
			);
		}

		return array();
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
			//TODO: lang string
			throw new RuntimeException('Invalid User ID', 101);
		}

		$link = null;

		// Check if we can retrieve link from cache
		if (isset(self::$userCache[$adapter->loginuser]) && is_array(self::$userCache[$adapter->loginuser]))
		{
			foreach (self::$userCache[$adapter->loginuser] as $links)
			{
				if ($links['joomla_id'] === $id)
				{
					$link = self::$userCache[$adapter->loginuser];
					break;
				}
			}
		}

		if (empty($link))
		{
			$link = self::lookupFromJoomlaId(self::TYPE_USER, $id);
		}

		if ($link)
		{
			// Check if we actually need to update
			if (($link[0]['adapter'] == $adapter->getName())
				&& ($link[0]['domain'] == $adapter->getDomain())
				&& ($link[0]['username'] == $adapter->getId(false))
				&& ($link[0]['joomla_id'] == $id))
			{
				return;
			}

			// Commit the link update
			self::update($link[0]['id'], $adapter->getName(), $adapter->getDomain(), $adapter->getId(false), $id);

		}
		else
		{
			// Commit the link insert
			self::insert(self::TYPE_USER, $adapter->getName(), $adapter->getDomain(), $adapter->getId(false), $id);
		}

		unset(self::$userCache['U_' . $adapter->loginuser]);
		unset(self::$userCache['I_' . $id]);
	}

	/**
	 * Deletes the user from the adapter map table.
	 *
	 * @param   integer  $JUser  Joomla user ID.
	 *
	 * @return  void
	 *
	 * @since   2.1
	 */
	public static function deleteUser($JUser)
	{
		self::delete('joomla_id', self::TYPE_USER, $JUser);
	}

	public static function update($id, $adapterName, $domain, $adapterId, $joomlaId)
	{
		if (empty($id))
		{
			// Do not allow empty IDs
			return;
		}

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

	public static function delete($key, $type, $value)
	{
		if (empty($value))
		{
			// Do not allow empty values
			return;
		}

		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->delete($db->quoteName('#__sh_adapter_map'))
			->where($db->quoteName('type') . ' = ' . $type)
			->where($db->quoteName($key) . ' = ' . $db->quote($value));

		$db->setQuery($query);

		$db->execute();
	}

	public static function lookup($key, $type, $value)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
			->select('map.*, map.adapter_name AS adapter')
			->from('#__sh_adapter_map AS map')
			->where($db->quoteName('type') . ' = ' . $db->quote($type))
			->where($db->quoteName($key) . ' = ' . $db->quote($value));

		if ($type === self::TYPE_USER)
		{
			$query->select('users.username');

			// We need to use left join due to onUserAfterDelete having deleted juser already
			$query->join('LEFT', '#__users AS users ON map.joomla_id = users.id');
		}
		elseif ($type === self::TYPE_GROUP)
		{
			$query->select('groups.title');
			$query->join('LEFT', '#__usergroups AS groups ON map.joomla_id = groups.id');
		}

		$db->setQuery($query);

		$results = $db->loadAssocList();

		return $results;
	}

	public static function lookupFromJoomlaId($type, $joomlaId)
	{
		return self::lookup('joomla_id', $type, $joomlaId);
	}

	public static function lookupFromAdapterId($type, $adapterId)
	{
		return self::lookup('adapter_id', $type, $adapterId);
	}

	public static function lookupFromUsername($username)
	{
		return self::lookup('users.username', self::TYPE_USER, $username);
	}
}
