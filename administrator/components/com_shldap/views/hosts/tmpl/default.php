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
$canEdit	= $user->authorise('core.edit', 'com_shldap');

?>
<form action="<?php echo JRoute::_('index.php?option=com_shldap&view=hosts');?>" method="post" name="adminForm" id="adminForm">

	<div class="clearfix"> </div>

	<table class="adminlist table table-striped">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th width="20%">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_NAME_KEY')); ?>
				</th>
				<th>
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_HOST_KEY')); ?>
				</th>
				<th width="12%">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_ATTR_UID_KEY')); ?>
				</th>
				<th width="12%">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_ATTR_NAME_KEY')); ?>
				</th>
				<th width="12%">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_ATTR_EMAIL_KEY')); ?>
				</th>
				<th width="8%">
					<?php echo $this->escape(JText::_('COM_SHLDAP_HOSTS_USERS_KEY')); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="7" class="nowrap">
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
							<a href="<?php echo JRoute::_('index.php?option=com_shldap&task=host.edit&id='.$item->id);?>" title="<?php echo $this->escape($item->name); ?>">
							<?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?></a>
						<?php else : ?>
							<?php echo $this->escape(str_replace(JURI::root(), '', $item->name)); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $this->escape($item->host . ':' . $item->port); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->attribute_uid); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->attribute_name); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->attribute_email); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->users); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>

</form>
