<?php
/**
 * @version		$Id: default.php 21595 2011-06-21 02:51:29Z dextercowley $
 * @package		Joomla.Administrator
 * @subpackage	com_redirect
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Include the component HTML helpers.
//JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
//JHtml::_('behavior.tooltip');
//JHtml::_('behavior.multiselect');

$counter	= 1;
$user		= JFactory::getUser();

foreach($this->items as &$item) {
	$item['state'] = $item['enabled'];
	if($item['enabled']==0) {
		$item['state'] = JHtml::_('jgrid.published', 0, 0, 'plugins.', 0) . ' ' . JText::_('JDISABLED');
	} elseif($item['enabled']==1) {
		$item['state'] = JHtml::_('jgrid.published', 1, 0, 'plugins.', 0) . ' ' . JText::_('JENABLED');
	} else {
		$item['state'] = JHtml::_('jgrid.published', 0, 0, 'plugins.', 0) . ' ' . JText::_('COM_LDAPADMIN_NOT_INSTALLED');
	}
}

// Displays a notice if PHP LDAP extension not loaded. Only on dash.
if(!$this->ldapExt) JERROR::raiseNotice('SOME_NUMBER', JText::_('COM_LDAPADMIN_LDAP_EXTENSION_ERROR'));

//die(JHtml::_('jgrid.published', 1, 5, 'plugins.', 1));

//$listOrder	= $this->escape($this->state->get('list.ordering'));
//$listDirn	= $this->escape($this->state->get('list.direction'));
?>
<table style="width:100%">
<tr valign="top">
<?php foreach($this->items as &$item): ?>
<?php if($counter % 3 == 0): ?>
<tr>
<?php endif; ?>
<td style="width:33%">
<div class="width-100">
<fieldset class="adminform">
 <legend><?php echo $item['name']; ?></legend>
 <table style="width:100%">
  <tr><td style="width:70px;padding:8px 0px;border-right:#ccc solid 1px;border-bottom:#ccc solid 1px;"><?php echo JText::_('JENABLED'); ?></td><td style="border-bottom:#ccc solid 1px;"><?php echo $item['state']; ?></td></tr>
  <tr><td style="width:70px;padding:8px 0px;border-right:#ccc solid 1px;border-bottom:#ccc solid 1px;"><?php echo JText::_('JVERSION'); ?></td><td style="border-bottom:#ccc solid 1px;"><?php echo $item['version']; ?></td></tr>
  <tr><td style="width:70px;padding:8px 0px;border-right:#ccc solid 1px;<?php if($item['controller']): ?>border-bottom:#ccc solid 1px;<?php endif; ?>"><?php echo JText::_('JGLOBAL_DESCRIPTION'); ?></td><td <?php if($item['controller']): ?>style="border-bottom:#ccc solid 1px;"<?php endif; ?>><?php echo $item['description']; ?></td></tr>
  <?php if($item['controller']): ?>
  <tr><td style="width:70px;padding:8px 0px;border-right:#ccc solid 1px;"><?php echo JText::_('JACTION_ADMIN'); ?></td><td><a style="background-color:#ededed;padding:4px;border:1px solid #aaa;" href="<?php echo JRoute::_('index.php?option=com_ldapadmin&task=' . $item['controller'] . '.display'); ?>"><?php echo JText::_('COM_LDAPADMIN_PLUGIN_CONFIG'); ?></a></td></tr>
  <?php endif; ?>
 </table>
</fieldset>
</div>
</td>
<?php if($counter == count($this->items) && $counter % 3 != 2): ?>
<?php for($i=$counter; $i%3==2; $i++) { echo '<td><fieldset class="adminform"></fieldset></td>'; } ?>
<?php endif; ?>
<?php if($counter % 3 == 2 || $counter == count($this->items)): ?>
</tr>
<?php endif; ?>
<?php $counter++; endforeach; ?>

</table>
