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
 * Plugin MyAlerts Class
 * 
 */
class profileVisitorsMyAlerts 
{
    static $alertType = null;
    static $enabled = null;
    

    public static function isEnabled()
    {
        global $db, $cache;
        
        if ($enabled !== null) {
            return $enabled;
        }

        if (!function_exists('myalerts_is_activated') 
            || !myalerts_is_activated()
            || !profileVisitors::getConfig('MyAlerts')    
        ) {
            self::$enabled = false;
            return false;    
        }
        
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        self::$alertType = $alertTypeManager->getByCode('profilevisitors');    
        if (!self::$alertType->getEnabled()) {
            self::$enabled = false;
            return false;
        }
        
        if (class_exists("MybbStuff_MyAlerts_Formatter_AbstractFormatter")) {
            require_once MYBB_ROOT . '/inc/plugins/profileVisitorsFormatter.php';
            self::registerFormatter();
        }
        
        self::$enabled = true;
        return true;
    }
    
    
    public function registerFormatter() 
    {
        global $db, $cache, $mybb, $lang;

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
		if($formatterManager === false) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}
		$formatterManager->registerFormatter(new profileVisitorsFormatter($mybb, $lang, "profilevisitors"));
    }

    public static function activate() 
    {
        global $db, $cache;
        
        if (function_exists('myalerts_is_activated') && myalerts_is_activated()) {
    		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
    		if($alertTypeManager === false) {
    			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    		}
            $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        	$alertType->setCode('profilevisitors');
        	$alertType->setEnabled(true);
            $alertTypeManager->add($alertType);
    	}
    }

    public static function deactivate() 
    {
        global $db, $cache;
    
        if (self::isEnabled()) {
    		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
    		if($alertTypeManager === false) {
    			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    		}
            $alertTypeManager->deleteByCode('profilevisitors');
    	}
    }

    
    /**
     * Save alert to database
     * 
     * @param int $uid To user ID
     * @param int $from From user ID
     */
    public static function alert($uid, $from)
    {
    	global $db, $lang, $mybb;
    	
        if (!self::isEnabled()) {
            return;
        }
        
         // Is already alerted?
        $result = $db->simple_select(
			'alerts',
			'id',
			'uid = ' .$uid . ' AND from_user_id = ' . $from . ' AND unread = 1 AND alert_type_id = ' . self::$alertType->getId() . ''
        );
        
        if ($db->num_rows($result) == 0) {   
            $alert = new MybbStuff_MyAlerts_Entity_Alert($uid, self::$alertType, $from);
            MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
        }
    }
}
