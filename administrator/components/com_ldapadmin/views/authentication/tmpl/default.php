<?php

defined('_JEXEC') or die;

?>

<table style="width:100%">
 <tr valign="top">
  <td style="width:20%">
  
   <div class="width-100">
    <fieldset class="adminform">
     <legend>Operations</legend>
     <table class="width-100">
      <tr><td><a href="#">Configuration</a></td></tr>
      <tr><td><a href="#">Quick Test</a></td></tr>
     </table>
    </fieldset>
   </div>
   
  </td>
  <td style="width:80%">
  
   <div class="width-100">
    <fieldset class="adminform">
     <legend>Operations</legend>
     <iframe src="<?php echo JRoute::_('index.php?option=com_ldapadmin&view=authentication&layout=configure&format=raw'); ?>" style="width:100%;height:400px;">
      <p>Your browser doesn't support iframes.</p>
     </iframe>
    </fieldset>
   </div>
  
  </td>
 </tr>
</table>