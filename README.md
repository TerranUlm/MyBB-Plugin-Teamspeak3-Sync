# MyBB-Plugin-Teamspeak3-Sync
A plugin for MyBB 1.8.x to sync forum groups to TS3 groups.

**Requires the [Teamspeak 3 PHP Framework](http://addons.teamspeak.com/directory/addon/integration/TeamSpeak-3-PHP-Framework.html) installed in the MyBB Root folder!**

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
