# Premium Plugin: Upload Manager

    "You have no overview what your users are uploading. You do not know which files are used or are gone to your database nirvana? You have no idea who uploads what when in which dimension? Then it is time for the q2apro upload manager!" 


# Description

	A complete upload manager for question2answer with upload details, image rotate features and more!


# Features

    - easy to use upload manager, giving you an overview of all uploads within a specified timespan
	- control in the admin options who can access the page (experts, moderators, admins)
	- checks if the uploaded files are used within posts, pages or as avatar
	- all files are listed with: 
		* upload date
		* original file name
		* blob id
		* file size
		* file location (database or FTP)
		* uploader
		* hint if used as avatar
	- you can enable: 
		* showing all images directly in the upload table
		* click an image and it will open in a lightbox
		* showing all post links where the uploaded files can be found
	- by default if you mouse over an uploaded image it loads in a tooltip box
    - you can delete single files or you can delete all unused files (via Ajax)
	- you can filter the uploads by user
    - available languages: en, de
    - the plugin was tested with blob tables holding more than 30 000 entries
	
   
# Installation

	- Important: Make a full backup of your q2a database before installing the plugin.
	- Extract the folder q2apro-uploadmanager from the ZIP file.
	- Move the folder q2apro-uploadmanager to the qa-plugin folder of your Q2A installation.
	- Use your FTP-Client to upload the folder q2apro-uploadmanager into the qa-plugin folder of your server.
	- Navigate to your site, go to Admin -> Plugins and check if the plugin "q2apro Upload Manager" is listed.
	- Change the plugin options to meet your needs, and save the changes.
	- Congratulations, your new plugin can now be accessed at yourforum.com/uploadmanager

   
# Disclaimer

	This code is in use in many q2a forums and has been tested thoroughly. However, please make a full MySQL backup of your data before installing this plugin in production environments. There could be, for instance, another plugin that interferes with this plugin. We cannot accept any claim for compensation if data is lost.


# Copyright

	Copyright © q2apro.com - All rights reserved
	A redistribution of this code is not permitted.
