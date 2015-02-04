<?php
/**
* Joomla Community Builder CB ldap thumbnail Type Plugin: plug_cbldapthumbnail
* @version $Id$
* @package plug_cbldapthumbnail
* @subpackage cb.ldapthumbnail.php
* @author Kiran Cheema
* @copyright (C) 2014 
* @license Limited http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
* @final 1.2
*/

/** ensure this file is being included by a parent file */
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->loadPluginGroup( 'user', array( (int) 1 ) );
$_PLUGINS->registerUserFieldTypes( array( 'ldapthumbnail' => 'CBfield_ldapthumbnail' ) );
$_PLUGINS->registerUserFieldParams();

class CBfield_ldapthumbnail extends cbFieldHandler {
		function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		global $_CB_framework, $ueConfig, $_CB_database;
		
		$oReturn = null;
		//grab user profile id 
		$thisuser = $user->id;
	
	// grab ldap.thumbnailPhoto from user_profile table 
	$db = JFactory::getDBO();
	$query = $db->getQuery(true);
	$query->select($db->quoteName('profile_value'))	
		->from($db->quoteName('#__user_profiles'))
		->where($query->quoteName('profile_key') . ' = ' . $query->quote('ldap.thumbnailPhoto'))
		->where($query->quoteName('user_id') . ' = ' . $query->quote((int) $thisuser));
	$db->setQuery($query);
	$result=$db->loadResult();
	
	//check the result of the Query from the DB
	if($result!==null){
		//if there is something in the result stream back the image
			$image=	'<img src="data:image/jpeg;base64,'.$result.'"/>';
		}else{
		//if no image from Ldap then check CB avatar field
			$cbUser =& CBuser::getInstance( $thisuser );
			if ( $cbUser !== null ) {
				//$thumbnailAvatarHtmlWithLink 	= $cbUser->getField( 'avatar', null, 'html', 'none', 'list' );
				$bigAvatarHtmlWithLink = $cbUser->getField( 'avatar' );
				$image = $bigAvatarHtmlWithLink;			
			}
}
		$oReturn=$image;	
		return $oReturn;
	}
}//end of ldap Thumbnail field
?>
