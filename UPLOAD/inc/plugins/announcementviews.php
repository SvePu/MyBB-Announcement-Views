<?php
/*
Announcement Views Plugin for MyBB 1.8 - v1.0
Copyright (C) 2017 SvePu

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

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('forumdisplay_announcement', 'announcementviews_show');
$plugins->add_hook('announcements_start', 'announcementviews_run');

function announcementviews_info()
{
	global $db, $lang;
	$lang->load('config_announcementviews');
	return array(
		"name"			=> $db->escape_string($lang->announcementviews),
		"description"	=> $db->escape_string($lang->announcementviews_desc),
		"website"		=> "https://github.com/SvePu/MyBB-Announcement-Views",
		"author"		=> "SvePu",
		"authorsite"	=> "https://community.mybb.com/user-91011.html",
		"version"		=> "1.0",
		"guid" 			=> "",
		"codename"		=> "announcementviews",
		"compatibility" => "18*"
	);
}

function announcementviews_install()
{
	global $mybb, $db, $lang;
	$lang->load('config_announcementviews');

	if(!$db->field_exists("views", "announcements"))
	{
		$db->add_column("announcements", "views", "int(10) NOT NULL default '0'");
	}
	
	$query_add = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query_add, "rows");
    $announcementviews_group = array(
		"name" 			=>	"announcementviews_settings",
		"title" 		=>	$db->escape_string($lang->announcementviews_settings_title),
		"description" 	=>	$db->escape_string($lang->announcementviews_settings_title_desc),
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
    $gid = $db->insert_query("settinggroups", $announcementviews_group);
	
	$announcementviews_setting_array = array(
		'announcementviews_enable' => array(
			'title'			=> $db->escape_string($lang->announcementviews_enable_title),
			'description'  	=> $db->escape_string($lang->announcementviews_enable_title_desc),
			'optionscode'  	=> 'yesno',
			'value'        	=> 1,
			'disporder'		=> 1
		)
	);

	foreach($announcementviews_setting_array as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}
	
	rebuild_settings();
}

function announcementviews_is_installed()
{
	global $mybb;

	if(isset($mybb->settings['announcementviews_enable']))
	{
		return true;
	}
	return false;
}

function announcementviews_uninstall()
{
	global $mybb, $db;
	
	if($mybb->request_method != 'post')
	{
		global $page, $lang;
		$lang->load('config_announcementviews');
		$page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=announcementviews', $db->escape_string($lang->announcementviews_uninstall_confirm), $db->escape_string($lang->announcementviews_uninstall));
	}
	if($db->field_exists("views", "announcements") && !isset($mybb->input['no']))
	{
		$db->drop_column("announcements", "views");
	}
	
	$result = $db->simple_select('settinggroups', 'gid', "name = 'announcementviews_settings'", array('limit' => 1));
	$annoviews_group = $db->fetch_array($result);
	
	if(!empty($annoviews_group['gid']))
	{
		$db->delete_query('settinggroups', "gid='{$annoviews_group['gid']}'");
		$db->delete_query('settings', "gid='{$annoviews_group['gid']}'");
		rebuild_settings();
	}
}

function announcementviews_activate()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"forumdisplay_announcements_announcement",
		"#" . preg_quote('<td align="center" class="{$bgcolor} forumdisplay_announcement">-</td>
{$rating}') . "#i",
		'<td align="center" class="{$bgcolor} forumdisplay_announcement">{$annoviews}</td>
{$rating}'
	);
	
	
}

function announcementviews_deactivate()
{
	require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"forumdisplay_announcements_announcement",
		"#" . preg_quote('{$annoviews}') . "#i",
		'-'
	);

}

function announcementviews_run()
{
	global $mybb, $db, $aid;
	if($mybb->settings['announcementviews_enable'] == 1)
	{
		$db->query("UPDATE ".TABLE_PREFIX."announcements SET views=views+1 WHERE aid='$aid'"); 
	}	
}

function announcementviews_show()
{
	global $mybb, $annoviews, $announcement;
	if($mybb->settings['announcementviews_enable'] == 1)
	{
		$annoviews = my_number_format($announcement['views']);
	}
}