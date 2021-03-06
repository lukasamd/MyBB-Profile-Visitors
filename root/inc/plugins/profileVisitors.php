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
 * Add hooks
 * 
 */
if (profileVisitors::getConfig('Enabled')) {
    $plugins->add_hook("global_start", ['profileVisitors', 'addTemplates']);
    $plugins->add_hook('member_profile_end', ['profileVisitors', 'actionProfile']);
    $plugins->add_hook('usercp_options_end', ['profileVisitors', 'profileOptionsStart']);
    $plugins->add_hook('usercp_do_options_start', ['profileVisitors', 'profileOptionsEnd']);
}

/**
 * Standard MyBB info function
 * 
 */
function profileVisitors_info() {
    global $lang;
    
    $lang->load("profileVisitors");

    return Array(
        'name' => $lang->profileVisitorsName,
        'description' => $lang->profileVisitorsDesc,
        'website' => 'https://tkacz.pro',
        'author' => 'Lukasz Tkacz (Tkacz.pro)',
        'authorsite' => 'https://tkacz.pro',
        'version' => '1.8.1',
        'guid' => '',
        'compatibility' => '18*',
        'codename' => 'profile_visitors',
    );
}

/**
 * Standard MyBB installation functions 
 * 
 */
function profileVisitors_install() {
    require_once MYBB_ROOT . '/inc/plugins/profileVisitors.settings.php';
    profileVisitorsInstaller::install();    
}

function profileVisitors_is_installed() {
    global $mybb;
    return (isset($mybb->settings['profileVisitorsEnabled']));
}

function profileVisitors_uninstall() {
    require_once MYBB_ROOT . '/inc/plugins/profileVisitors.settings.php';
    profileVisitorsInstaller::uninstall();
}

/**
 * Standard MyBB activation functions 
 * 
 */
function profileVisitors_activate() {
    global $db, $cache;

    require_once MYBB_ROOT . '/inc/plugins/profileVisitors.tpl.php';
    profileVisitorsActivator::activate();
}

function profileVisitors_deactivate() {
    global $db, $cache;
    
    require_once MYBB_ROOT . '/inc/plugins/profileVisitors.tpl.php';
    profileVisitorsActivator::deactivate();
}

/**
 * Plugin Class 
 * 
 */
class profileVisitors 
{
    /**
     * Add templates - all initial actions
     *      
     */
    public static function addTemplates() 
    {
        global $mybb, $lang, $cache, $templatelist, $formatterManager;
        
        if ($mybb->user['uid'] == 0) {
            return;
        }
        
        $lang->load("profileVisitors");
        if (THIS_SCRIPT == 'memberlist.php') {
            $templatelist .= ',profileVisitors_Row,profileVisitors'; 
        }
        if (THIS_SCRIPT == 'usercp.php') {
            $templatelist .= ',profileVisitors_UCP'; 
        }
    }
    
    
        
    /**
     * Actions in profile view - collect data and display table
     *      
     */
    public static function actionProfile() 
    { 
        global $db, $lang, $memprofile, $mybb, $profileVisitors, $theme, $templates;
        
        // Save data about view
        if ($memprofile['uid'] != $mybb->user['uid'] 
            && ($memprofile['show_profile_visitors'] || self::getConfig('ForceSave'))
            ) {
            $sql = "REPLACE INTO " . TABLE_PREFIX . "profile_visitors 
                    VALUES ({$memprofile['uid']}, {$mybb->user['uid']}, " . TIME_NOW . ")";
            $db->query($sql);
        }
        
        // Select data and cleanup 
        $visitors = array(); 
        $limit = (int) self::getConfig('Limit');
        $num_visitors = 0;

        $sql = "SELECT tpv.datestamp, tu.usergroup, tu.displaygroup, tu.avatar, tu.avatardimensions, tu.username, tu.uid 
                FROM " . TABLE_PREFIX . "profile_visitors AS tpv
                INNER JOIN " . TABLE_PREFIX . "users AS tu ON (tpv.vuid = tu.uid)
                WHERE tpv.uid = {$memprofile['uid']}
                ORDER BY datestamp DESC";
        $result = $db->query($sql); 
        while ($row = $db->fetch_array($result)) {
            
            // Delete old data
            if ($num_visitors == $limit) {
                $sql = "DELETE FROM " . TABLE_PREFIX . "profile_visitors
                        WHERE uid = {$memprofile['uid']} 
                        AND vuid = {$row['uid']}";
                $db->query($sql); 
            }       
            else {
                $visitors[] = $row;
                $num_visitors++;
            }
        }

        // Something to do?
        if (!$memprofile['show_profile_visitors'] || !$num_visitors) {
            return;
        }
        
        // Display table
        $profileVisitorsList = '';
        foreach ($visitors as $visitor) { 
            $visitor['username'] = format_name($visitor['username'], $visitor['usergroup'], $visitor['displaygroup']);
    		$visitor['profilelink'] = build_profile_link($visitor['username'], $visitor['uid']);
            $visitor['date'] = my_date('relative', $visitor['datestamp']);
			$avatar = format_avatar(htmlspecialchars_uni($visitor['avatar']), $visitor['avatardimensions'], my_strtolower(self::getConfig('AvatarWidth')));
            $bgcolor = alt_trow();
            eval("\$profileVisitorsList .= \"" . $templates->get("profileVisitors_Row") . "\";");	
        }
        eval("\$profileVisitors = \"" . $templates->get("profileVisitors") . "\";");            
    }
     
    /**
     * Display option in profile settings
     *      
     */
    public static function profileOptionsStart() 
    {
        global $user, $lang, $profileVisitorsUCP, $profileVisitorsOption, $templates;
        
        // Load lang
        $lang->load("profileVisitors");

        $profileVisitors = '';
        $profileVisitorsOption = '';
    	if($user['show_profile_visitors'] == 1) {
    		$profileVisitorsOption = 'checked="checked"';
        }
        
        eval("\$profileVisitorsUCP = \"" . $templates->get("profileVisitors_UCP") . "\";");
    }
    
    /**
     * Save options from profile settings
     *      
     */
    public static function profileOptionsEnd() 
    {
        global $mybb, $db;
        
        $show_profile_visitors = 0;
        if (isset($mybb->input['show_profile_visitors'])) {
            $show_profile_visitors = 1;
        }
		
    	$db->update_query("users", array('show_profile_visitors' => $show_profile_visitors), "uid = {$mybb->user['uid']}");
    }
    
    /**
     * Helper function to get variable from config
     * 
     * @param string $name Name of config to get
     * @return string Data config from MyBB Settings
     */
    public static function getConfig($name) 
    {
        global $mybb;

        return $mybb->settings["profileVisitors{$name}"];
    }
}  