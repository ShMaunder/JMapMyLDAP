<?php
use CB\Database\Table\UserTable;
use CB\Database\Table\FieldTable;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;

$_PLUGINS->loadPluginGroup( 'user' );

$_PLUGINS->registerUserFieldParams();
$_PLUGINS->registerUserFieldTypes( array( 'ldapthumbnail' => 'CBfield_ldapthumbnail' ));

class CBfield_ldapthumbnail extends CBfield_image
{

	/**
	 * Returns a field in specified format
	 *
	 * @param  FieldTable  $field
	 * @param  UserTable   $user
	 * @param  string      $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string      $reason  'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  int         $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	public function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		if ( $output == 'htmledit' ) {
			// There's no edit or search display for this calculated field so suppress the core image field output:
			return null;
		}

		return parent::getField( $field, $user, $output, $reason, $list_compare_types );
	}

	/**
	 * returns full or thumbnail path of image
	 *
	 * @param  FieldTable   $field
	 * @param  UserTable    $user
	 * @param  boolean      $thumbnail
	 * @param  int          $showAvatar
	 * @return null|string
	 */
	public function _avatarLivePath( &$field, &$user, $thumbnail = true, $showAvatar = 2 ) {
		global $_CB_database;

		$return				=	null;

		if ( $user && $user->id ) {
			$query			=	'SELECT ' . $_CB_database->NameQuote( 'profile_value' )
							.	"\n FROM " . $_CB_database->NameQuote( '#__user_profiles' )
							.	"\n WHERE " . $_CB_database->NameQuote( 'profile_key' ) . " = " . $_CB_database->Quote( 'ldap.thumbnailPhoto' )
							.	"\n AND " . $_CB_database->NameQuote( 'user_id' ) . " = " . (int) $user->get( 'id' );
			$_CB_database->setQuery( $query, 0, 1 );
			$return			=	$_CB_database->loadResult();

			if ( $return ) {
				$return		=	'data:image/jpeg;base64,' . $return;
			}
		}

		if ( ! $return ) {
			// If there is no image lets fallback to the avatar path:
			$return			=	CBuser::getInstance( (int) $user->get( 'id' ) )->getField( 'avatar', null, 'php', 'none', ( $thumbnail ? 'list' : 'profile' ) );

			if ( is_array( $return ) ) {
				$return		=	$return['avatar'];
			}
		}

		return $return;
	}
}
