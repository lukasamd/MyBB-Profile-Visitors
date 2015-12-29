<?php
/**
 * This file is part of Profile Visistors plugin for MyBB.
 * Copyright (C) Lukasz Tkacz <lukasamd@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */ 
 
/**
 * Disallow direct access to this file for security reasons
 * 
 */
if (!defined("IN_MYBB")) exit;

/**
 * Plugin Installator Class
 * 
 */
class profileVisitorsInstaller 
{

    public static function install() 
    {
        global $db, $lang, $mybb;
        self::uninstall();

        $result = $db->simple_select('settinggroups', 'MAX(disporder) AS max_disporder');
        $max_disporder = $db->fetch_field($result, 'max_disporder');
        $disporder = 1;
        
        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "profile_visitors (
                uid INT UNSIGNED NOT NULL,
                vuid INT UNSIGNED NOT NULL,
                datestamp INT UNSIGNED NOT NULL
                ) DEFAULT CHARSET=utf8;";
        $db->query($sql);
        
        $sql = "ALTER TABLE " . TABLE_PREFIX . "profile_visitors
                ADD UNIQUE KEY uid (uid, vuid)";
        $db->query($sql);

        $settings_group = array(
            'gid' => 'NULL',
            'name' => 'profileVisitors',
            'title' => $db->escape_string($lang->profileVisitorsName),
            'description' => $db->escape_string($lang->profileVisitorsGroupDesc),
            'disporder' => $max_disporder + 1,
            'isdefault' => '0'
        );
        $db->insert_query('settinggroups', $settings_group);
        $gid = (int) $db->insert_id();

        $setting = array(
            'sid' => 'NULL',
            'name' => 'profileVisitorsEnabled',
            'title' => $db->escape_string($lang->profileVisitorsEnabled),
            'description' => $db->escape_string($lang->profileVisitorsEnabledDesc),
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'profileVisitorsLimit',
            'title' => $db->escape_string($lang->profileVisitorsLimit),
            'description' => $db->escape_string($lang->profileVisitorsLimitDesc),
            'optionscode' => 'text',
            'value' => '5',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'profileVisitorsAvatarWidth',
            'title' => $db->escape_string($lang->profileVisitorsAvatarWidth),
            'description' => $db->escape_string($lang->profileVisitorsAvatarWidthDesc),
            'optionscode' => 'text',
            'value' => '50x50',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
        
        $setting = array(
            'sid' => 'NULL',
            'name' => 'profileVisitorsForceSave',
            'title' => $db->escape_string($lang->profileVisitorsForceSave),
            'description' => $db->escape_string($lang->profileVisitorsForceSaveDesc),
            'optionscode' => 'yesno',
            'value' => '1',
            'disporder' => $disporder++,
            'gid' => $gid
        );
        $db->insert_query('settings', $setting);
    }

    public static function uninstall() 
    {
        global $db;
        
        $result = $db->simple_select('settinggroups', 'gid', "name = 'profileVisitors'");
        $gid = (int) $db->fetch_field($result, "gid");
        
        if ($gid > 0) {
            $db->delete_query('settings', "gid = '{$gid}'");
        }
        $db->delete_query('settinggroups', "gid = '{$gid}'");
        

        if ($db->field_exists("show_profile_visitors", "users")) {
            $db->drop_column("users", "show_profile_visitors");
        }
        $db->drop_table('profile_visitors');
        rebuild_settings();
    }

}
