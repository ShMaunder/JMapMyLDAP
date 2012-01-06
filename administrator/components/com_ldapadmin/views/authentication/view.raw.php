<?php

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.view');


class LdapAdminViewauthentication extends JView
{
	protected $enabled;
	protected $items;
	protected $pagination;
	protected $state;

	/**
	 * Display the view
	 *
	 * @since	1.6
	 */
	public function display($tpl = null)
	{ 

		/*$return = "<?xml version=\"1.0\" encoding=\"utf8\" ?>";
     	$return .= "<options>";
      	$return .= "</options>";
      	echo $return;*/
		
		parent::display($tpl);
	}

}
