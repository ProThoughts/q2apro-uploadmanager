# q2apro-uploadmanager
Question2Answer Plugin: A complete upload manager for question2answer with upload details and image rotate features

## Features

- easy to use upload manager, giving you an overview of all uploads within a specified timespan
- control in the admin options who can access the page (experts, moderators, admins)
- checks if the uploaded files are used within posts, pages or as avatar
- all files are listed with:
  - upload date
  - original file name
  - file preview for images
    - - blob id
  - file size
  - file location (database or FTP)
  - uploader
  - hint if used as avatar
- you can enable:
  - showing all images directly in the upload table
  - click an image and it will open in a lightbox
  - showing all post links where the uploaded files can be found
- by default if you mouse over an uploaded image it loads in a tooltip box
- you can delete single files or you can delete all unused files (via Ajax)
- you can filter the uploads by user
- Special feature: you can rotate JPG images by only one click!
- available languages: en, de
- the plugin was tested with blob tables holding more than 30 000 entries

## Installation

- Download the ZIP file.
- Important: Make a full backup of your q2a database before installing the plugin.
- Extract the folder q2apro-uploadmanager from the ZIP file.
- Move the folder q2apro-uploadmanager to the qa-plugin folder of your Q2A installation.
- Use your FTP-Client to upload the folder q2apro-uploadmanager into the qa-plugin folder of your server.
- Navigate to your site, go to Admin -> Plugins and check if the plugin "q2apro Upload Manager" is listed.
- Change the plugin settings to meet your needs, and save the changes.
- Congratulations, your new plugin can now be accessed at yourforum.com/uploadmanager

## Disclaimer / Copyright ##

This is beta code. It is probably okay for production environments, but may not work exactly as expected. 
You bear the risk. Refunds will not be given!

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

All code herein is OpenSource. Feel free to build upon it and share with the world.
