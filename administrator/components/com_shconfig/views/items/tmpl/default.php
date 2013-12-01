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

$version = new JVersion;

if ($version->isCompatible('3.0.0'))
{
	JHtml::_('formbehavior.chosen', 'select');
	JHtml::_('bootstrap.tooltip');
}
else
{
	JHtml::_('behavior.tooltip');
}

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canEdit	= $user->authorise('core.edit', 'com_shconfig');

?>
<form action="<?php echo JRoute::_('index.php?option=com_shconfig&view=items');?>" method="post" name="adminForm" id="adminForm">

	<fieldset id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_SHCONFIG_ITEMS_SEARCH_DESCRIPTION'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_SHCONFIG_ITEMS_SEARCH_DESCRIPTION'); ?>" />
		</div>
		<div class="btn-group pull-left">
			<button class="btn hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i> <?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><i class="icon-remove"></i> <?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</fieldset>
	<div class="clearfix"> </div>

	<table class="adminlist table table-striped">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th width="20%">
					<?php echo JHtml::_('grid.sort', 'COM_SHCONFIG_ITEMS_HEADING_KEY', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th>
					<?php echo $this->escape(JText::_('COM_SHCONFIG_ITEMS_HEADING_VALUE')); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="3" class="nowrap">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($this->items as $i => $item): ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td>
						<?php if ($canEdit) : ?>
							<a href="<?php echo JRoute::_('index.php?option=com_shconfig&task=item.edit&id='.$item->id);?>" title="<?php echo $this->escape($item->name); ?>">
							<?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?></a>
						<?php else : ?>
							<?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $this->escape($item->value); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>

</form>
