<?php
/**
 * LDAP Debug using the Joomla framework. Place this file in the root Joomla
 * directory. The following directories should be present:
 * /libraries/joomla
 * /libraries/shmanic/ldap
 *
 * Do NOT leave this file executable on a public web server.
 *
 * PHP Version 5.3
 *
 * @package    Shmanic.Libraries
 * @author     Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright  Copyright (C) 2011-2012 Shaun Maunder. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://shmanic.com/tool/jmapmyldap/?id=4&doc=ver-1-auth-debug-method
 */

define('VERSION', '2.0.0');

/* *****************************************************
 * ********** Importing Joomla Platform ****************
 * *****************************************************
 */

// Prevent against direct access of PHP files
define('_JEXEC', 1);

// Override PHP error output
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

// Do all the Joomla global variables
if (!defined('JPATH_PLATFORM'))
{
	define('JPATH_PLATFORM', realpath(__DIR__) . '/libraries');
}
if (!defined('JPATH_BASE'))
{
	define('JPATH_BASE', realpath(JPATH_PLATFORM . '/tmp'));
}
if (!defined('JPATH_ROOT'))
{
	define('JPATH_ROOT', realpath(JPATH_BASE));
}
if (!defined('JPATH_CACHE'))
{
	define('JPATH_CACHE', JPATH_BASE . '/cache');
}
if (!defined('JPATH_CONFIGURATION'))
{
	define('JPATH_CONFIGURATION', JPATH_BASE);
}
if (!defined('JPATH_MANIFESTS'))
{
	define('JPATH_MANIFESTS', JPATH_BASE . '/manifests');
}
if (!defined('JPATH_PLUGINS'))
{
	define('JPATH_PLUGINS', JPATH_BASE . '/plugins');
}
if (!defined('JPATH_THEMES'))
{
	define('JPATH_THEMES', JPATH_BASE . '/themes');
}

// Import the platform.
require_once JPATH_PLATFORM . '/import.php';

// Setup the Joomla configuration
jimport('joomla.application.component.helper');
JFactory::getConfig(JPATH_ROOT . '/configuration.php');


/* *****************************************************
 * ************** HTML Header Output *******************
 * *****************************************************
 */
ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>JMMLdap Debug</title>
<style type="text/css">
	* {margin:0; padding:0;}
	html {font: 82.5%/1 Helvetica, Arial, Tahoma, sans-serif;}
	html, body {height:100%;}
	table.single {margin:0 auto; margin-top:10px; margin-bottom:10px; border:#000 1px solid;}
	table.single td {padding:4px 2px 4px 2px; border:#666 1px solid;}
	input.standard {font-size:0.8em; width:99%; background-image: -webkit-gradient(linear, left bottom, left top, color-stop(0, rgb(255,255,255)), color-stop(1, rgb(240,240,240))); background-image: -moz-linear-gradient(center bottom,rgb(255,255,255) 0%,rgb(240,240,240) 100%);}
	input.checkbox {font-size:0.8em; margin:4px 0;}
	button {padding:2px;}
	#introduction {margin:0 auto;margin-top:40px;text-align:center;font-size:1.3em;width:70%;border:#333 1px solid;border-radius:20px;}
	#introduction p {margin-top:13px;}
	button {width:100px;height:24px;}
	.roundBorder {border:#333 1px solid;border-radius:10px;}
	#navigation {margin:0 auto;width:40%;margin-top:24px;height:24px;}
	tr.header {background-color:#ccc;height:24px;}
	tr.header th {border-top:#333 1px solid;}
	hr {margin:4px 0;color:#AAA;}
	#tabs {height:26px;}
	#tabs ul {list-style: none;}
	#tabs ul li {float:left; }
	#tabs ul li a {display:block; padding:4px 10px; color:#025A8D; text-decoration:none;}
	#tabs ul li a:hover {background-color:#111; color:#fff;}
</style>
<script type="text/javascript">

function getResults(type)
{
	var xmlhttp;
	var postVars;

	document.getElementById("results").innerHTML="Fetching result...";

	if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else { // code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function()
		{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		  {
		  document.getElementById("results").innerHTML=xmlhttp.responseText;
		  }
		}


	postVars = getPostVars('authForm');

	if(type==2) {
		postVars += '&' + getPostVars('mappingForm');
	}

	xmlhttp.open("POST", "?result=" + type, true); //sent back to the same page
	xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Content-length", postVars.length);
	xmlhttp.setRequestHeader("Connection", "close");
	xmlhttp.send(postVars);
}


function getPostVars(form)
{
	var out = '';
	var elements = document.getElementById(form).elements;

	for(var i=0; i<elements.length; i++) {
		if(elements[i].type == 'checkbox') {
			var value = elements[i].checked == true ? 1 : 0;
			out += elements[i].id + '=' + value + '&';
		} else if(elements[i].type == 'text') {
			out += elements[i].id + '=' + escape(elements[i].value) + '&';
		} else if(elements[i].type == 'password') {
			out += elements[i].id + '=' + escape(elements[i].value) + '&';
		}
	}

	out = out.substr(0, out.length-1);

	return out;
}


function showAuth()
{
	document.getElementById("mappingContainer").style.display = 'none';
	document.getElementById("authContainer").style.display = 'block';

	document.getElementById("mappingTab").style.fontWeight = '';
	document.getElementById("authTab").style.fontWeight = 'bold';
}


function showMapping()
{
	document.getElementById("authContainer").style.display = 'none';
	document.getElementById("mappingContainer").style.display = 'block';

	document.getElementById("authTab").style.fontWeight = '';
	document.getElementById("mappingTab").style.fontWeight = 'bold';
}

</script>
</head>
<body>
<div style="min-height:100%; height:auto !important; height:100%; margin:0 auto;">
<div style="background-color:#000000; background-image: -webkit-gradient(linear, left bottom, left top, color-stop(0, rgb(0,0,0)), color-stop(1, rgb(110,110,110))); background-image: -moz-linear-gradient(center bottom,rgb(0,0,0) 0%,rgb(110,110,110) 100%); color:#FFFFFF;">
<h4 style="padding:4px;">Shmanic.com</h4>
<h3 style="padding:0px 4px 4px 8px;">JMMLdap Debug V<?php echo VERSION; ?> [Joomla Integrated]</h3>
</div>

<?php
$htmlHeader = ob_get_contents();
ob_end_clean();


/* *****************************************************
 * *************** HTML Form Output ********************
 * *****************************************************
 */
ob_start();
?>
<div style="margin:10px;">
<div id="tabs">
<ul>
 <li><a href="#" onclick="showAuth()" id="authTab" style="font-weight:bold;">Authentication</a></li>
 <li><a href="#" onclick="showMapping()" id="mappingTab">Group Mapping</a></li>
</ul>
</div>
<div id="authContainer" style="width:40%;float:left;height:100%;">

<form id="authForm">
<hr />
	<input type="checkbox" id="chkV3" class="checkbox" /> LDAP V3<br />
	<input type="checkbox" id="chkTLS" class="checkbox" /> Start TLS<br />
	<input type="checkbox" id="chkRef" class="checkbox" /> Follow Referrals<br /><br />
<hr />
	Host: <input type="text" id="txtHost" class="standard" /><br />
	Port: <input type="text" id="txtPort" class="standard" value="389" /><br /><br />
<hr />
	Proxy User: <input type="text" id="connUser" class="standard" /><br />
	Proxy Password: <input type="password" id="connPass" class="standard" /><br /><br />
<hr />
	<input type="checkbox" id="chkSearch" class="checkbox" /> Use Search<br />
	Base DN: <input type="text" id="baseDn" class="standard" /><br />
	User DN/Filter: <input type="text" id="userQry" class="standard" /><br /><br />
<hr />
	Map User ID: <input type="text" id="mapUid" class="standard" value="uid" /><br />
	Map Full Name: <input type="text" id="mapName" class="standard" value="fullName" /><br />
	Map Email: <input type="text" id="mapEmail" class="standard" value="mail" /><br /><br />
<hr />
	Test User: <input type="text" id="testUser" class="standard" /><br />
	Test Password: <input type="password" id="testPass" class="standard" /><br />
<hr />
<button type="button" onclick="getResults(1)">Show Result</button>
</form>
</div>

<div id="mappingContainer" style="width:40%;float:left;height:100%;display:none;">

<form id="mappingForm">
<hr />
	<p>Ensure the authentication results are successful before continuing! </p><br />
	<p>This tab is only a guide (i.e. it won't give specific parameters for group mapping). You only need either the forward or reverse lookup configured, not both.</p><br />
<hr />
	<p>Use the following box to print all attributes for a group. This is optional and is only required for the correct <strong>reverse lookup attribute</strong>. </p><br />
	Group DN: <input type="text" id="groupDN" class="standard" value="" /><br /><br />
<hr />
	<p><strong>Forward Lookup: </strong> if the group membership attribute prints out on the authentication results then populate the following text box with the attribute name (this is memberOf for Active Directory and usually groupMembership for others). </p><br />
	Lookup Attribute: <input type="text" id="lookupFAttribute" class="standard" value="groupMembership" /><br /><br />

<hr />
	<p><strong>Reverse Lookup: </strong> if no group membership attribute is printed from the authentication results then a reverse lookup is required. Populate the following boxes to test reverse lookup configuration. You can use the 'Group DN' text box to print out all attributes for a specific group. </p><br />
	Lookup Attribute: <input type="text" id="lookupRAttribute" class="standard" value="member" /><br />
	Lookup Member: <input type="text" id="lookupMember" class="standard" value="dn" /><br /><br />
<hr />
<button type="button" onclick="getResults(2)">Show Result</button>
</form>
</div>

<div id="results" style="float:right;width:56%;">

</div>
<div class="clear:both"></div>
</div>
<?php
$htmlForm = ob_get_contents();
ob_end_clean();


/* *****************************************************
 * ************** HTML Footer Output *******************
 * *****************************************************
 */
ob_start();
?>

</body>
</html>
<?php
$htmlFooter = ob_get_contents();
ob_end_clean();


/* *****************************************************
 * ************** HTML Result Output *******************
 * *****************************************************
 */
$htmlContent = '';

jimport('joomla.application.input');
$input = new JInput;

if ($reqResult = $input->get('result', 0, 'int'))
{

	ob_start();

	/* *****************************************************
 	 * *************** LDAP Parameters *********************
	 * *****************************************************
 	 */
	$config = array(
		'use_v3' => $input->get('chkV3', false, 'boolean'),
		'negotiate_tls' => $input->get('chkTLS', false, 'boolean'),
		'use_referrals' => $input->get('chkRef', false, 'boolean'),

		'host' => $input->get('txtHost', null, 'string'),
		'port' => $input->get('txtPort', null, 'string'),

		'proxy_username' => $input->get('connUser', null, 'string'),
		'proxy_password' => $input->get('connPass', null, 'string'),

		'use_search' => $input->get('chkSearch', false, 'boolean'),
		'base_dn' => $input->get('baseDn', null, 'string'),
		'user_qry' => $input->get('userQry', null, 'string'),

		'ldap_uid' => $input->get('mapUid', null, 'string'),
		'ldap_fullname' => $input->get('mapName', null, 'string'),
		'ldap_email' => $input->get('mapEmail', null, 'string')
	);

	/*
	 * Get the test users. The test user is used to test what
	 * would happen if it were to be typed in as the Joomla user.
	 */
	$authUsername = $input->get('testUser', null, 'string');
	$authPassword = $input->get('testPass', null, 'string');

	try
	{
		/* *****************************************************
 		 * ************** Common LDAP Tasks ********************
 		 * *****************************************************
 		 */

		// Ensure the Shmanic platform have been loaded
		if (!defined('SH_PLATFORM'))
		{
			require_once JPATH_PLATFORM . '/shmanic/import.php';
		}

		// Import JMapMyLDAP
		if (!shImport('ldap'))
		{
			throw new Exception('Failed to import JMapMyLDAP');
		}

		$client = new SHLdap($config);

		// Connect to Ldap
		if ($client->connect() === false)
		{
			// Internal Ldap client error
			throw new Exception($client->getError());
		}

		// Get the user dn
		$dn = $client->getUserDN($authUsername, $authPassword, true);
		if ($dn === false)
		{
			// Internal Ldap client error
			throw new Exception($client->getError());
		}

		if (empty($dn))
		{
			// Failed to find test user
			$msg = ($config->use_search && (!$config->proxy_username || !$config->proxy_password)) ?
				'Did you forget to set the \'Connect User\' and \'Connect Password\'? Currently it is connecting as anonymous.' :
				null;

			throw new Exception('Failed: cannot find the authenticating user. ' . $msg);
		}

		echo "Successfully built test user distinguished name '{$dn}'";

		echo '<br /><br />Attempting to retrieve all user attributes then process the results request...<br /><br />';

		// Read the test users attributes
		$read = $client->read($dn);

		if ($read === false)
		{
			// Internal Ldap client error
			throw new Exception('Failed to read user attributes: ' . $client->getError());
		}

		if (!$read->countEntries() > 0)
		{
			// No entries found
			throw new Exception('No attributes found for test user.');
		}

		echo 'Successfully found test user attributes.<br /><br />';

		if ($uid = $read->getValue(0, $client->getUid(), 0))
		{
			echo "User ID: {$uid} <br />";
		}
		else
		{
			echo '<p><strong>Invalid Map User ID.</strong></p>';
		}

		if ($fullname = $read->getValue(0, $client->getFullname(), 0))
		{
			echo "Full Name: {$fullname} <br />";
		}
		else
		{
			echo '<p><strong>Invalid Map Full Name.</strong></p>';
		}

		if ($email = $read->getValue(0, $client->getEmail(), 0))
		{
			echo "Email: {$email} <br />";
		}
		else
		{
			echo '<p><strong>Invalid Map Email. If your LDAP server does not use emails, then use a \'fake\' email.</strong></p>';
		}

		if ($reqResult === 1)
		{
			/* *****************************************************
 			 * ************** Authentication Tab *******************
 			 * *****************************************************
 			 */

			echo '<div style="margin:10px 0; padding:2px; background-color:#EAEAEA;display:block;border:#AAA 1px solid;"><table>';

			echo '<tr style="background-color:#CCC;"><th>LDAP Attribute</th><th>Value(s)</th></tr>';

			/*
			 * This section loops around each attribute found in the Ldap
			 * query then prints it onto screen within a table.
			 */
			for ($i = 0; $i < $read->countAttributes(0); ++$i)
			{
				echo '<tr><td style="border-top:#CCC 1px solid;"><strong>';
				echo $read->getAttributeKeyAtIndex(0, $i);
				echo '</strong></td><td style="border-top:#CCC 1px solid;">';

				$values = $read->getAttributeAtIndex(0, $i);

				foreach ($values as $key => $value)
				{
					echo "[{$key}] {$value} <br />";
				}

				echo '</td></tr>';
			}

			echo '</table></div>';

		}
		elseif ($reqResult === 2)
		{
			/* *****************************************************
 			 * *************** Group Mapping Tab *******************
 			 * *****************************************************
 			 */
					/* Group mapping request result - print out everything for
					 * the groups results.
					 */

					/*** Parameters to edit ***/
					$lookupFAttribute = getRequest('lookupFAttribute');

					$lookupRAttribute = getRequest('lookupRAttribute');
					$lookupMember  = getRequest('lookupMember');

					$groupDN = getRequest('groupDN');
					/*** End of parameters ***/

					// Group DN Helper
					if ($groupDN)
					{
						echo '<br /><u>Group DN</u>';
						echo '<br />Attempting to get attributes for the Group DN...<br /> ';

						$result = ldapRead($ldapconn, $basedn, $groupDN);

						if ($result && isset($result[0]) && $groupDNResult = $result[0])
						{
							echo 'Found the Group DN. Printing out attributes: <br />';
							echo '<div style="margin:10px 0; padding:2px; background-color:#EAEAEA;display:block;border:#AAA 1px solid;"><table>';

							echo '<tr style="background-color:#CCC;"><th>LDAP Attribute</th><th>Value(s)</th></tr>';
							foreach ($groupDNResult as $key=>$val)
							{
								echo '<tr><td style="border-top:#CCC 1px solid;"><strong>' . $key . '</strong></td><td style="border-top:#CCC 1px solid;">';
								print_r($val);
								echo '</td></tr>';
							}
							echo '</table></div>';

						} else {
							echo '<strong>Failed: couldn\'t find the group dn.</strong>';
						}

					}

					// ** Forward Lookup **
					if ($lookupFAttribute)
					{
						echo '<br /><u>Forward Lookup</u>';
						echo '<br />Attempting a forward lookup...<br /> ';

						if (isset($data[$lookupFAttribute]))
						{
							if (count($data[$lookupFAttribute]))
							{
								echo 'Found the forward lookup attribute and the following groups will be mapped:<br /> ';

								echo '<div style="margin:10px 0; padding:2px; background-color:#EAEAEA;display:block;border:#AAA 1px solid;"><table>';

								foreach ($data[$lookupFAttribute] as $group)
								{
									echo '<tr><td style="border-top:#CCC 1px solid;">' . $group . '</td></tr>';
								}

								echo '</table></div>';

							} else {
								echo 'Found the forward lookup attribute however, it currently has no groups.';
							}
						} else {
							echo '<strong>failed: cannot use forward lookup using the attribute ' . $lookupFAttribute . '</strong>';
						}
					}


					// ** Reverse Lookup **
					if ($lookupRAttribute)
					{
						echo '<br /><u>Reverse Lookup</u>';
						echo '<br />Attempting a reverse lookup...<br /> ';

						if (isset($data[$lookupMember]))
						{

							$lookupMemberValue = $lookupMember!='dn' ? $data[$lookupMember][0] : $data[$lookupMember];

							$search = "($lookupRAttribute=$lookupMemberValue)";
							echo 'Searching LDAP for ' . $search . '<br />';
							$result = ldap_search($ldapconn, $basedn, $search);

							if ($result)
							{

								if ($entries = getEntries($ldapconn, $result))
								{

									echo 'Found the reverse lookup attribute and the following groups will be mapped:<br /> ';

									echo '<div style="margin:10px 0; padding:2px; background-color:#EAEAEA;display:block;border:#AAA 1px solid;"><table>';

									foreach ($entries as $group)
									{
										echo '<tr><td style="border-top:#CCC 1px solid;">' . $group['dn'] . '</td></tr>';
									}

									echo '</table></div>';


								} else {
									echo '<strong>Failed: couldn\'t get a result for reverse lookup.</strong>';
								}

							} else {
								echo '<strong>Failed: couldn\'t get a result for reverse lookup.</strong>';
							}


						} else {
							echo '<strong>Failed: the lookup member attribute doesn\'t exist. Use only attributes that are listed from the authentication results.</strong>';
						}

					}
				}
				else
				{
					throw new Exception('UNKNOWN RESULT REQUEST');
				}


	}
	catch (Exception $e)
	{
		echo '<p style="background-color:#FBB;display:block;padding:4px 0;font-weight:bold;">' . $e->getMessage() . '</p>';
	}

	$resultHTML = ob_get_contents();
	ob_clean();

	echo ' :: PHP LDAP Debug ' . VERSION . ' Script Started :: <br /><br />';
	echo $resultHTML;
	echo '<br /><br /> :: PHP LDAP Debug ' . VERSION . ' Script Finished :: <br /><br />';

} else {

	/* Output the HTML form and Javascript */
	echo $htmlHeader;
	echo $htmlForm;
	echo $htmlFooter;
}
