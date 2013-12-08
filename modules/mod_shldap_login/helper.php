<?php
/**
 * Orginally forked from the Joomla! 2.5 mod_login module.
 *
 * PHP Version 5.3
 *
 * @package     Shmanic.Site
 * @subpackage  mod_shldap_login
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * A helper for mod_shldap_login.
 *
 * @package     Shmanic.Site
 * @subpackage  mod_shldap_login
 * @since       2.0
 */
abstract class ModShldapLoginHelper
{
	public static function getReturnURL($params, $type)
	{
		$app	= JFactory::getApplication();
		$router = $app->getRouter();
		$url 	= null;

		if ($itemid = $params->get($type))
		{
			$db		= JFactory::getDbo();
			$query	= $db->getQuery(true);

			$query->select($db->quoteName('link'));
			$query->from($db->quoteName('#__menu'));
			$query->where($db->quoteName('published') . '=1');
			$query->where($db->quoteName('id') . '=' . $db->quote($itemid));

			$db->setQuery($query);

			if ($link = $db->loadResult())
			{
				if ($router->getMode() == JROUTER_MODE_SEF)
				{
					$url = 'index.php?Itemid=' . $itemid;
				}
				else
				{
					$url = $link . '&Itemid=' . $itemid;
				}
			}
		}

		if (!$url)
		{
			// Stay on the same page
			$uri = clone JFactory::getURI();
			$vars = $router->parse($uri);
			unset($vars['lang']);

			if ($router->getMode() == JROUTER_MODE_SEF)
			{
				if (isset($vars['Itemid']))
				{
					$itemid = $vars['Itemid'];
					$menu = $app->getMenu();
					$item = $menu->getItem($itemid);
					unset($vars['Itemid']);

					if (isset($item) && $vars == $item->query)
					{
						$url = 'index.php?Itemid=' . $itemid;
					}
					else
					{
						$url = 'index.php?' . JURI::buildQuery($vars) . '&Itemid=' . $itemid;
					}
				}
				else
				{
					$url = 'index.php?' . JURI::buildQuery($vars);
				}
			}
			else
			{
				$url = 'index.php?' . JURI::buildQuery($vars);
			}
		}

		return base64_encode($url);
	}

	/**
	 * Returns whether there is currently a logged on user.
	 *
	 * @return  string  Returns 'logout' on logged out or 'login' on logged in.
	 *
	 * @since   2.0
	 */
	public static function getType()
	{
		$user = JFactory::getUser();

		return (!$user->get('guest')) ? 'logout' : 'login';
	}
}
