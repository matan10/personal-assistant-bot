#! /usr/bin/python3

import sys
import os
import subprocess
import json

class manage_service:
    def __init__(self):
        # Read config.json file.
        with open(os.path.dirname(__file__) + "/../config.json", "r") as config_file:
            config = json.loads(config_file.read())

        self.scripts_dir = config["SCRIPTS_DIR"] # The directory containing the scripts.
        self.services_dir = config["SERVICES_DIR"] # The directory containing the systemd services.

        self.start_func_recursive = 0 # After the 'start' function enables the script it runs recursively with 4 tries.

        self.validate_data()

    def validate_data(self):
        if len(sys.argv) > 2:
            self.service_command = sys.argv[1] # 'start', 'stop' or 'status'

            self.script_file_name = sys.argv[2] # The name of the script file including the extension.
            self.script_name = self.script_file_name.split(".")[0] # The name of the script file without the extension.
            self.service_file_name = self.script_name + ".service" # The name of the service file.

            if os.path.isfile(self.scripts_dir + self.script_file_name): # Check if the script file is exists.
                #print("Data validated successfully.")
                self.handle_command()
            else:
                print("Script file does not exist.")
                
        else: 
            print("You have to provide script name and command.")

    def handle_command(self):
        if self.service_command == "start":
            res = self.start()
            self.start_func_recursive = 0 # Reset
        elif self.service_command == "stop":
            res = self.stop()
        elif self.service_command == "status":
            res = self.status()

        print(res)

    def start(self):
        if self.start_func_recursive >= 4:
            return "false"

        start_command = ["sudo", "systemctl", "start", self.service_file_name]
        enable_command = ["sudo", "systemctl", "enable", self.services_dir + self.service_file_name]

        service_status = self.status()

        if service_status == "running": # The service is already running.
            return "true"
        elif service_status == "enabled": # The service is not running.
            
            proc = subprocess.Popen(start_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = proc.communicate()
            err = err.decode()

            return "true" if err == "" else "false"

        elif service_status == "disabled": # The service is not enabled.
            
            if os.path.isfile(self.services_dir + self.service_file_name): # Check the original service file
                subprocess.Popen(enable_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE) # Enable the service file

                return self.start() # 4 tries to start - stored in 'start_func_recursive' varibale

            else:
                return "false"

        else:
            return "false"

    def stop(self):
        stop_command = ["sudo", "systemctl", "stop", self.service_file_name]
        subprocess.Popen(stop_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

        return "true"

    def status(self):
        # Status check:
        #
        # sudo systemctl is-active <service-name>
        # 'active' - if the service is running
        # 'inactive' - if the service is not running, even if its not exists
        # 
        # sudo systemctl is-enabled <service-name>
        # 'enabled' - if the service is enabled (running or not is doesn't matter)
        # otherwise - the service is not enabled or even not exists

        is_active_command = ["sudo", "systemctl", "is-active", self.service_file_name]
        is_enabled_command = ["sudo", "systemctl", "is-enabled", self.service_file_name]

        proc = subprocess.Popen(is_active_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        out = out.decode()
        
        if out == "active\n": # Check if the service is running.
            return "running"
        else: # The service is not running.
            proc = subprocess.Popen(is_enabled_command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            out, err = proc.communicate()
            out = out.decode()
            
            if out == "enabled\n": # Check if the service is exists and enabled.
                return "enabled"
            else:
                return "disabled"

manage_service()