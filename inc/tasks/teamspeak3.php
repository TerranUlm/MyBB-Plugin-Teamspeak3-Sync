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

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
        die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
function task_teamspeak3($task)
{
        global $db, $mybb, $lang;

        teamspeak3_log("----------------------------");
        $vserver=teamspeak3_connect();
        if ($vserver)
        {
                teamspeak3_mass_set_permissions($vserver);
                teamspeak3_cleanup_teamspeak_groups($vserver);
        }

        add_task_log($task, "updated all managed TS3 permissions");
}
?>
