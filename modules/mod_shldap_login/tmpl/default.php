<?php
/**
 * Orginally forked from the Joomla! 2.5 MOD_SHLDAP_LOGIN module.
 *
 * PHP Version 5.3
 *
 * @package     Shmanic.Site
 * @subpackage  mod_shldap_login
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;
JHtml::_('behavior.keepalive');

?>
<?php if ($type == 'logout') : ?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form">
<?php if ($params->get('greeting')) : ?>
	<div class="login-greeting">
	<?php
	if ($params->get('name') == 0)
	{
		echo JText::sprintf('MOD_SHLDAP_LOGIN_HINAME', htmlspecialchars($user->get('name')));
	}
	else
	{
		echo JText::sprintf('MOD_SHLDAP_LOGIN_HINAME', htmlspecialchars($user->get('username')));
	} ?>
	</div>
<?php endif; ?>
	<div class="logout-button">
		<input type="submit" name="Submit" class="button" value="<?php echo JText::_('JLOGOUT'); ?>" />
		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.logout" />
		<input type="hidden" name="return" value="<?php echo $return; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
<?php else : ?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form" >
	<?php if ($params->get('pretext')): ?>
		<div class="pretext">
		<p><?php echo $params->get('pretext'); ?></p>
		</div>
	<?php endif; ?>
	<fieldset class="userdata">
	<?php
	if (!JPluginHelper::isEnabled('system', 'remember'))
	{
		// Hide and Disable the remember me field
		$form->setFieldAttribute('remember', 'hidden', 'true');
		$form->setFieldAttribute('remember', 'disabled', 'true');
	}
	if ($forced = (int) $params->get('forcedomain', 0))
	{
		// Select the forced domain and read-only it (better way to do this?)
		$form->bind(array('ldapdomain' => $forced));
		$form->setFieldAttribute('ldapdomain', 'readonly', 'true');
	}
	if ($params->get('hidedomain', false))
	{
		// Hide the domain field
		$form->setFieldAttribute('ldapdomain', 'hidden', 'true');
	}
	?>
	<?php foreach ($form->getFieldset('credentials') as $field) : ?>
		<?php if (!$field->hidden) : ?>
			<p class="login-fields" id="form-login-<?php echo $field->name; ?>"><?php echo $field->label; ?>
			<?php echo $field->input; ?></p>
		<?php endif; ?>
	<?php endforeach; ?>
	<input type="submit" name="Submit" class="button" value="<?php echo JText::_('JLOGIN') ?>" />
	<input type="hidden" name="option" value="com_users" />
	<input type="hidden" name="task" value="user.login" />
	<input type="hidden" name="return" value="<?php echo $return; ?>" />
	<?php echo JHtml::_('form.token'); ?>
	</fieldset>
	<ul>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
			<?php echo JText::_('MOD_SHLDAP_LOGIN_FORGOT_YOUR_PASSWORD'); ?></a>
		</li>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind'); ?>">
			<?php echo JText::_('MOD_SHLDAP_LOGIN_FORGOT_YOUR_USERNAME'); ?></a>
		</li>
		<?php
		$usersConfig = JComponentHelper::getParams('com_users');
		if ($usersConfig->get('allowUserRegistration')) : ?>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>">
				<?php echo JText::_('MOD_SHLDAP_LOGIN_REGISTER'); ?></a>
		</li>
		<?php endif; ?>
	</ul>
	<?php if ($params->get('posttext')): ?>
		<div class="posttext">
		<p><?php echo $params->get('posttext'); ?></p>
		</div>
	<?php endif; ?>
</form>
<?php endif; ?>
