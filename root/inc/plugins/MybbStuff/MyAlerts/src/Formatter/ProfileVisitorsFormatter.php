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
* Plugin MyAlerts Formatter Class
* 
*/
class MybbStuff_MyAlerts_Formatter_ProfileVisitorsFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
	public function init()
	{
		$this->alertTypeName = 'profilevisitors';
        if (!$this->lang->profileVisitors) {
			$this->lang->load('profileVisitors');
		}
	}

	public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
	{
        $from_uid = $alert->getFromUserId();
        $from = get_user($from_uid);
        $username = format_name($from['username'], $from['usergroup'], $from['displaygroup']);
    
        return $this->lang->sprintf($this->lang->profileVisitorsAlert, $username);				
	}

	public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
	{
        return "{$this->mybb->settings['bburl']}/member.php?action=profile&uid=" . $alert->getFromUserId();         
	}
}	
