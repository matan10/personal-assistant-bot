import sys
import os
import subprocess
import json

class new_service:
    def __init__(self):
        self.file_types = ["py"] # Supported script file types.

        # Read config.json file.
        with open(os.path.dirname(__file__) + "/../config.json", "r") as config_file:
            config = json.loads(config_file.read())

        self.scripts_dir = config["SCRIPTS_DIR"] # The directory containing the scripts.
        self.services_dir = config["SERVICES_DIR"] # The directory containing the systemd services.

        self.validate_data()

    def validate_data(self):
        if len(sys.argv) > 1:
            self.script_file_name = sys.argv[1] # The name of the script file including the extension.
            self.script_name = self.script_file_name.split(".")[0] # The name of the script file without the extension.
            self.script_name_extension = self.script_file_name.split(".")[-1] # The extension of the script file.

            if len(sys.argv) > 2:
                self.script_description = sys.argv[2]
            else:
                self.script_description = "No_description"

            if self.script_name_extension in self.file_types: # Check if the script file type is supported.
                if os.path.isfile(self.scripts_dir + self.script_file_name): # Check if the script file is exists.
                    #print("Data validated successfully.")
                    self.create_service_file()

        else: 
            #print("You have to provide script name.")
            print("false")

    def create_service_file(self):
        #print("Creating the service file...")
        service_file = \
'''[Unit]
Description={}

[Service]
User=matan
Group=matan
Restart=always
ExecStart=/usr/bin/python3 {}{}

[Install]
WantedBy=multi-user.target '''.format(self.script_description, self.scripts_dir, self.script_file_name)
                    
        # Create the service file.
        self.service_file_name = self.script_name + '.service'
        with open(self.services_dir + self.service_file_name, "w") as file:
            file.write(service_file)

        #print("Service file created.")

        self.run_the_new_systemd_service()

    def run_the_new_systemd_service(self):
        daemon_reload_command = ["sudo", "systemctl", "daemon-reload"]
        enable_service_command = ["sudo", "systemctl", "enable", self.services_dir + self.service_file_name]

        #print("Enabling the service...")
        proc = subprocess.Popen(daemon_reload_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        out = out.decode()
        err = err.decode()

        if err == "":
            
            subprocess.Popen(enable_service_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

            #print("Service enabled successfully.")
            print("true")

        else:
            #print("Error with the command 'daemon-reload'.")
            print("false")


new_service()