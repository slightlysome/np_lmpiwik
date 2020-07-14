<?php
/*
    LMPiwik Nucleus plugin
    Copyright (C) 2013 Leo (www.slightlysome.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	(http://www.gnu.org/licenses/gpl-2.0.html)
	
	See lmpiwik/help.html for plugin description, install, usage and change history.
*/
class NP_LMPiwik extends NucleusPlugin
{
	// name of plugin 
	function getName()
	{
		return 'LMPiwik';
	}

	// author of plugin
	function getAuthor()
	{
		return 'Leo (www.slightlysome.net)';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.slightlysome.net/nucleus-plugins/np_lmpiwik';
	}

	// version of the plugin
	function getVersion()
	{
		return '1.0.0';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
		return 'Integration with the open source web analytics platform Piwik.';
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			case 'SqlApi':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}
	
	function hasAdminArea()
	{
		return 1;
	}
	
	function getMinNucleusVersion()
	{
		return '360';
	}
	
	function getTableList()
	{	
		return 	array();
	}
	
	function getEventList() 
	{ 
		return array('AdminPrePageFoot', 'QuickMenu'); 
	}
	
	function install()
	{
		$sourcedataversion = $this->getDataVersion();

		$this->upgradeDataPerform(1, $sourcedataversion);
		$this->setCurrentDataVersion($sourcedataversion);
		$this->upgradeDataCommit(1, $sourcedataversion);
		$this->setCommitDataVersion($sourcedataversion);					
	}
	
	function event_AdminPrePageFoot(&$data)
	{
		// Workaround for missing event: AdminPluginNotification
		$data['notifications'] = array();
			
		$this->event_AdminPluginNotification($data);
			
		foreach($data['notifications'] as $aNotification)
		{
			echo '<h2>Notification from plugin: '.htmlspecialchars($aNotification['plugin'], ENT_QUOTES, _CHARSET).'</h2>';
			echo $aNotification['text'];
		}
	}
	
	function event_QuickMenu(&$data) 
	{
		global $member;

		if (!$member->isAdmin()) return;
			array_push($data['options'],
				array('title' => 'LMPiwik',
					'url' => $this->getAdminURL(),
					'tooltip' => 'Administer NP_LMPiwik'));
	}

	////////////////////////////////////////////////////////////
	//  Events
	function event_AdminPluginNotification(&$data)
	{
		global $member, $manager;
		
		$actions = array('overview', 'pluginlist', 'plugin_LMPiwik');
		$text = "";
		
		if(in_array($data['action'], $actions))
		{
			$globalpiwikenable = $this->getOption("globalpiwikenable");	
			
			if($globalpiwikenable <> 'yes')
			{
				$text .= '<p>Piwik tracking code is disabled in plugin options. If you have just installed the LM_LMPiwik plugin you need to set the plugin options for Piwik URL and website id, before you can enable the Piwik tracking code.</p>';
			}
			
			$piwikurl = trim($this->getOption("globalpiwikurl"));
			
			if(!$piwikurl)
			{
				$text .= '<p>The URL to your Piwik installation must be set in the LM_LMPiwik plugin options.</p>';
			}

			$websiteid = intVal($this->getOption("globalwebsiteid"));
			
			if(!$websiteid)
			{
				$text .= '<p>The Piwik website id must be set in the LM_LMPiwik plugin options.</p>';
			}

			$sourcedataversion = $this->getDataVersion();
			$commitdataversion = $this->getCommitDataVersion();
			$currentdataversion = $this->getCurrentDataVersion();
		
			if($currentdataversion > $sourcedataversion)
			{
				$text .= '<p>An old version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin files are installed. Downgrade of the plugin data is not supported. The correct version of the plugin files must be installed for the plugin to work properly.</p>';
			}
			
			if($currentdataversion < $sourcedataversion)
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is for an older version of the plugin than the version installed. ';
				$text .= 'The plugin data needs to be upgraded or the source files needs to be replaced with the source files for the old version before the plugin can be used. ';

				if($member->isAdmin())
				{
					$text .= 'Plugin data upgrade can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.';
				}
				
				$text .= '</p>';
			}
			
			if($commitdataversion < $currentdataversion && $member->isAdmin())
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is upgraded, but the upgrade needs to commited or rolled back to finish the upgrade process. ';
				$text .= 'Plugin data upgrade commit and rollback can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.</p>';
			}
		}
		
		if($text)
		{
			array_push(
				$data['notifications'],
				array(
					'plugin' => $this->getName(),
					'text' => $text
				)
			);
		}
	}

////////////////////////////////////////////////////////////
//  Handle vars

	function doSkinVar($skinType, $vartype, $templatename = '')
	{
		global $manager;

		$aArgs = func_get_args(); 
		$num = func_num_args();

		$aSkinVarParm = array();
		
		for($n = 3; $n < $num; $n++)
		{
			$parm = explode("=", func_get_arg($n));
			
			if(is_array($parm))
			{
				$aSkinVarParm[$parm['0']] = $parm['1'];
			}
		}

		if($templatename)
		{
			$template =& $manager->getTemplate($templatename);
		}
		else
		{
			$template = array();
		}

		switch (strtoupper($vartype))
		{
			case 'TRACKINGCODE':
				$this->doSkinVar_TrackingCode($skinType, $template, $aSkinVarParm);
				break;
			default:
				echo "Unknown vartype: ".$vartype;
		}
	}

	function doSkinVar_TrackingCode($skinType, &$template, $aSkinVarParm)
	{
		global $member, $blogid;
		
		$trackingcodetemplate = '<!-- Piwik -->
<script type="text/javascript"> 
  var _paq = _paq || [];
  _paq.push([\'trackPageView\']);
  _paq.push([\'enableLinkTracking\']);
  (function() {
    var u="<%piwikurl%>";
    _paq.push([\'setTrackerUrl\', u+\'piwik.php\']);
    _paq.push([\'setSiteId\', <%websiteid%>]);
    var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0]; g.type=\'text/javascript\';
    g.defer=true; g.async=true; g.src=u+\'piwik.js\'; s.parentNode.insertBefore(g,s);
  })();
</script>
<noscript><p><img src="<%piwikurl%>piwik.php?idsite=<%websiteid%>" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Code -->';

		if($member->isLoggedIn())
		{
			$memberpiwikenable = $this->getMemberOption($member->getID(), "memberpiwikenable");	
		}
		else
		{
			$memberpiwikenable = 'yes';
		}
		
		$globalpiwikenable = $this->getOption("globalpiwikenable");	
		
		$blogpiwikenable = $this->getBlogOption($blogid, "blogpiwikenable");	

		if(!($globalpiwikenable == 'no' || $blogpiwikenable == 'no' || $memberpiwikenable == 'no'))
		{
			$piwikurl = trim($this->getOption("globalpiwikurl"));
			$websiteid = intVal($this->getBlogOption($blogid, "blogwebsiteid"));
			
			if(!$websiteid)
			{
				$websiteid = intVal($this->getOption("globalwebsiteid"));
			}
			
			if($piwikurl && $websiteid)
			{
				if(substr($piwikurl, -1) <> '/')
				{
					$piwikurl .= '/';
				}

				$aVars = array( 'piwikurl' => $piwikurl, 'websiteid' => $websiteid);
				
				echo TEMPLATE::fill($trackingcodetemplate, $aVars);
			}
		}
	}

	////////////////////////////////////////////////////////////////////////
	// Plugin Upgrade handling functions
	function getCurrentDataVersion()
	{
		$currentdataversion = $this->getOption('currentdataversion');
		
		if(!$currentdataversion)
		{
			$currentdataversion = 0;
		}
		
		return $currentdataversion;
	}

	function setCurrentDataVersion($currentdataversion)
	{
		$res = $this->setOption('currentdataversion', $currentdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getCommitDataVersion()
	{
		$commitdataversion = $this->getOption('commitdataversion');
		
		if(!$commitdataversion)
		{
			$commitdataversion = 0;
		}

		return $commitdataversion;
	}

	function setCommitDataVersion($commitdataversion)
	{	
		$res = $this->setOption('commitdataversion', $commitdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getDataVersion()
	{
		return 1;
	}
	
	function upgradeDataTest($fromdataversion, $todataversion)
	{
		// returns true if rollback will be possible after upgrade
		$res = true;
				
		return $res;
	}
	
	function upgradeDataPerform($fromdataversion, $todataversion)
	{
		// Returns true if upgrade was successfull
		
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$this->createOption('currentdataversion', 'currentdataversion', 'text','0', 'access=hidden');
					$this->createOption('commitdataversion', 'commitdataversion', 'text','0', 'access=hidden');

					$this->createOption('globalpiwikenable','Enable Piwik tracking code for site (overrides Blog/Member enable setting when No)', 'yesno', 'no');
					$this->createOption('globalwebsiteid','Website id in Piwik', 'text', '1', 'datatype=numerical');
					$this->createOption('globalpiwikurl','URL to Piwik installation', 'text', '/piwik/');
					$this->createOption('globaltokenauth','User authentication for admin page widget', 'text', '');

					$this->createBlogOption('blogpiwikenable','Enable Piwik tracking code for blog (overrides Global/Member enable setting when No)', 'yesno', 'yes');
					$this->createBlogOption('blogwebsiteid','Website id in Piwik for this blog (0 = Use global setting)', 'text', '0', 'datatype=numerical');

					$this->createMemberOption('memberpiwikenable','Enable Piwik tracking code for member (overrides Global/Blog enable setting when No)', 'yesno', 'yes');
					
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		
		return true;
	}
	
	function upgradeDataRollback($fromdataversion, $todataversion)
	{
		// Returns true if rollback was successfull
		for($ver = $fromdataversion; $ver >= $todataversion; $ver--)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function upgradeDataCommit($fromdataversion, $todataversion)
	{
		// Returns true if commit was successfull
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		return true;
	}
}
?>
