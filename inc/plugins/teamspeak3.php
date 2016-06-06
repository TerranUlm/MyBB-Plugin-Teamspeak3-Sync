<?php

/*
Teamspeak 3 Group Sync Plugin for MyBB
Copyright (C) 2013 Dieter Gobbers

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}


function teamspeak3_info()
{
	return array
	(
		'name'=>'Teamspeak 3 Group Sync',
		'description'=>'Syncs forum and Teamspeak3 groups.',
		'website'=>'http://opt-community.de/',
		'author'=>'Dieter Gobbers',
		'version'=>'1.1.0',
		'guid'=>'',
		'compatibility'=>'18*',
		'codename'=>'teamspeak3'
	);
}

function teamspeak3_activate()
{
	global $db, $lang, $cache;

        $lang->load('teamspeak3');

	$result = $db->update_query("tasks", array("enabled" => intval(1)), "title='".$db->escape_string($lang->ts3)."'");
	$cache->update_tasks();

}

function teamspeak3_deactivate()
{
	global $db, $cache, $lang;

        $lang->load('teamspeak3');
	
	$result = $db->update_query("tasks", array("enabled" => intval(0)), "title='".$db->escape_string($lang->ts3)."'");
	$cache->update_tasks();
}

/**
 * function teamspeak3_is_installed()
 * function teamspeak3_install()
 * function teamspeak3_uninstall()
 */

function teamspeak3_is_installed()
{
    global $mybb, $db;

    $info=teamspeak3_info();
    $result = $db->simple_select('settinggroups','gid','name="'.$info['codename'].'"',array('limit'=>1));
    $group = $db->fetch_array($result);

    return !empty($group['gid']);
}

function teamspeak3_install()
{
	global $db, $lang, $cache;
	
	$lang->load('teamspeak3');
	teamspeak3_uninstall();
	$info=teamspeak3_info();
	$setting_group_array=array
	(
		'name'=>$info['codename'],
		'title'=>$info['name'],
		'description'=>'Here you can edit '.$info['name'].' settings.',
		'disporder'=>1,
		'isdefault'=>0
	);
	$db->insert_query('settinggroups',$setting_group_array);
	$group=$db->insert_id();
	$db->query("ALTER TABLE `".TABLE_PREFIX."usergroups` ADD `ts3_sgid` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Servergroiup ID',  ADD `ts3_cgid` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Channelgroup ID',  ADD `ts3_cid` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Channel ID',  ADD `ts3_order` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Sort Order'");
	$settings=array
	(
		'teamspeak3_address'=>array
		(
			$lang->ts3_server_address,
			$lang->ts3_server_address_description,
			'text',
			'127.0.0.1'
		),
		'teamspeak3_port'=>array
		(
			$lang->ts3_server_port,
			$lang->ts3_server_port_description,
			'text',
			'10011'
		),
		'teamspeak3_username'=>array
		(
			$lang->ts3_username,
			$lang->ts3_username_description,
			'text',
			''
		),
		'teamspeak3_password'=>array
		(
			$lang->ts3_password,
			$lang->ts3_password_description,
			'passwordbox',
			''
		),
		'teamspeak3_vserverport'=>array
		(
			$lang->ts3_vserverport,
			$lang->ts3_vserverport_description,
			'text',
			''
		),
		'teamspeak3_guest_channel_group'=>array
		(
			$lang->ts3_guest_channel_group,
			$lang->ts3_guest_channel_group_description,
			'text',
			''
		)
	);
	$i=1;
	foreach($settings as $name=>$sinfo)
	{
		$insert_array=array
		(
			'name'=>$name,
			'title'=>$db->escape_string($sinfo[0]),
			'description'=>$db->escape_string($sinfo[1]),
			'optionscode'=>$db->escape_string($sinfo[2]),
			'value'=>$db->escape_string($sinfo[3]),
			'gid'=>$group,
			'disporder'=>$i,
			'isdefault'=>0
		);
		$db->insert_query('settings',$insert_array);
		$i++;
	}
	rebuild_settings();

	for ($i=1; $i<=3; $i++)
	{
		$new_profile_field = array(
			"name" => 'TeamspeakID '.$i,
			"description" => 'eindeutige Teamspeak ID, zu finden unter "Einstellungen->Identitäten->Standard->Eindeutige ID"',
			"disporder" => 6+$i,
			"type" => 'text',
			"length" => intval('70'),
			"maxlength" => intval('60'),
			"required" => intval('0'),
			"editable" => intval('1'),
			"hidden" => intval('0'),
			"postnum" => intval('0')
		);

		$fid = $db->insert_query("profilefields", $new_profile_field);

		$db->write_query("ALTER TABLE ".TABLE_PREFIX."userfields ADD fid{$fid} TEXT");
	}

	require_once MYBB_ROOT."/inc/functions_task.php";

	$new_task = array(
		"title" => $db->escape_string($lang->ts3),
		"description" => $db->escape_string($lang->ts3_task_description),
		"file" => $db->escape_string('teamspeak3'),
		"minute" => $db->escape_string('3,33'),
		"hour" => $db->escape_string('*'),
		"day" => $db->escape_string('*'),
		"month" => $db->escape_string('*'),
		"weekday" => $db->escape_string('*'),
		"enabled" => intval(0),
		"logging" => intval(1)
	);

	$new_task['nextrun'] = fetch_next_run($new_task);
	$tid = $db->insert_query("tasks", $new_task);
	$cache->update_tasks();
}

function teamspeak3_uninstall()
{
        global $db, $lang, $cache;
        $info=teamspeak3_info();
        $result=$db->simple_select('settinggroups','gid','name="'.$info['codename'].'"',array('limit'=>1));
        $group=$db->fetch_array($result);
        if(!empty($group['gid']))
        {
		$db->write_query("ALTER TABLE `".TABLE_PREFIX."usergroups` DROP `ts3_sgid`, DROP `ts3_cgid`, DROP `ts3_cid`, DROP `ts3_order`");
                $db->delete_query('settinggroups','gid="'.$group['gid'].'"');
                $db->delete_query('settings','gid="'.$group['gid'].'"');
                rebuild_settings();
        }
	for ($i=1; $i<=3; $i++)
	{
		$query = $db->simple_select("profilefields", "*", "name='TeamspeakID ".$i."'");
		$profile_field = $db->fetch_array($query);
		if($profile_field['fid'])
		{
			$db->delete_query("profilefields", "fid='{$profile_field['fid']}'");
			$db->write_query("ALTER TABLE ".TABLE_PREFIX."userfields DROP fid{$profile_field['fid']}");
		}
	}

	$db->delete_query("tasks", "title='{$db->escape_string($lang->ts3)}'");
	$cache->update_tasks();
}

/* --- Hooks: --- */

/* --- Hook #1 - Add Teamspeak Tab --- */

$plugins->add_hook('admin_user_groups_edit_graph_tabs','teamspeak3_admin_user_groups_edit_graph_tabs_1',1);

function teamspeak3_admin_user_groups_edit_graph_tabs_1(&$tabs)
{
	global $lang;
	$lang->load('teamspeak3');
	$tabs["ts"] = $lang->ts3;
	return $tabs;
}

/* --- Hook #2 - Add Teamspeak Tab Content --- */

$plugins->add_hook('admin_user_groups_edit_graph','teamspeak3_admin_user_groups_edit_graph_2',1);

function teamspeak3_admin_user_groups_edit_graph_2()
{
	global $lang, $form, $mybb;

	$lang->load('teamspeak3');

	echo "<div id=\"tab_ts\">";

	$form_container = new FormContainer("Teamspeak 3 Mapping");

	$form_container->output_row($lang->ts3_sgid, $lang->ts3_sgid_description, $form->generate_text_box('ts3_sgid', $mybb->input['ts3_sgid'], array('id' => 'ts3_sgid')), 'servergroupid');

	$channelgroup_options = array(
		$lang->ts3_cid.":<br />".$lang->ts3_cid_description."<br />".$form->generate_text_box('ts3_cid', $mybb->input['ts3_cid'], array('id' => 'ts3_cid')),
		$lang->ts3_order.":<br />".$lang->ts3_order_description."<br />".$form->generate_text_box('ts3_order', $mybb->input['ts3_order'], array('id' => 'ts3_order')),
		$lang->ts3_cgid.":<br />".$form->generate_text_box('ts3_cgid', $mybb->input['ts3_cgid'], array('id' => 'ts3_cgid'))
	);
	$form_container->output_row($lang->ts3_data, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $channelgroup_options)."</div>");

	$form_container->end();

	echo "</div>";

	print date("T Y-m-d H:i:s");
/*
	teamspeak3_log("----------------------------");
	$vserver=teamspeak3_connect();
	if ($vserver)
	{
		teamspeak3_mass_set_permissions($vserver);
		teamspeak3_cleanup_teamspeak_groups($vserver);
	}
*/
}

/* --- Hook #6 - admin_user_groups_edit_commit --- */

$plugins->add_hook('admin_user_groups_edit_commit','teamspeak3_admin_user_groups_edit_commit_6',10);

function teamspeak3_admin_user_groups_edit_commit_6()
{
	global $mybb, $db, $updated_group, $cache;

	$updated_group['ts3_sgid']  = intval($mybb->input['ts3_sgid']);
	$updated_group['ts3_cgid']  = intval($mybb->input['ts3_cgid']);
	$updated_group['ts3_cid']   = intval($mybb->input['ts3_cid']);
	$updated_group['ts3_order'] = intval($mybb->input['ts3_order']);

	$db->update_query("usergroups", $updated_group, "gid='{$usergroup['gid']}'");
	
	// Update the caches
	$cache->update_usergroups();

	teamspeak3_log("----------------------------");
	$vserver=teamspeak3_connect();
	if ($vserver)
	{
		teamspeak3_mass_set_permissions($vserver);
		teamspeak3_cleanup_teamspeak_groups($vserver);
	}
}

/* --- Hook #8 - usercp_do_profile_end --- */

$plugins->add_hook('usercp_do_profile_end','teamspeak3_usercp_do_profile_end_8',10);

function teamspeak3_usercp_do_profile_end_8()
{
	teamspeak3_log("----------------------------");
	$vserver=teamspeak3_connect();
	if ($vserver)
	{
		teamspeak3_mass_set_permissions($vserver);
		teamspeak3_cleanup_teamspeak_groups($vserver);
	}
}

// **************************************
// TS3 communication functions
// **************************************
function teamspeak3_connect()
{
        global $mybb;

        require_once MYBB_ROOT . 'TeamSpeak3/TeamSpeak3.php';

        $ts3_host=$mybb->settings['teamspeak3_address'];
        $ts3_port=$mybb->settings['teamspeak3_port'];
        $ts3_username=$mybb->settings['teamspeak3_username'];
        $ts3_password=$mybb->settings['teamspeak3_password'];
        $ts3_vserverport=$mybb->settings['teamspeak3_vserverport'];

	$result = false;
	try
	{
		$result = TeamSpeak3::factory('serverquery://'.$ts3_username.':'.$ts3_password.'@'.$ts3_host.':'.$ts3_port.'/?server_port='.$ts3_vserverport);
	}
	catch(TeamSpeak3_Exception $e)
	{
		// print the error message returned by the server
		teamspeak3_log("TeamSpeak3::factory: Error " . $e->getCode() . ": " . $e->getMessage());
		return false;
	}
	return $result;
}

function teamspeak3_get_teamspeak_userlist($vserver)
{
	// query clientlist from virtual server
	$offset=0;
        $limit=200; // 200 is the max 
	$count=1;
	$ts3_userlist=array();
	while($offset < $count)
	{
		//echo "offset: ".$offset."<br>";
		$arr_ClientList = $vserver->clientListDb($offset,$limit);

		foreach($arr_ClientList as $ts3_Client)
		{
			if($ts3_Client["count"]) $count=$ts3_Client["count"];
			if($ts3_Client["client_type"]) continue;
			$ts3_userlist = array_merge($ts3_userlist, array($ts3_Client));
		}
		$offset = $offset + $limit;
	}

	//echo "<br>Gesamtanzahl: ".$count;
	//teamspeak3_print_r('ts3_userlist',$ts3_userlist);
	return $ts3_userlist;
}

// **************************************
// TS3 helper functions
// **************************************
function teamspeak3_get_cldbid_by_uid($uid,$users)
{
	$result=false;
	foreach($users as $user)
	{
		//echo "###".$user['client_unique_identifier']."###<br>";
		if($user['client_unique_identifier'] == $uid)
		{
			$result=$user['cldbid'];
		}
	}
	return $result;
}

// **************************************
// Forum to TS3 functions
// **************************************
function teamspeak3_map_forum_to_server_group($gid,$grouplist)
{
	$sgid=-1;
	foreach($grouplist as $group)
	{
		if ($group['gid'] == $gid)
		{
			$sgid = $group['ts3_sgid'];
		}
	}
	return $sgid;
}

function teamspeak3_map_forum_to_channel_group_and_channel($gid,$grouplist)
{
	$cgid_cid=array();
	foreach($grouplist as $group)
	{
		if ($group['gid'] == $gid and $group['ts3_cgid'] > 0 and $group['ts3_cid'] > 0)
		{
			$cgid_cid = array(
						"cid" => $group['ts3_cid'],
						"order" => $group['ts3_order'],
						"cgid" => $group['ts3_cgid']
					);
		}
	}
	return $cgid_cid;
}

// **************************************
// Forum helper functions
// **************************************
function teamspeak3_get_forum_grouplist()
{
	global $cache;

	return $cache->read("usergroups");
}

// **************************************
// High level functions
// **************************************
function teamspeak3_get_forum_userlist($vserver)
{
	global $db;

	$ts3_userlist = teamspeak3_get_teamspeak_userlist($vserver);
	$grouplist = teamspeak3_get_forum_grouplist();

	$fids=array();
	for ($i=1; $i<=3; $i++)
	{
		$query = $db->simple_select("profilefields", "fid", "name='TeamspeakID ".$i."'");
		$profile_field = $db->fetch_array($query);
		if($profile_field['fid'])
		{
			$fids[]='fid'.$profile_field['fid'];
		}
	}
	$userlist=array();
	$query = $db->write_query("select uf.ufid,u.username,u.usergroup,u.additionalgroups,uf.".$fids[0].",uf.".$fids[1].",uf.".$fids[2]." FROM ".TABLE_PREFIX."userfields as uf JOIN ".TABLE_PREFIX."users as u ON uf.ufid=u.uid WHERE (uf.".$fids[0]." IS NOT NULL OR uf.".$fids[1]." IS NOT NULL OR uf.".$fids[2]." IS NOT NULL) AND (uf.".$fids[0]." != '' OR uf.".$fids[1]." != '' OR uf.".$fids[2]." != '')");
	while($userdata = $db->fetch_array($query))
	{
		$groups=array_merge(preg_split('/,/',$userdata['usergroup']),preg_split('/,/',$userdata['additionalgroups']));

		// map forum groups to server groups
		$sgids=array();
		foreach($groups as $group)
		{
			$tmp=teamspeak3_map_forum_to_server_group($group,$grouplist);
			if ($tmp > 0)
			{
				$sgids[]=$tmp;
			}
		}
		//teamspeak3_print_r('sgids',$sgids);

		// map forum groups to channel groups and channels
		$cgs=array();
		foreach($groups as $group)
		{
			if($tmp=teamspeak3_map_forum_to_channel_group_and_channel($group,$grouplist))
			{
				$cgs[]=$tmp;
			}
		}
		//teamspeak3_print_r('cgs',$cgs);

		// process channel group ranking
		$cids=array();
		foreach($cgs as $channel)
		{
			//teamspeak3_print_r('channel',$channel);
			// do we already have data for this channel?
			if($cids[$channel['cid']])
			{
				//echo"<br>weitere Daten gefunden";
				if($channel['order']>$cids[$channel['cid']]['order'])
				{
					// echo"<br>höherrangige Daten gefunden";
					$cids[$channel['cid']]['order']=$channel['order'];
					$cids[$channel['cid']]['cgid']=$channel['cgid'];
				}
				else
				{
					// echo"<br>niederrangige Daten gefunden: ".$channel['order']." < ".$cids[$channel['cid']]['order'];
				}
			}
			else
			{
				$cids[$channel['cid']]=array(
								"order" => $channel['order'],
								"cgid" => $channel['cgid']
							);
			}
		}
		//teamspeak3_print_r('cids',$cids);
		$cgids_cids=array();
		foreach(array_keys($cids) as $cid)
		{
			$cgids_cids[]=array(
						"cid" => $cid,
						"order" => $cids[$cid]['order'],
						"cgid" => $cids[$cid]['cgid']
					);
		}

		// map forum user to (multiple) teamspeak users
		$cldbids=array();
		for ($i=0; $i<=2; $i++)
		{
			if($userdata[$fids[$i]])
			{
				//echo "--> fids".$fids[$i].": ".$userdata[$fids[$i]];
				if($tmp=teamspeak3_get_cldbid_by_uid($userdata[$fids[$i]],$ts3_userlist))
				{
					//echo " => ".$tmp;
					$cldbids[]=$tmp;
				}
				//echo "<br>";
			}
		}
		$tuserlist=array(
					"userid" => $userdata['ufid'],
					"username" => $userdata['username'],
					"cldbids" => $cldbids,
					"servergroups" => $sgids,
					"channels" => $cgids_cids
				);
		//teamspeak3_print_r('tuserlist',$tuserlist);
		$userlist[]=$tuserlist;
	}
	return $userlist;
}

function teamspeak3_mass_set_permissions($vserver)
{
	//echo("<br><br>mass set permissions:");

	$userlist=teamspeak3_get_forum_userlist($vserver);
	//teamspeak3_print_r('userlist',$userlist);
	
	foreach($userlist as $user)
	{
		//echo("<br>UserID: ".$user['userid']);
		foreach($user['cldbids'] as $cldbid)
		{
			//echo("<br>-> cldbid: ".$cldbid);
			foreach($user['servergroups'] as $sgid)
			{
				//echo("<br>-> Server Group: ".$sgid);
				try
				{
					$sgids=$vserver->clientGetServerGroupsByDbid($cldbid);
					//teamspeak3_print_r('sgids',$sgids);
				}
				catch(TeamSpeak3_Exception $e)
				{
					// print the error message returned by the server
					teamspeak3_log("Error " . $e->getCode() . ": " . $e->getMessage());
				}
				try
				{
					if (!$sgids[$sgid])
					{
						//echo "<br>setting server group ".$sgid." for user ".$cldbid;
						teamspeak3_log("setting server group ".$sgid." for user ".$cldbid);
						$vserver->serverGroupClientAdd($sgid,$cldbid);
					}
				}
				catch(TeamSpeak3_Exception $e)
				{
					// print the error message returned by the server
					teamspeak3_log("Error " . $e->getCode() . ": " . $e->getMessage());
				}
			}
			foreach($user['channels'] as $channel)
			{
				if ($channel['cgid'] == 0 or $channel['cid'] == 0) continue;
				//echo("<br>-> Channel: ".$channel['cid'].", Channel Group: ".$channel['cgid']);
				try
				{
					$result=$vserver->channelGroupClientList($channel['cgid'],$channel['cid'],$cldbid);
					//teamspeak3_print_r('result',$result);
				}
				catch(TeamSpeak3_Exception $e)
				{
					if ($e->getCode()<>1281)
					{
						// print the error message returned by the server
						teamspeak3_log("channelGroupClientList: Error " . $e->getCode() . ": " . $e->getMessage());
					}
					else
					{
						$result=false;
					}
				}
				try
				{
					if (!($result))
					{
						teamspeak3_log("setting channel group ".$channel['cgid']." at channel ".$channel['cid']." for user ".$cldbid);
						$result=$vserver->clientSetChannelGroup($cldbid,$channel['cid'],$channel['cgid']);
					}
				}
				catch(TeamSpeak3_Exception $e)
				{
					// print the error message returned by the server
					teamspeak3_log("clientSetChannelGroup: Error " . $e->getCode() . ": " . $e->getMessage());
				}
			}
		}
	}
	//echo "<br>done mass set permissions";
}

function teamspeak3_cleanup_teamspeak_groups($vserver)
{
	global $mybb;

	//echo("<br><br>cleanup teamspeak groups:");

	$userlist=teamspeak3_get_forum_userlist($vserver);
	$grouplist=teamspeak3_get_forum_grouplist();

	// teamspeak3_print_r('userlist',$userlist);

	// reformat the user data for easier processing
	$u_sgids=array();
	$u_cgids_cids=array();
	foreach($userlist as $user)
	{
		//teamspeak3_print_r('user',$user);
		foreach($user['cldbids'] as $cldbid)
		{
			foreach($user['servergroups'] as $sgid)
			{
				$u_sgids[$cldbid][$sgid]=true;
			}
			foreach($user['channels'] as $channel)
			{
				//teamspeak3_print_r('channel',$channel);
		
				$u_cgids_cids[$cldbid][$channel['cid']][$channel['cgid']]=true;
			}
		}
	}
	//teamspeak3_print_r('u_sgids',$u_sgids);
	//teamspeak3_print_r('u_cgids_cids',$u_cgids_cids);

	// reformat the group data for easier processing
	$g_sgids=array();
	$g_cgids_cids=array();
	foreach($grouplist as $group)
	{
		//teamspeak3_print_r('group',$group);
		if ($group['ts3_sgid'] > 0)
		{
			$g_sgids[$group['ts3_sgid']][]=$group['gid'];
		}
		if ($group['ts3_cgid'] > 0 and $group['ts3_cid'] > 0 )
		{
			$g_cgids_cids[$group['ts3_cid']][$group['ts3_order']][$group['ts3_cgid']][]=$group['gid'];
		}
	}
	//teamspeak3_print_r('g_sgids',$g_sgids);
	//teamspeak3_print_r('g_cgids_cids',$g_cgids_cids);
	
	// fetch server group data from the teamspeak server
	$sg_uids=array();
	foreach(array_keys($g_sgids) as $g_sgid)
	{
		if($g_sgid>0)
		{
			//teamspeak3_print_r('g_sgid',$g_sgid);
			try
			{
				$tmp = $vserver->serverGroupClientList($g_sgid);
			}
			catch(TeamSpeak3_Exception $e)
			{
				// print the error message returned by the server
				teamspeak3_log("serverGroupClientList: Error " . $e->getCode() . ": " . $e->getMessage());
			}
			//teamspeak3_print_r('tmp',$tmp);
			$tmp2=array();
			foreach(array_keys($tmp) as $cldbid)
			{
				$tmp2[]=$cldbid;
			}
			$sg_uids[$g_sgid] = $tmp2;
			//teamspeak3_print_r('sg_uids',$sg_uids);
		}
	}
	//teamspeak3_print_r('sg_uids',$sg_uids);

	// fetch channel data from the teamspeak server
	$cg_uids=array();
	foreach(array_keys($g_cgids_cids) as $cid)
	{
		//teamspeak3_print_r('cid',$cid);
		foreach(array_keys($g_cgids_cids[$cid]) as $order)
		{
			//teamspeak3_print_r('order',$order);
			foreach(array_keys($g_cgids_cids[$cid][$order]) as $cgid)
			{
				//teamspeak3_print_r('cgid',$cgid);
				$tmp=array();
				try
				{
					$tmp = $vserver->channelGroupClientList($cgid,$cid);
				}
				catch(TeamSpeak3_Exception $e)
				{
					if ($e->getCode()<>1281)
					{
						// print the error message returned by the server
						teamspeak3_log("channelGroupClientList: Error " . $e->getCode() . ": " . $e->getMessage());
					}
				}
				//teamspeak3_print_r('tmp',$tmp);
				$cldbids=array();
				foreach($tmp as $data)
				{
					//teamspeak3_print_r('data',$data);
					$cldbids[]=$data['cldbid'];
				}
				//teamspeak3_print_r('cldbids',$cldbids);
				$cg_uids[$cid][$cgid]=$cldbids;
			}
			//teamspeak3_print_r('cg_uids',$cg_uids);
		}
		//echo"<br>";
	}
	//teamspeak3_print_r('cg_uids',$cg_uids);

	//echo"<br><br>compare server groups:";
	// compare server group data
	foreach(array_keys($sg_uids) as $sgid)
	{
		//teamspeak3_print_r('sgid',$sgid);
		foreach($sg_uids[$sgid] as $cldbid)
		{
			//teamspeak3_print_r('cldbid',$cldbid);
			if ($u_sgids[$cldbid][$sgid])
			{
				//echo"<br>-> User ".$cldbid." ist berechtigt";
			}
			else
			{
				//echo"<br>-> User ".$cldbid." ist nicht berechtigt";
				try
				{
					teamspeak3_log("removing user ".$cldbid." from server group ".$sgid);
					$vserver->serverGroupClientDel($sgid,$cldbid);
				}
				catch(TeamSpeak3_Exception $e)
				{
					// print the error message returned by the server
					teamspeak3_log("serverGroupClientDel: Error " . $e->getCode() . ": " . $e->getMessage());
				}
			}
		}
		//echo"<br>";
	}

	// compare channel group data
	$default_channel_group=$mybb->settings['teamspeak3_guest_channel_group'];
	foreach(array_keys($cg_uids) as $cid)
	{
		//teamspeak3_print_r('cid',$cid);
		foreach(array_keys($cg_uids[$cid]) as $cgid)
		{
			//teamspeak3_print_r('cgid',$cgid);
			foreach($cg_uids[$cid][$cgid] as $cldbid)
			{
				//teamspeak3_print_r('cldbid',$cldbid);
				if ($u_cgids_cids[$cldbid][$cid][$cgid])
				{
					//echo"<br>-> User ".$cldbid." ist berechtigt";
				}
				else
				{
					//echo"<br>-> User ".$cldbid." ist nicht berechtigt";
					try
					{
						teamspeak3_log("clearing channel group ".$cgid." at channel ".$cid." for user ".$cldbid);
						$vserver->clientSetChannelGroup($cldbid,$cid,$default_channel_group);
					}
					catch(TeamSpeak3_Exception $e)
					{
						// print the error message returned by the server
						teamspeak3_log("clientSetChannelGroup: Error " . $e->getCode() . ": " . $e->getMessage());
					}
				}
			}
		}
		//echo "<br>";
	}
	
	//echo "<br>done cleaning up the channel groups";
}

// **************************************
// debugging
// **************************************
function teamspeak3_print_r($label,$array)
{
	echo("<br>".$label.": ");
	print_r($array);
}

function teamspeak3_log($log)
{
	$logfile = MYBB_ROOT . 'cache/teamspeak3_log.php';
	$logmessage = '';

	if(!file_exists($logfile))
	{
		$fileoption = 'w';
		$logmessage = '<?php die(); ?>'."\n";
	}
	else
	{
		$fileoption = 'a';
	}

	$logmessage .= date("T Y-m-d H:i:s").": ";
	$logmessage .= $log."\n";

	$fhandle = @fopen($logfile, $fileoption);
	@fwrite($fhandle, $logmessage);
	@fclose($fhandle);
}


/* Exported by Hooks plugin Tue, 06 Aug 2013 10:09:38 GMT */
?>
