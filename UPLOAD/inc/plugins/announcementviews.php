<?php
/*
Announcement Views Plugin for MyBB 1.8 - v1.2
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

if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook("admin_config_settings_begin", 'announcementviews_load_lang');
}
else
{
    $plugins->add_hook('forumdisplay_announcement', 'announcementviews_show');
    $plugins->add_hook('announcements_start', 'announcementviews_run');
}

function announcementviews_info()
{
    global $db, $lang;
    $lang->load('config_announcementviews');
    return array(
        "name"          => $db->escape_string($lang->announcementviews),
        "description"   => $db->escape_string($lang->announcementviews_desc),
        "website"       => "https://github.com/SvePu/MyBB-Announcement-Views",
        "author"        => "SvePu",
        "authorsite"    => "https://community.mybb.com/user-91011.html",
        "version"       => "1.2",
        "codename"      => "announcementviews",
        "compatibility" => "18*"
    );
}

function announcementviews_install()
{
    global $mybb, $db, $lang;
    $lang->load('config_announcementviews');

    if (!$db->field_exists("views", "announcements"))
    {
        $db->add_column("announcements", "views", "int(10) NOT NULL default '0'");
    }

    $group = array(
        'name'        => 'announcementviews',
        'title'       => $db->escape_string($lang->setting_group_announcementviews),
        'description' => $db->escape_string($lang->setting_group_announcementviews_desc),
        'isdefault'   => 0
    );

    $query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
    $disporder = (int)$db->fetch_field($query, 'disporder');

    $group['disporder'] = ++$disporder;

    $gid = (int)$db->insert_query('settinggroups', $group);

    $settings = array(
        'enable' => array(
            'optionscode' => 'yesno',
            'value' => 1
        )
    );

    $disporder = 0;

    foreach ($settings as $key => $setting)
    {
        $key = "announcementviews_{$key}";

        $setting['name'] = $db->escape_string($key);

        $lang_var_title = "setting_{$key}";
        $lang_var_description = "setting_{$key}_desc";

        $setting['title'] = $db->escape_string($lang->{$lang_var_title});
        $setting['description'] = $db->escape_string($lang->{$lang_var_description});
        $setting['disporder'] = $disporder;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
        ++$disporder;
    }

    rebuild_settings();
}

function announcementviews_is_installed()
{
    global $mybb;

    if (isset($mybb->settings['announcementviews_enable']))
    {
        return true;
    }
    return false;
}

function announcementviews_uninstall()
{
    global $mybb, $db;

    if ($mybb->request_method != 'post')
    {
        global $page, $lang;
        $lang->load('config_announcementviews');
        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=announcementviews', $db->escape_string($lang->announcementviews_uninstall_confirm), $db->escape_string($lang->announcementviews_uninstall));
    }

    $db->delete_query("settinggroups", "name='announcementviews'");
    $db->delete_query("settings", "name LIKE 'announcementviews%'");

    rebuild_settings();

    if (!isset($mybb->input['no']))
    {
        if ($db->field_exists("views", "announcements"))
        {
            $db->drop_column("announcements", "views");
        }
    }
}

function announcementviews_activate()
{
    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumdisplay_announcements_announcement", "#" . preg_quote("forumdisplay_announcement\">-</td>\n{\$rating}") . "#i", "forumdisplay_announcement\">{\$announcement['views']}</td>\n{\$rating}");
}

function announcementviews_deactivate()
{
    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("forumdisplay_announcements_announcement", "#" . preg_quote('{$announcement[\'views\']}') . "#i", '-');
}

function announcementviews_load_lang()
{
    global $lang;
    $lang->load('config_announcementviews');
}

function announcementviews_run()
{
    global $mybb, $db, $aid;
    if ($mybb->settings['announcementviews_enable'] == 1)
    {
        $db->query("UPDATE " . TABLE_PREFIX . "announcements SET views=views+1 WHERE aid='$aid'");
    }
}

function announcementviews_show()
{
    global $mybb, $announcement;

    if ($mybb->settings['announcementviews_enable'] != 0)
    {
        $announcement['views'] = my_number_format($announcement['views']);
    }
    else
    {
        $announcement['views'] = '-';
    }
}
