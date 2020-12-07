<?php

require_once("./manage_states.php");

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Jerusalem');


// Read configuration file
$config = file_get_contents("./config.json");
if ($config === false) exit("Error: You have to create configuration file ('config.json')");

$config_json = json_decode($config, true);
if ($config_json === null) exit("Error: There is an error in your config.json file.");

define("API_TOKEN", $config_json["API_TOKEN"]);
define("MY_ID", $config_json["MY_ID"]);
define("SCRIPTS_DIR", $config_json["SCRIPTS_DIR"]);
define("MANAGEMENT_SCRIPTS", $config_json["MANAGEMENT_SCRIPTS"]);


$manage_service_script = MANAGEMENT_SCRIPTS."manage_service.py";
$new_service_script = MANAGEMENT_SCRIPTS."new_service.py";
$phone_number_to_person_script = "phone_number_to_person.py";

ini_set('display_errors', 1);

$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);

function curlPost($method, $datas=[]){
	$urll = "https://api.telegram.org/bot".API_TOKEN."/".$method;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urll);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
	
	$res = curl_exec($ch);
	file_put_contents("log.txt", $res);
	if (curl_error($ch)){
		var_dump(curl_error($ch));
		curl_close($ch);
	}else{
		curl_close($ch);
		return json_decode($res, TRUE);
	}
}

function _log($msg) {
	file_put_contents('logs.txt', $msg, FILE_APPEND | LOCK_EX);
}

function sendMessage($message, $chat_id, $reply_markup=NULL){
	$postData = array(
		"chat_id" => $chat_id,
		"text" => $message,
		"parse_mode" => "markdown",
		"reply_markup" => $reply_markup,
		"disable_web_page_preview" => true
	);
	$out = curlPost("sendMessage", $postData);
	return $out;
}

function sendMessageWPM($message, $chat_id){
	$postData = array(
		"chat_id" => $chat_id,
		"text" => $message,
		"disable_web_page_preview" => true
	);
	$out = curlPost("sendMessage", $postData);
	return $out;
}

function editMessage($message, $chat_id, $message_id, $reply_markup = NULL){
	$postData = array(
		"chat_id" => $chat_id,
		"message_id" => $message_id,
		"text" => $message,
		"parse_mode" => "Markdown",
		"reply_markup" => $reply_markup,
		"disable_web_page_preview" => true
	);
	$out = curlPost("editMessageText", $postData);
	return $out;
}


// Regular message.
$chat_id = $update["message"]["chat"]["id"];
$text = $update["message"]["text"];

// Inline
$callback_data = $update["callback_query"]["data"];
$message_id = $update["callback_query"]["message"]["message_id"];
$call_chat_id = $update["callback_query"]["message"]["chat"]["id"];
$inline_message_text = $update["callback_query"]["message"]["text"];

// Document
$document = $update["message"]["document"];

// Scripts file types supported
$file_types = array("py");


function download_and_save_script_file($document) {

	$file_name = $document["file_name"];
	$file_id = $document["file_id"];

	$postData = array(
		"file_id" => $file_id,
	);
	$out = curlPost("getFile", $postData);
	$file_path = $out["result"]["file_path"];
	
	$download_url = "https://api.telegram.org/file/bot".API_TOKEN."/".$file_path;

	if (!is_dir('scripts')) { // Create 'scripts' dir if not exists.
		mkdir('scripts',  0777, true);
	}

	$log = file_put_contents("./scripts/".$file_name, file_get_contents($download_url));
	if ($log !== false) {
		return true;
	} else {
		return false;
	}

}

function save_new_script_file($document, $description) {
	$log = download_and_save_script_file($document);
	if ($log === true) {

		$script_file_name = $document["file_name"];
		$description = str_replace(" ", "_", $description);

		global $new_service_script;
		$out = shell_exec("sudo python3 $new_service_script $script_file_name $description");
		_log($out);
		if ($out === "true\n") {
			return true;
		} else {
			return false;
		}

	} else {
		return false;
	}
}



// Main keyboard.
$main_keyboard = json_encode(array(
    "inline_keyboard" => array( // That array is for the buttons.
		array( // That array is for the line.
			array("text" => "Server Status", "callback_data" => "server_status"), // That array is for the column.
			array("text" => "Systemd Services", "callback_data" => "systemd_services")
		),
		array(
			array("text" => "Phone Number to Person", "callback_data" => "phone_to_person")
		)
	)
));

// Back to main menu keyboard.
$back_to_main_menu_keyboard = json_encode(array(
    "inline_keyboard" => array( // That array is for the buttons.
		array( // That array is for the line.
			array("text" => "👈 Back to Main Menu", "callback_data" => "main_menu") // That array is for the column.
		)
	)
));

// Cancel keyboard.
$cancel_keyboard = json_encode(array(
    "inline_keyboard" => array(
		array(
			array("text" => "⛔ Cancel", "callback_data" => "main_menu")
		)
	)
));

// Systemd Services keyboard.
$services_keyboard = json_encode(array(
    "inline_keyboard" => array(
		array(
			array("text" => "Scripts", "callback_data" => "scripts"),
			array("text" => "New Script", "callback_data" => "new_script")
		),
		array(
			array("text" => "👈 Back to Main Menu", "callback_data" => "main_menu")
		)
	)
));

// Cancel new script keyboard.
$cancel_new_script_keyboard = json_encode(array(
    "inline_keyboard" => array(
		array(
			array("text" => "⛔ Cancel", "callback_data" => "systemd_services")
		)
	)
));

function build_file_types_keyboard() {
	global $file_types;

	$file_types_buttons = array();
	foreach($file_types as $file_type) {
		array_push($file_types_buttons, array(array("text" => $file_type, "callback_data" => "new_script_$file_type")));
	}

	array_push($file_types_buttons, array(
		array("text" => "👈 Back", "callback_data" => "systemd_services"),
		array("text" => "🏠 Main Menu", "callback_data" => "main_menu"),
	));

	// Scripts keyboard.
	$file_types_keyboard = json_encode(array(
		"inline_keyboard" => $file_types_buttons
	));

	return $file_types_keyboard;
}

function get_scripts() {
	global $file_types;

	$files_and_dirs = scandir(SCRIPTS_DIR);
	$files = array();
	foreach ($files_and_dirs as $file) {
		if (in_array(end(explode('.', $file)), $file_types)) { // Get the file extension and check if its exists in 'file_types' array.
			array_push($files, $file);
		}
	}

	return $files;
}

function build_scripts_keyboard() {
	global $manage_service_script;
	
	$scripts = get_scripts();
	$scripts_buttons = array();
	foreach($scripts as $script) {
		$out = shell_exec("sudo python3 $manage_service_script status $script");
		$script_status = $out == "running\n" ? "🟢 " : "🔴 ";
		array_push($scripts_buttons, array(array("text" => $script_status.$script, "callback_data" => $script)));
	}

	array_push($scripts_buttons, array(
		array("text" => "👈 Back", "callback_data" => "systemd_services"),
		array("text" => "🏠 Main Menu", "callback_data" => "main_menu"),
	));

	// Scripts keyboard.
	$scripts_keyboard = json_encode(array(
		"inline_keyboard" => $scripts_buttons
	));

	return $scripts_keyboard;
}

function build_service_manage_keyboard($script_name) {
	$manage_service_keyboard = json_encode(array(
		"inline_keyboard" => array(
			array(
				array("text" => "Start 	🟢", "callback_data" => "start_$script_name")
			),
			array(
				array("text" => "Stop 	🔴", "callback_data" => "stop_$script_name")
			),
			array(
				array("text" => "Status 	📃", "callback_data" => $script_name)
			),
			array(
				array("text" => "👈 Back", "callback_data" => "scripts"),
				array("text" => "🏠 Main Menu", "callback_data" => "main_menu")
			)
		)
	));

	return $manage_service_keyboard;
}



// Messages
$welcome_message = "Hello!\nWelcome to your personal assistant.\nI'm here to help you with all your needs.";
// Server status
$server_is_on_message = "Server is on ✅";
// Systemd services
$services_welcome_message = "Here you can manage your scripts.";
$services_scripts_message = "Here is the list of all of your scripts 👇\n";
$choose_file_type_message = "Choose script file type from the list below 👇";
$get_description_message = "Send service description:";
$get_script_file_message = "Send the script file:";
$new_script_added_successfully_message = "Congratulations!\nThe script added successfully.\nYou can manage the script in the 'Systemd Services' tab.";
$new_script_added_failed_message = "Sorry, but there was a problem adding the script...";
$document_without_purpose_message = "Sorry, but I didn't understand what the document is intended for...";
// Phone number to Person
$send_phone_number_message = "Send phone number:";


// Text Messages.

if ($chat_id == MY_ID) {
	if (isset($document)) { // Document
		if (ManageStates::get_waiting_for_script_file() == true) {

			$description = ManageStates::get_description();
			ManageStates::reset_states();

			$log = save_new_script_file($document, $description); // The function download the script, save it into 'scripts' folder and create new service.
			if ($log === true) {
				sendMessage($new_script_added_successfully_message, $chat_id);
				sendMessage($services_welcome_message, $chat_id, $services_keyboard);
			} else {
				sendMessage($new_script_added_failed_message, $chat_id);
				sendMessage($services_welcome_message, $chat_id, $services_keyboard);
			}

		} else {
			sendMessage($document_without_purpose_message, $chat_id, $back_to_main_menu_keyboard);
		}
	} else { // Text message
		if ($text == "/start") {

			ManageStates::reset_states();
			sendMessage($welcome_message, $chat_id, $main_keyboard);
		} elseif (ManageStates::get_waiting_for_description() === true) {

			// Declare expect to file.
			ManageStates::set_waiting_for_description(false);
			ManageStates::set_waiting_for_script_file(true);

			$description = $text;
			ManageStates::set_description($description);
			sendMessage($get_script_file_message, $chat_id, $cancel_new_script_keyboard);

		} elseif (ManageStates::get_waiting_for_phone_number() == true) {

			// Got phone number.
			$phone_number = $text;
			$out = shell_exec("python3 phone_number_to_person.py $phone_number");
			sendMessageWPM(print_r($out, true), $chat_id, $cancel_keyboard);

		}
	}
} else {
    sendMessage("You are not allowed to use this bot.", $chat_id);
}




// Inline.

if (isset($callback_data)) {
	if ($callback_data == "main_menu") {

		ManageStates::reset_states();
		editMessage($welcome_message, $call_chat_id, $message_id, $main_keyboard);

	} elseif ($callback_data == "server_status") {

		editMessage($server_is_on_message, $call_chat_id, $message_id, $back_to_main_menu_keyboard);

	} elseif ($callback_data == "phone_to_person") {

		ManageStates::set_waiting_for_phone_number(true);
		editMessage($send_phone_number_message, $call_chat_id, $message_id, $cancel_keyboard);

	} elseif ($callback_data == "systemd_services") {

		ManageStates::reset_states(); // Turn to 'false' after choose 'cancel' when creating new script.

		editMessage($services_welcome_message, $call_chat_id, $message_id, $services_keyboard);

	} elseif ($callback_data == "scripts") {

		$scripts_keyboard = build_scripts_keyboard();

		editMessage($services_scripts_message, $call_chat_id, $message_id, $scripts_keyboard);

	} elseif ($callback_data == "new_script") {

		$file_types_keyboard = build_file_types_keyboard();
		
		editMessage($choose_file_type_message, $call_chat_id, $message_id, $file_types_keyboard);

	} elseif (substr($callback_data, 0, 6) == "start_") { // 'start_<script_name>'

		$script_name = explode("_", $callback_data)[1];
		$service_manage_keyboard = build_service_manage_keyboard($script_name);

		$out = shell_exec("sudo python3 $manage_service_script start $script_name");
		_log($out);

		$out = $out == "true\n" ? "🟢 Running" : "Failed to start.";
		editMessage($script_name."\n\n".$out, $call_chat_id, $message_id, $service_manage_keyboard);

	} elseif (substr($callback_data, 0, 5) == "stop_") { // 'stop_<script_name>'

		$script_name = explode("_", $callback_data)[1];
		$service_manage_keyboard = build_service_manage_keyboard($script_name);

		$out = shell_exec("sudo python3 $manage_service_script stop $script_name");
		_log($out);

		$out = $out == "true\n" ? "🔴 Stopped" : "Failed to stop.";
		editMessage($script_name."\n\n".$out, $call_chat_id, $message_id, $service_manage_keyboard);

	} elseif (substr($callback_data, 0, 11) == "new_script_") { // 'new_script_<script_name>'

		global $file_types;
		$script_file_type = explode("_", $callback_data)[2];

		if (in_array($script_file_type, $file_types)) {
			
			// Ask for description.
			ManageStates::set_waiting_for_description(true); // Expect to description in the next message.
			editMessage($get_description_message, $call_chat_id, $message_id, $cancel_new_script_keyboard);

		} else { // Just in case there is an error.
			$file_types_keyboard = build_file_types_keyboard();
			editMessage("Error.\n".$choose_file_type_message, $call_chat_id, $message_id, $file_types_keyboard);
		}

	} else {

		$scripts = get_scripts();

		if (in_array($callback_data, $scripts)) { // Check if this is script name or something else.
			$script_name = $callback_data;

			$service_manage_keyboard = build_service_manage_keyboard($callback_data);
			
			$out = shell_exec("sudo python3 $manage_service_script status $script_name");
			_log($out);

			// Possible returns - 'running', 'enabled' or 'disabled'
			$status_msg = "";
			if ($out == "running\n") {
				$status_msg = "🟢 Running";
			} elseif ($out == "enabled\n") {
				$status_msg = "🔴 Stopped";
			} elseif ($out == "disabled\n") {
				$status_msg = "❌ Disabled";
			}

			editMessage($script_name."\n\n".$status_msg, $call_chat_id, $message_id, $service_manage_keyboard);
		}

	}
}


?>
