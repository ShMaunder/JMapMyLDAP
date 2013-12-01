<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shconfig
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
require_once JPATH_COMPONENT . '/views/settings/header.php';

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (document.formvalidator.isValid(document.id('settings-form')))
		{
			Joomla.submitform(task, document.getElementById('settings-form'));
		}
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_shconfig&task=settings.edit&layout=sso'); ?>" method="post" name="adminForm" id="settings-form" class="form-validate">
	<div class="row-fluid">
	<div class="width-50 fltlft span6 form-horizontal">
		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_SHCONFIG_SETTINGS_SSO_TITLE'); ?></legend>
			<div class="adminformlist tab-content">
				<div class="tab-pane active" id="details">
				<?php foreach ($this->form->getFieldset('sso') as $field) : ?>
					<?php if (!$field->hidden) : ?>
						<div class="control-group"><?php echo $field->label; ?>
						<?php echo $field->input; ?></div>
					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="width-50 fltrt span6 form-horizontal">
		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_SHCONFIG_SETTINGS_SSO_INFO_TITLE'); ?></legend>
			<div class="adminformlist tab-content">
				<div class="tab-pane active" id="details">
					<div id="sso-info"><?php echo JText::_('COM_SHCONFIG_SETTINGS_SSO_INFO_TEXT'); ?></div>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="clearfix"> </div>

	<div>
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
	</div>
</form>
