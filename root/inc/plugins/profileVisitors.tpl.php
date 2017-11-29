<?php
/**
 * This file is part of Profile Visitors plugin for MyBB.
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
 * Plugin Activator Class
 * 
 */
class profileVisitorsActivator 
{

    private static $tpl = array();

    private static function getTpl() 
    {
        global $db;
        
        
        self::$tpl[] = array(
            "title" => 'profileVisitors_Row',
            "template" => $db->escape_string('<tr>
	<td class="{$bgcolor}" style="width:5%;"><strong><img src="{$avatar[\'image\']}" alt="" {$avatar[\'width_height\']}/></td>
	<td class="{$bgcolor}">{$visitor[\'profilelink\']}<p class="smalltext">{$visitor[\'date\']}</p></td>
</tr>'),
            "sid" => "-1",
            "version" => "1.0",
            "dateline" => TIME_NOW,
        );
        
        self::$tpl[] = array(
            "title" => 'profileVisitors',
            "template" => $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td colspan="2" class="thead"><strong>{$lang->profileVisitorsTitle}</strong></td>
	</tr>
	{$profileVisitorsList}
</table>'),
            "sid" => "-1",
            "version" => "1.0",
            "dateline" => TIME_NOW,
        );
        
        
        self::$tpl[] = array(
            "title" => 'profileVisitors_UCP',
            "template" => $db->escape_string('</tr><tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="show_profile_visitors" id="show_profile_visitors" value="1" {$profileVisitorsOption} /></td>
<td><span class="smalltext"><label for="show_profile_visitors">{$lang->profileVisitorsLabel}</label></span></td>'),
            "sid" => "-1",
            "version" => "1.0",
            "dateline" => TIME_NOW,
        );
    }

    public static function activate() 
    {
        global $db;
        self::deactivate();

        for ($i = 0; $i < sizeof(self::$tpl); $i++) {
            $db->insert_query('templates', self::$tpl[$i]);
        }
        
        
        find_replace_templatesets('usercp_options', '#' . preg_quote('{$lang->invisible_mode}</label></span></td>') . '#', '{$lang->invisible_mode}</label></span></td>{$profileVisitorsUCP}');
        find_replace_templatesets('member_profile', '#' . preg_quote('{$modoptions}') . '#', '{$profileVisitors}{$modoptions}');    
    }

    public static function deactivate() 
    {
        global $db;
        self::getTpl();

        for ($i = 0; $i < sizeof(self::$tpl); $i++) {
            $db->delete_query('templates', "title = '" . self::$tpl[$i]['title'] . "'");
        }

        require_once(MYBB_ROOT . '/inc/adminfunctions_templates.php');
        find_replace_templatesets('usercp_options', '#' . preg_quote('{$profileVisitorsUCP}') . '#', '');
        find_replace_templatesets('member_profile', '#' . preg_quote('{$profileVisitors}') . '#', '');
    }
    
}
