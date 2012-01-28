<?php
/**
 * @version     $Id:$
 * @author      Shaun Maunder <shaun@shmanic.com>
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapProfile
 * 
 * @copyright	Copyright (C) 2011 Shaun Maunder. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('shmanic.ldap.profile');

/**
 * LDAP Profile Plugin
 *
 * @package     Shmanic.Plugin
 * @subpackage  System.LdapProfile
 * @since       2.0
 */
class plgLdapProfile extends JPlugin 
{	
	
	/**
	* An object to a instance of LdapProfile
	* 
	* @var    LdapProfile
	* @since  2.0
	*/
	protected $profile = null;
	
	/* holds the name ldap key value */
	public $nameKey = null;
	
	/* holds the email ldap key value */
	public $emailKey = null;
	
	/* holds the reference to the xml file */
	protected $xml = null;
	
	/**
	 * Constructor
	 *
	 * @param  object  &$subject  The object to observe
	 * @param  array   $config    An array that holds the plugin configuration
	 * 
	 * @since  2.0
	 */
	function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		$this->profile = new LdapProfile(
			$this->params->toArray()
		);
		
		$this->xml = $this->profile->getXMLFields();
	}
	
	/**
	 * Called during a ldap login. If this method returns false,
	 * then the whole login is cancelled.
	 * 
	 * Checks to ensure the onlogin parameter is true then calls
	 * the on sync method.
	 *
	 * @param  object  $instance  A JUser object for the authenticating user
	 * @param  array   $user      The auth response including LDAP attributes
	 * @param  array   $options   Array holding options
	 *
	 * @return  boolean  False to cancel login
	 * @since   2.0
	 */
	public function onLdapLogin(&$instance, $user, $options = array()) 
	{
		if($this->params->get('onlogin')) {
			$this->onLdapSync($instance, $user, $options);
		}
		
		// Even if it did fail, we don't want to cancel the logon
		return true;
	}		
	
	/**
	* Called during an active LDAP connection after the
	* initial user LDAP read for any extra object/attributes that
	* were not returned from the initial LDAP read.
	*
	* @param  JLDAP2  $ldap
	* @param  array   $attribute values of ldap read
	* @param  array   $options (user=>JUser)
	*
	* @return  void
	* @since   2.0
	*/
	public function onLdapAfterRead(&$ldap, &$details, $options = array())
	{
		$this->nameKey 	= $ldap->ldap_fullname;
		$this->emailKey = $ldap->ldap_email;
	}
	
	/**
	 * Called during a ldap synchronisation.
	 * 
	 * Checks to ensure that required variables are set before
	 * calling the main do mapping library routine.
	 *
	 * @param  object  $instance  A JUser object for the authenticating user
	 * @param  array   $user      The auth response including LDAP attributes
	 * @param  array   $options   Array holding options
	 *
	 * @return  boolean  True on success
	 * @since   2.0
	 */
	public function onLdapSync(&$instance, $user, $options = array()) 
	{
		if(!class_exists('LdapProfile')) {
			$this->_reportError('Missing LDAP Profile Class');
			return false;
		}
		
		if(isset($user['attributes'])) {
			$this->profile->doSync($instance, $user, $this->nameKey, $this->emailKey);
			$this->profile->saveProfile($this->xml, $instance, $user, $options);
		} else {
			$this->_reportError('No attributes to process');
			return false;
		}
		
	}
	
	/**
	 * LDAP profile adapted from docs.joomla.org/Creating_a_profile_plugin
	 * 
	* @param  string  $context  The context for the data
	* @param  int     $data     The user id
	* @param  object
	*
	* @return  boolean
	* @since   2.0
	*/
	public function onLdapContentPrepareData($context, $data)
	{
		
		$forms = explode(';', $this->params->get('permitted_forms'));
		
		// Check we are manipulating a valid form
		if (!in_array($context, $forms)){
			return true;
		}

		$userId = isset($data->id) ? $data->id : 0;
		
		// Load the profile data from the database.
		$records = $this->profile->queryProfile($userId, true);
		
		// Merge the profile data
		$data->ldap_profile = array();
		foreach($records as $record) {
			$data->ldap_profile[$record['profile_key']] = $record['profile_value'];
		}
		
		return true;
	}
	
	/**
	 * LDAP profile adapted from docs.joomla.org/Creating_a_profile_plugin
	 * 
	 * @param  JForm  $form  The form to be altered.
	 * @param  array  $data  The associated data for the form.
	 *
	 * @return  boolean
	 * @since   2.0
	 */
	public function onLdapContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm)) {
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}
		
		$forms = explode(';', $this->params->get('permitted_forms'));
		
		// Check we are manipulating a valid form
		if (!in_array($form->getName(), $forms)) {
			return true;
		}
		
		// Load in the profile XML file
		if(($xml = JFactory::getXML($this->profile->getXMLPath(), true)) && ($form->load($xml, false, false))) {			

			// :: success ::
			return true;
		}
		
	}
	
	// Delete the profile
	public function onLdapAfterDelete($user, $success, $msg)
	{
		if (!$success) {
			return false;
		}
		
		if($userId = JArrayHelper::getValue($user, 'id', 0, 'int')) {
			$this->profile->deleteProfile($userId);
		}
		
	}
	
	public function onLdapAfterSave($data, $isNew, $result, $error) {
		
		if ($result && isset($data['ldap_profile']) && (count($data['ldap_profile'])))
		{
			$username = JArrayHelper::getValue($data, 'username', 0, 'string');
			
			// Remove any disabled elements from being saved
			$toSave = $this->profile->cleanInput($this->xml, $data['ldap_profile']);
			
			$this->profile->saveToLDAP($this->xml, $username, $toSave);	
		}
		
	}
	
	/**
	 * Called just before a user LDAP read to gather
	 * extra user ldap attributes required for this plugin.
	 *  
	 * @param  JLDAP2  $ldap
	 * @param  array   $options
	 * 
	 * @return  array  Array of attributes required for this plug-in
	 * @since   2.0
	 */
	public function onLdapBeforeRead(&$ldap, $options = array())
	{
		if($this->xml) {
			return $this->profile->getAttributes($this->xml);
		}
	}
	
	/**
	 * Reports an error to the screen and log. If debug mode is on 
	 * then it displays the specific error on screen, if debug mode 
	 * is off then it displays a generic error.
	 *
	 * @param  string  $exception  The error
	 * 
	 * @return  void
	 * @since   1.0
	 * 
	 * @deprecated old ways
	 */
	protected function _reportError($exception = null) 
	{
		//TODO: An error routine...
	}

}
