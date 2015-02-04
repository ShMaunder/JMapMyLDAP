<?php
/**
* Joomla Community Builder CB ldap directreports Type Plugin: plug_cbldapdirectreports
* @version $Id$
* @package plug_cbldapdirectreports
* @subpackage cb.ldapdirectreports.php
* @author Kiran Cheema
* @copyright (C) 2014 
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
* @final 1
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->loadPluginGroup( 'user', array( (int) 1 ) );
$_PLUGINS->registerUserFieldTypes( array( 'directreports' => 'CBfield_ldapdirectreports' ) );
$_PLUGINS->registerUserFieldParams();

class CBfield_ldapdirectreports extends CBfield_text {
		function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		global $_CB_framework, $ueConfig, $_CB_database;
		
		$oReturn = null;
		//grab user profile id 
		$thisuser = $user->id;
	//var_dump($thisuser);
	// grab ldap.thumbnailPhoto from user_profile table 
	$db = JFactory::getDBO();
	$query = $db->getQuery(true);
	$query->select($db->quoteName('profile_value'))	
		->from($db->quoteName('#__user_profiles'))
		->where($query->quoteName('profile_key') . ' = ' . $query->quote('ldap.directReports'))
		->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $thisuser));
	$db->setQuery($query);
	$result=$db->loadResult();
	
	//check the result of the Query from the DB
	if($result!==null){
		$stafflist=explode("\n",$result);
		foreach($stafflist as $staff){
		$staffquery = $db->getQuery(true);	
		$staffquery->select($db->quoteName('user_id'))	
		->from($db->quoteName('#__user_profiles'))
		->where($query->quoteName('profile_key') . ' = ' . $query->quote('ldap.distinguishedName'))
		->where($query->quoteName('profile_value') . ' = ' . $query->quote($staff));
		$db->setQuery($staffquery);
		$staffid=$db->loadResult();
		
		$cbUser =& CBuser::getInstance( $staffid );
		
			$oReturn.="<div>".$cbUser->getField('cb_ldap_thumb').$cbUser->getField('name')."</div>";
		}
		}else{
		//if no image from Ldap then check CB avatar field
		$oReturn=null;
		}
			
		return $oReturn;
	}
}//end of ldap directreports field
?>
