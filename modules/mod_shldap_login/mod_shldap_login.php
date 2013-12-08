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

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

$params->def('greeting', 1);

$type	= modShldapLoginHelper::getType();
$return	= modShldapLoginHelper::getReturnURL($params, $type);
$user	= JFactory::getUser();

$form = JForm::getInstance('mod_shldap_login', dirname(__FILE__) . '/forms/login.xml');

require JModuleHelper::getLayoutPath('mod_shldap_login', $params->get('layout', 'default'));
