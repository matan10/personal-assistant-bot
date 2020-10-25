# Personal Assistant Bot

**Personal Assistant Bot** is a telegram bot that helps you manage your server.

**Features:**
* **Server Status** - to check if the server is awake.
* **Systemd Services** - allows you to run scripts on your server with systemd service. (currently supports only 'py' files.)

## Configuration

* Create a new folder in your systemd services folder like:
    * `mkdir /etc/systemd/system/personal_assistant_bot_services`
* Create `config.json` file in the main dir and copy that code into the file:

        {
            "API_TOKEN": "<bot-api-token>", 
            "MY_ID": <your-telegram-id (you can get it by using this bot: [ShowID_bot](https://t.me/showid_bot))>,
            "SCRIPTS_DIR": "/var/www/html/Personal-Assistant-Bot/scripts/",
            "MANAGEMENT_SCRIPTS": "/var/www/html/Personal-Assistant-Bot/scripts_management/",
            "SERVICES_DIR": "/etc/systemd/system/personal_assistant_bot_services/"
        }

    * Edit the paths to yours.

* Run this command: `sudo chmod 777 personal_assistant_bot.php`
* Run this command: `sudo visudo` and add this two lines in the end of the file:

        www-data ALL=NOPASSWD: /usr/bin/python3 /var/www/html/Personal-Assistant-Bot/scripts_management/manage_service.py*
        www-data ALL=NOPASSWD: /usr/bin/python3 /var/www/html/Personal-Assistant-Bot/scripts_management/new_service.py*
        
    * The `www-data` stands for the username of your web server (such as apache, nginx and so on...)
    * The `/usr/bin/python3` stands for your python location in the server
    * The path after that stands for the `scripts_management` dir in your server. change it to your directory location
    * This two lines allows the `personal_assistant_bot.php` file run the management scripts.
* Don't forget to set telegram webhook into the `personal_assistant_bot.php` file.
    * `https://api.telegram.org/bot<api-token>/setWebhook?url=<your-https-web-server-url>`


## Changelog

* **2020-10-25**: Initial release