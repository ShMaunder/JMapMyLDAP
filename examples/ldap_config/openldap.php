<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Examples
 * @subpackage  Ldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_PLATFORM') or die;

/**
 * LDAP configuration file for OpenLDAP.
 *
 * @package     Shmanic.Examples
 * @subpackage  Ldap
 * @since       2.0
 */
class SHLdapConfig
{
	public $host = 'ldap1.shmanic.net';

	public $port = '389';

	public $use_v3 = '1';

	public $negotiate_tls = '0';

	public $use_referrals = '0';

	public $proxy_username = 'cn=admin,dc=shmanic,dc=net';

	public $proxy_password = 'shmanic.com';

	public $proxy_encryption = '0';

	public $base_dn = 'dc=shmanic,dc=net';

	public $use_search = '1';

	public $user_qry = '(uid=[username])';

	public $ldap_fullname = 'cn';

	public $ldap_email = 'mail';

	public $ldap_uid = 'uid';

	public $ldap_password = 'userPassword';

	public $password_hash = 'sha';

	public $password_prefix = '1';
}
