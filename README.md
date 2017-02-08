Y.A.A.M
=======

(Yet Another Asterisk Manager)

This is a web interface for managing an Asterisk server. It is not meant at all to be an alternative to other GUI such as FreePBX. It is more like an assistant, for those who prefer to edit configuration files manually but needs a web interface for quick access to server logs, call logs, etc.

![Screenshot : Call-log](/screenshots/call_log_filters.jpg)

Users are only allowed to view call logs, listen to voice message or edit phonebook records for their extension only. It is possible to give certain users permission to access to those functions for all users as well.

![Screenshot : Edit users](/screenshots/edit_user.jpg)

Y.A.A.M also has a plugin system, so you can add or remove features you don’t need.

Available plugins
-----------------

 - Call log viewer
 - Call treatment
 - Voicemail (ODBC)
 - Phone book
 - Sys Admin (Log viewer, channel status, run CLI commands)
 - Originate call
