# MyBB-Plugin-Teamspeak3-Sync
A plugin for MyBB 1.8.x to sync forum groups to TS3 groups.

**Requires the [Teamspeak 3 PHP Framework](http://addons.teamspeak.com/directory/addon/integration/TeamSpeak-3-PHP-Framework.html) installed in the MyBB root folder!**

You only need the "TeamSpeak3" folder found in the "libraries" folder. Upload it to the MyBB root folder.

## Quick Setup Guide:

**_TS3 setup:_**

You need to create a serverquery account - dont use the superadmin account for that! I'll recommend YaTQA (http://yat.qa/) as a TS3 admin tool.
The account needs only an limited set of permissions (I've put them in a Query Group and added the user to that group):
* b_serverinstance_virtualserver_list
* b_serverquery_login
* b_serverinstance_textmessage_send
* b_virtualserver_select
* b_virtualserver_info_view
* b_virtualserver_client_dblist
* b_virtualserver_modify_channel_temp_delete_delay_default
* i_channel_create_modify_with_temp_delete_delay **86400**
* b_channel_modify_temp_delete_delay
* b_virtualserver_servergroup_client_list
* b_virtualserver_channelgroup_client_list
* i_group_needed_modify_power **99**
* i_group_member_add_power **99**
* i_group_needed_member_add_power **99**
* i_group_member_remove_power **99**
* i_group_needed_member_remove_power **99**
* b_group_is_permanent
* i_group_auto_update_type **50**
* b_client_permissionoverview_own
* i_client_needed_kick_from_server_power **100**
* i_client_needed_kick_from_channel_power **100**
* i_client_needed_ban_power **100**
* i_client_needed_move_power **100**
* i_client_needed_complain_power **100**
* b_client_create_modify_serverquery_login
* i_client_permission_modify_power **99**

:exclamation: Adjust the numbers to your servers needs!

**_MyBB Settings:_**

The plugin adds a new tab in the ACP for each group where you can configure the correspondig Teamspeak 3 permissions:
* Server Group ID (Database ID of the Server Group)
* Teamspeak3 Channel Data:
 * Teamspeak 3 Channel ID (Database ID of the Channel)
 * Sort Order (highest number wins as a user can be member of one channel group per channel only)
 * Teamspeak 3 Channel Group ID  (Database ID of the Channel Group)
 
The plugin also adds a section to the MyBB Settings:
* Teamspeak 3 Group Sync
 * Teamspeak 3 Server address (Set the Teamspeak 3 Server IP or hostname)
 * Teamspeak 3 Server Query Port (Set the Teamspeak 3 Server Query Port)
 * Teamspeak 3 Server Query User (Set the Teamspeak 3 Server Query User)
 * Teamspeak 3 Server Query User Password (Set the Teamspeak 3 Server Query User Password)
 * Teamspeak 3 Virutal Server Port (Set the Teamspeak 3 Virtual Server Port)
 * Teamspeak 3 Server Guest Channel Group ID (This will be the default group for channels controlled by this plugin)

The plugin adds three new custom profile fields. The users have to put their TS3 unique IDs in those fields. Usually they only need one but some users might have multiple computers and don't know how to transfer the ID and just create a new one.

**_Now for the fun part: setting up how a forum group is synced to TS3._**

E.g. you want to sync the forums administrators groups to the TS3 serveradmin group, so everyone that is a administrator in your forum also becomes a serveradmin at your TS3 server.
* Edit the Administrator group
* go to the new "Teamspeak 3" tab
* go to TS3 and lookup the group ID of your serveradmin group, e.g. 35
* put "35" in the "Teamspeak 3 Server Group ID" field
* you could stop here and save now, which would trigger the sync task
* or you could also setup a channel group, e.g. you have a "Admin only" channel in TS3 and want limit access to that channel by other means than the server group, e.q. for moderators as well which aren't serveradmins in TS3
* go to TS3 and figure the required values for the "Teamspeak3 Channel Data" section and put them in there
* save
* wait a bit (you might want to turn on the "see serverquery users" settings in your TS3 client profile for that server)
* done

The plugin kinda slows down the group editing as it always syncs on save. 
If users add their ID to the custom profile fields the sync isn't triggered instantly, instead it is started about every 30 minutes. You can change that interval in the tast manager section of the forum.

The plugin also writes a logfile to the cache folder of the forum, so if things aint working properly, check there first.
