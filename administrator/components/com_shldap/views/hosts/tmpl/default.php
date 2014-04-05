<?php
/**
 * PHP Version 5.3
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('behavior.multiselect');

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
$canEdit	= $user->authorise('core.admin', 'com_shldap');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$ordering 	= ($listOrder == 'ordering');
$saveOrder 	= ($listOrder == 'ordering' && $listDirn == 'asc');

if ($version->isCompatible('3.0.0'))
{
	if ($saveOrder)
	{
		$saveOrderingUrl = 'index.php?option=com_shldap&task=hosts.saveOrderAjax&tmpl=component';
		JHtml::_('sortablelist.sortable', 'articleList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
	}

	$sortFields = $this->getSortFields();
}
?>
<?php if ($version->isCompatible('3.0.0')) : ?>
<script type="text/javascript">
	Joomla.orderTable = function()
	{
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>')
		{
			dirn = 'asc';
		}
		else
		{
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<?php endif;?>
<form action="<?php echo JRoute::_('index.php?option=com_shldap&view=hosts');?>" method="post" name="adminForm" id="adminForm">

	<fieldset id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_SHLDAP_ITEMS_SEARCH_DESCRIPTION'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_SHSHLDAP_ITEMS_SEARCH_DESCRIPTION'); ?>" />
		</div>
		<div class="btn-group pull-left">
			<button class="btn hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i> <?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><i class="icon-remove"></i> <?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
	</fieldset>
	<div class="clearfix"> </div>

	<table class="adminlist table table-striped" id="articleList">
		<thead>
			<tr>
				<?php if ($version->isCompatible('3.0.0')) : ?>
				<th width="1%" class="nowrap center hidden-phone">
					<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
				</th>
				<?php endif; ?>
				<th width="20" class="center">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th width="20%" class="left">
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_HOSTS_NAME_KEY', 'name', $listDirn, $listOrder); ?>
				</th>
				<th class="left">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_HOST_KEY')); ?>
				</th>
				<th width="7%" class="center">
					<?php echo JHtml::_('grid.sort', 'JSTATUS', 'enabled', $listDirn, $listOrder); ?>
				</th>
				<?php if (!$version->isCompatible('3.0.0')) : ?>
				<th width="10%" class="center">
					<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ORDERING', 'ordering', $listDirn, $listOrder); ?>
					<?php if (($canEdit && $this->editable) && $saveOrder) :?>
						<?php echo JHtml::_('grid.order',  $this->items, 'filesave.png', 'hosts.saveorder'); ?>
					<?php endif; ?>
				</th>
				<?php endif; ?>
				<th width="7%" class="center">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_DEFAULT_KEY')); ?>
				</th>
				<th width="8%" class="center">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_USERS_KEY')); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="7" class="nowrap">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php if (count($this->items)) : ?>
			<?php foreach ($this->items as $i => $item): ?>
				<tr class="row<?php echo $i % 2; ?>">
					<?php if ($version->isCompatible('3.0.0')) : ?>
					<td class="order nowrap center hidden-phone">
						<?php
						$iconClass = '';
						if (!($canEdit && $this->editable))
						{
							$iconClass = ' inactive';
						}
						elseif (!$saveOrder)
						{
							$iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
						}
						?>
						<span class="sortable-handler<?php echo $iconClass ?>">
							<i class="icon-menu"></i>
						</span>
						<?php if (($canEdit && $this->editable) && $saveOrder) : ?>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
						<?php endif; ?>
					</td>
					<?php endif; ?>
					<td class="center">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td>
						<?php if ($canEdit) : ?>
							<?php if ($this->editable && $item->checked_out) : ?>
								<?php echo JHtml::_('jgrid.checkedout', $i, $item->name, $item->checked_out_time, 'hosts.', 1); ?>
							<?php endif; ?>
							<a href="<?php echo JRoute::_('index.php?option=com_shldap&task=host.edit&id='.$item->id);?>" title="<?php echo $this->escape($item->name); ?>">
							 <?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?>
							</a>
						<?php else : ?>
							<?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $this->escape($item->host . ':' . $item->port); ?>
					</td>
					<td class="center">
						<?php echo JHtml::_('jgrid.published', $item->enabled, $i, 'hosts.', ($canEdit && $this->editable)); ?>
					</td>
					<?php if (!$version->isCompatible('3.0.0')) : ?>
					<td class="order center">
					<?php if ($canEdit && $this->editable) : ?>
						<?php if ($saveOrder) :?>
							<?php if ($listDirn == 'asc') : ?>
								<span><?php echo $this->pagination->orderUpIcon($i, 1, 'hosts.orderup', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
								<span><?php echo $this->pagination->orderDownIcon($i, $this->pagination->total, 1, 'hosts.orderdown', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
							<?php elseif ($listDirn == 'desc') : ?>
								<span><?php echo $this->pagination->orderUpIcon($i, 1, 'hosts.orderdown', 'JLIB_HTML_MOVE_UP', $ordering); ?></span>
								<span><?php echo $this->pagination->orderDownIcon($i, $this->pagination->total, 1, 'hosts.orderup', 'JLIB_HTML_MOVE_DOWN', $ordering); ?></span>
							<?php endif; ?>
						<?php endif; ?>
						<?php $disabled = $saveOrder ?  '' : 'disabled="disabled"'; ?>
						<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text-area-order" />
					<?php else : ?>
						<?php echo $item->ordering; ?>
					<?php endif; ?>
					</td>
					<?php endif; ?>
					<td class="center">
						<?php echo JHtml::_('jgrid.isDefault', $item->default, $i, 'hosts.', $canEdit && (!$item->default)); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->users); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php else : ?>
			<tr class="row0">
				<td class="center" colspan="7">
					<?php echo JText::_('COM_SHLDAP_HOSTS_NO_HOSTS'); ?>
				</td>
			</tr>
			<?php endif; ?>
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
