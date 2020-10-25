<?php

class ManageStates {

    private static $waiting_for_description_file = "./state/waiting_for_description.txt";
    private static $waiting_for_script_file_file = "./state/waiting_for_script_file.txt";
    private static $description_file = "./state/description.txt";

    // Waiting for description when creating new script service.
    public static function get_waiting_for_description() {
        $state = file_get_contents(self::$waiting_for_description_file);
        
        return $state == "1" ? true : false;
    }

    public static function set_waiting_for_description($state) {
        $data = $state == true ? "1" : "0";
        file_put_contents(self::$waiting_for_description_file, $data);
    }

    // Waiting for script file when creating new script service.
    public static function get_waiting_for_script_file() {
        $state = file_get_contents(self::$waiting_for_script_file_file);
        
        return $state == "1" ? true : false;
    }

    public static function set_waiting_for_script_file($state) {
        $data = $state == true ? "1" : "0";
        file_put_contents(self::$waiting_for_script_file_file, $data);
    }

    // Store and access to the description when creating new script service.
    public static function get_description() {
        $description = file_get_contents(self::$description_file);
        return $description;
    }

    public static function set_description($description) {
        file_put_contents(self::$description_file, $description);
    }

    public static function reset_states() {
        self::set_waiting_for_description(false);
        self::set_waiting_for_script_file(false);
        self::set_description("");
    }
}


?>