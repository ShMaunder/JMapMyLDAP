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
JHtml::_('behavior.keepalive');
?>

<form action="<?php echo JRoute::_('index.php?option=com_shconfig&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate">

	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo JText::sprintf('COM_SHCONFIG_ITEM_CONFIG_TITLE'); ?></legend>
			<ul class="adminformlist">
				<?php foreach ($this->form->getFieldset('shconfig') as $field) : ?>
					<?php if (!$field->hidden) : ?>
						<li><?php echo $field->label; ?>
						<?php echo $field->input; ?></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		</fieldset>
	</div>

	<div class="width-40 fltrt">
		<fieldset class="adminform">
			<legend><?php echo JText::sprintf('COM_SHCONFIG_ITEM_IMPORTANT_TITLE'); ?></legend>
			<ul class="adminformlist">
				<li><?php echo JText::sprintf('COM_SHCONFIG_ITEM_SHORT_DESCRIPTION'); ?></li>
				<?php if ($this->item->id) : ?>
					<li><br /><?php echo JText::_('COM_SHCONFIG_ITEM_URL_DESC'); ?>
					<a href="<?php echo JText::sprintf('COM_SHCONFIG_ITEM_URL', $this->escape($this->item->name)); ?>">
					<?php echo JText::sprintf('COM_SHCONFIG_ITEM_URL', $this->escape($this->item->name)); ?></a></li>
				<?php endif; ?>
			</ul>
		</fieldset>
	</div>
	<div class="clr"></div>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->get('return', '', 'cmd');?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
