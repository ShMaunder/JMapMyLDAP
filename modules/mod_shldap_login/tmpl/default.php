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
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

$version = new JVersion;

if ($version->isCompatible('3.0.0'))
{
	JHtml::_('bootstrap.tooltip');
}

JHtml::_('behavior.keepalive');

?>

<?php if ($type == 'logout') : ?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form" class="form-inline">
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
		<div id="form-login-submit" class="control-group">
			<div class="controls">
				<input type="submit" tabindex="0" name="Submit" class="button btn btn-primary" value="<?php echo JText::_('JLOGOUT'); ?>" />
			</div>
		</div>
		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.logout" />
		<input type="hidden" name="return" value="<?php echo $return; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
<?php else : ?>
<form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="login-form" class="form-inline">
	<?php if ($params->get('pretext')): ?>
		<div class="pretext">
		<p><?php echo $params->get('pretext'); ?></p>
		</div>
	<?php endif; ?>
	<div class="userdata">
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
				<?php if ($version->isCompatible('3.0.0')) : ?>

					<div id="form-login-<?php echo $field->name; ?>" class="control-group <?php echo ($field->type == 'Checkbox') ? 'checkbox' : ''; ?>">

					<?php switch ($field->name) :

						case 'username': ?>
							<div class="controls">
								<?php if (!$params->get('usetext')) : ?>
									<div class="input-prepend input-append">
										<span class="add-on">
											<span class="icon-user tip" title="<?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_USERNAME'); ?>"></span>
											<label for="modlgn-<?php echo $field->name; ?>" class="element-invisible"><?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_USERNAME'); ?></label>
										</span>
										<input id="modlgn-<?php echo $field->name; ?>" type="text" name="<?php echo $field->name; ?>" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_USERNAME'); ?>" />
									</div>
								<?php else: ?>
									<label for="modlgn-<?php echo $field->name; ?>"><?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_USERNAME'); ?></label>
									<input id="modlgn-<?php echo $field->name; ?>" type="text" name="<?php echo $field->name; ?>" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_USERNAME'); ?>" />
								<?php endif; ?>
							</div>
							<?php break; ?>

						<?php case 'password': ?>
							<div class="controls">
								<?php if (!$params->get('usetext')) : ?>
									<div class="input-prepend input-append">
										<span class="add-on">
											<span class="icon-lock tip" title="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>"></span>
											<label for="modlgn-<?php echo $field->name; ?>" class="element-invisible"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
										</span>
										<input id="modlgn-<?php echo $field->name; ?>" type="password" name="<?php echo $field->name; ?>" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>" />
									</div>
								<?php else: ?>
									<label for="modlgn-<?php echo $field->name; ?>"><?php echo JText::_('JGLOBAL_PASSWORD'); ?></label>
									<input id="modlgn-<?php echo $field->name; ?>" type="password" name="<?php echo $field->name; ?>" class="input-small" tabindex="0" size="18" placeholder="<?php echo JText::_('JGLOBAL_PASSWORD'); ?>" />
								<?php endif; ?>
							</div>
							<?php break; ?>

						<?php case 'login_domain': ?>
							<div class="controls">
								<?php if (!$params->get('usetext')) : ?>
									<div class="input-prepend input-append">
										<span class="add-on">
											<span class="icon-puzzle tip" title="<?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_DOMAIN'); ?>"></span>
											<label for="modlgn-<?php echo $field->name; ?>" class="element-invisible"><?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_DOMAIN'); ?></label>
										</span>

										<?php echo $field->input; ?>
									</div>
								<?php else: ?>
									<label for="modlgn-<?php echo $field->name; ?>"><?php echo JText::_('MOD_SHLDAP_LOGIN_VALUE_DOMAIN'); ?></label>
									<?php echo $field->input; ?>
								<?php endif; ?>
							</div>
							<?php break; ?>

						<?php case 'remember': ?>

							<label for="modlgn-<?php echo $field->name; ?>" class="control-label"><?php echo JText::_('MOD_SHLDAP_LOGIN_REMEMBER_ME') ?></label>
							<input id="modlgn-<?php echo $field->name; ?>" type="checkbox" name="<?php echo $field->name; ?>" class="inputbox" value="yes" />

							<?php break; ?>
					<?php endswitch; ?>

					</div>
				<?php else : ?>
					<p class="login-fields" id="form-login-<?php echo $field->name; ?>"><?php echo $field->label; ?>
					<?php echo $field->input; ?></p>
				<?php endif; ?>
			<?php endif; ?>
		<?php endforeach; ?>

		<div id="form-login-submit" class="control-group">
			<div class="controls">
				<input type="submit" tabindex="0" name="Submit" class="button btn btn-primary" value="<?php echo JText::_('JLOGIN') ?>" />
			</div>
		</div>

		<input type="hidden" name="option" value="com_users" />
		<input type="hidden" name="task" value="user.login" />
		<input type="hidden" name="return" value="<?php echo $return; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
	<ul class="unstyled">
		<?php
		$usersConfig = JComponentHelper::getParams('com_users');
		if ($usersConfig->get('allowUserRegistration')) : ?>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=registration'); ?>">
				<?php echo JText::_('MOD_SHLDAP_LOGIN_REGISTER'); ?><span class="icon-arrow-right"></span></a>
		</li>
		<?php endif; ?>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=remind'); ?>">
			<?php echo JText::_('MOD_SHLDAP_LOGIN_FORGOT_YOUR_USERNAME'); ?></a>
		</li>
		<li>
			<a href="<?php echo JRoute::_('index.php?option=com_users&view=reset'); ?>">
			<?php echo JText::_('MOD_SHLDAP_LOGIN_FORGOT_YOUR_PASSWORD'); ?></a>
		</li>
	</ul>
	<?php if ($params->get('posttext')): ?>
		<div class="posttext">
		<p><?php echo $params->get('posttext'); ?></p>
		</div>
	<?php endif; ?>
</form>
<?php endif; ?>
