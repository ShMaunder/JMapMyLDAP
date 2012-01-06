<?php 

defined('_JEXEC') or die;

JHTML::_('behavior.mootools'); /* to load mootools */
$ajax = "
   /* <![CDATA[ */
   window.addEvent('domready', function() {
        $('start_ajax').addEvent('click', function(e) {
            document.getElementById('ajax_container').innerHTML = \"Please wait...\";
            e.stop();    
            var url = 'index.php?option=com_ldapadmin&view=authenticaiton&task=authentication&format=raw';
            var x = new Request({
                url: url, 
                method: 'post', 
		onSuccess: function(responseText){
		    document.getElementById('ajax_container').innerHTML = responseText;
		}
            }).send();      //  To pass values :    }).send('country_id=' + document.getElementById('country_id').value );
        });
    })
    /* ]]> */
    " ;
$doc = & JFactory::getDocument();
$doc->addScriptDeclaration( $ajax );
?>

<div><a id="start_ajax" href="#">Click here</a> to start Ajax request</div>
<div id="ajax_container">
    Here is the ajax output
</div>