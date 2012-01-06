<?php

defined('_JEXEC') or die;

jimport('joomla.application.controller');


class JMapMyLDAPControllerAuth extends JController
{
	
	public function check()
	{
		//lets get the ajax running babe
		if (!JRequest::getCmd('view')) {
			JRequest::setVar('view', 'xml'); // Probably should always be xml
		}
		
		JRequest::setVar('layout', 'authentication');

		parent::display();
	}
	
	
}