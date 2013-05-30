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

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');

?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'item.cancel' || document.formvalidator.isValid(document.id('item-form')))
		{
			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_shconfig&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
	<div class="row-fluid">
	<div class="width-60 fltlft span8 form-horizontal">
		<fieldset class="adminform">
			<legend><?php echo JText::sprintf('COM_SHCONFIG_ITEM_CONFIG_TITLE'); ?></legend>
			<div class="adminformlist tab-content">
				<div class="tab-pane active" id="details">
				<?php foreach ($this->form->getFieldset('shconfig') as $field) : ?>
					<?php if (!$field->hidden) : ?>
						<div class="control-group"><?php echo $field->label; ?>
						<?php echo $field->input; ?></div>
					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			</div>
		</fieldset>
	</div>

	<div class="width-40 fltrt span4">
		<fieldset class="adminform form-vertical">
			<legend><?php echo JText::sprintf('COM_SHCONFIG_ITEM_IMPORTANT_TITLE'); ?></legend>
			<div class="adminformlist">
				<div><?php echo JText::sprintf('COM_SHCONFIG_ITEM_SHORT_DESCRIPTION'); ?></div>
				<?php if ($this->item->id) : ?>
					<div><br /><?php echo JText::_('COM_SHCONFIG_ITEM_URL_DESC'); ?>
					<a href="<?php echo JText::sprintf('COM_SHCONFIG_ITEM_URL', $this->escape($this->item->name)); ?>">
					<?php echo JText::sprintf('COM_SHCONFIG_ITEM_URL', $this->escape($this->item->name)); ?></a></div>
				<?php endif; ?>
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
