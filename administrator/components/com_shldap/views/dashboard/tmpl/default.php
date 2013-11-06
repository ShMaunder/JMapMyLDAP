<?php

defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canEdit	= $user->authorise('core.edit', 'com_plugins');

$gridState = array(array('task' => '', 'inactive_class' => 'unpublish'), array('task' => '', 'inactive_class' => 'publish'));

?>

<div class="width-100 span12">

<div>
<fieldset class="adminform">
<legend><?php echo JText::sprintf('COM_SHLDAP_DASHBOARD_BIND_TESTS_TITLE'); ?></legend>
<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="8%" class="center">
				<?php echo $this->escape(JText::_('COM_SHLDAP_DASHBOARD_HEADING_CONFIG_CONNECT')); ?>
			</th>
			<th width="8%" class="center">
				<?php echo $this->escape(JText::_('COM_SHLDAP_DASHBOARD_HEADING_CONFIG_BIND')); ?>
			</th>
			<th width="20%" class="left">
				<?php echo $this->escape(JText::_('COM_SHLDAP_DASHBOARD_HEADING_CONFIG_NAME')); ?>
			</th>
			<th class="left">
				<?php echo $this->escape(JText::_('COM_SHLDAP_DASHBOARD_HEADING_CONFIG_HOST')); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="4">
			</td>
		</tr>
	</tfoot>
	<tbody>
		<?php if (is_array($this->binds)) : ?>
			<?php if (count($this->binds)) : ?>
			<?php foreach ($this->binds as $i => $bind) : ?>
			<tr class="row<?php echo $i % 2; ?>">
				<td class="center">
					<?php echo JHtml::_('jgrid.state', $gridState, $bind->connect, $i, 'hosts.', false); ?>
				</td>
				<td class="center">
					<?php echo JHtml::_('jgrid.state', $gridState, isset($bind->bind), $i, 'hosts.', false); ?>
				</td>
				<td class="left">
					<?php echo $this->escape($bind->name); ?>
				</td>
				<td class="left">
					<?php echo $this->escape($bind->host . ':' . $bind->port); ?>
				</td>
			</tr>
			<?php endforeach; ?>
			<?php else : ?>
			<tr class="row0">
				<td class="center" colspan="4">
					<?php echo JText::_('COM_SHLDAP_DASHBOARD_NO_HOSTS'); ?>
				</td>
			</tr>
			<?php endif; ?>

		<?php else : ?>
			<tr><td colspan="4">An error occurred: <?php echo $this->binds; ?></td></tr>
		<?php endif; ?>
	</tbody>
</table>
</fieldset>
</div>

<div class="clearfix"> </div>

<br />

<div>
<fieldset class="adminform">
<legend><?php echo JText::sprintf('COM_SHLDAP_DASHBOARD_PLUGINS_TITLE'); ?></legend>
<form action="<?php echo JRoute::_('index.php?option=com_shldap&view=dashboard');?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar" class="btn-toolbar">
		<div class="filter-search btn-group pull-left">
			<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_SHLDAP_DASHBOARD_SEARCH_DESCRIPTION'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_SHLDAP_DASHBOARD_SEARCH_DESCRIPTION'); ?>" />
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
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_DASHBOARD_HEADING_NAME', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th width="20%">
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_DASHBOARD_HEADING_ELEMENT', 'a.element', $listDirn, $listOrder); ?>
				</th>
				<th width="5%">
					<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.enabled', $listDirn, $listOrder); ?>
				</th>
				<th width="10%">
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_DASHBOARD_HEADING_INDEX_TYPE', 'a.type_id', $listDirn, $listOrder); ?>
				</th>
				<th width="10%">
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_DASHBOARD_HEADING_VERSION', 'a.version', $listDirn, $listOrder); ?>
				</th>
				<th width="20%">
					<?php echo JHtml::_('grid.sort', 'COM_SHLDAP_DASHBOARD_HEADING_AUTHOR', 'a.author', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6" class="nowrap">
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ($this->items as $i => $item): ?>
				<?php $manifest = json_decode($item->manifest_cache); ?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="left">
						<?php JFactory::getLanguage()->load($item->name . '.sys', JPATH_ADMINISTRATOR); ?>
						<?php if ($canEdit) :
							if ($item->checked_out) :
								echo JHtml::_('jgrid.checkedout', $i, JText::_('COM_SHLDAP_DASHBOARD_GOTO_PLUGIN'), $item->checked_out_time, 'plugins.');
							endif;

							echo '<a href="' . JRoute::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . (int) $item->extension_id) . '">';
							echo $this->escape(JText::_($item->name));
							echo '</a>';
						else :
							echo $this->escape($item->name);
						endif; ?>
					</td>
					<td class="left">
						<?php echo $this->escape($item->name); ?>
					</td>
					<td class="center">
						<?php echo JHtml::_('jgrid.published', $item->enabled, 0, 'plugins.', 0); ?>
					</td>
					<td class="center">
						<?php echo $this->escape($item->folder); ?>
					</td>
					<td class="center">
						<?php echo $this->escape(isset($manifest->version) ? $manifest->version : JText::_('COM_SHLDAP_DASHBOARD_DEFAULT_VALUE')); ?>
					</td>
					<td class="center">
						<?php echo $this->escape(isset($manifest->author) ? $manifest->author : JText::_('COM_SHLDAP_DASHBOARD_DEFAULT_VALUE')); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>

</form>
</fieldset>
</div>
</div>

