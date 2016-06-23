<?php
/*
 * A USSD Class to manage the responses and stored submitted data
 * author: Darren Buckley <dmaildot84@gmail.com>
 * 
 * Designed with a dynamic nature to make amendments easy.
 * 
 * $this object values
 * ->status (session callback)
 * ->data [array] (received data)
 * ->session (emulated session data via a text file)
 */
class ussd_callback {
    
    var $get_data; // Received RAW GET 
    var $callback; // Response Message
    var $track; // This includes session data and tracks your place in the menu.
    
    function __construct() {
        //Include the database connection
        require_once ($_SERVER["DOCUMENT_ROOT"] . "/ussd/control/setup.php"); 
    }
    
    /* 
     * Request function: Primary response function.
     * The Get function is converted into an object. This makes it easy to convert to 
     * a POST or XML type in the future. 
     * 
     * PARAMS: $get_data(string): Raw GET data received and converts to the structured object.
     */
    function request($get_data) {
        unset($callback);
        $this->status = 1;
       //Convert the $_GET data to easy to use varibles.
       parse_str($get_data, $this->data);
	   
       $this->data['type'] = ($this->data['request'] == "0") ? '1' : $this->data['type'];
       if (isset($this->data['type']) && isset($this->data['sessionid'])) {
       
           $this->session_file = ($_SERVER["DOCUMENT_ROOT"] . "/ussd/session_log/" . $this->data['sessionid'] . ".txt");
           if (!(file_exists($this->session_file))) {
               $message = "sessionid=" . $this->data['sessionid'] . "&" ;
               file_put_contents($this->session_file, $message, FILE_APPEND);
           } else {
               $this->session_extract();
           }
           
           $callback = $this->response();
       }

       return $callback;
    }
    /*
     * Response function: Welcome message and main menu options. 
     * This Function also structures the response.
     */
    private function response() {
        unset($callback);
        //This case checks the data type varible, 
        //1 will always be the welcome!
        //2 is any other response
        if (isset($this->session["track"])) {
            $this->data["type"] = 2;
        }
        
        if (isset($this->data["type"])) {
            switch ($this->data["type"]) {
                case '1': 
                    $callback = "Welcome, please select\n 1: Register \n 2: Exit"; // Unrelated to this case set, don't get confused (option replies are in user_reply)
                    break;
                case '2': 
                    if (is_numeric($this->data["request"]["0"])) {
                        $track = (empty($this->session["track"]["0"])) ? $this->data["request"]["0"] : $this->session["track"]["0"];
                    }
                    $callback = $this->user_reply($track);
                    break;
                default :
                    $callback = end_session();
            }
        }

        return "text=" . $callback . "&session=" . $this->status;
    }
    /*
     * User reply function: Evaluates the main menu responses and redirects appropriatly.
     * 
     * PARAMS: $track(string): Menu tracker
     */
    private function user_reply($track) {
        unset($callback);
        
        if (!isset($this->session["track"][0]) && (is_numeric($this->data["request"]))) {
            $message = "track[]=" . $this->data["request"] . "&";
            file_put_contents($this->session_file, $message, FILE_APPEND);
            $this->session["track"][0] = $this->data["request"]["0"];
        }
        
        $first_track = $this->session["track"][0];
        switch ($first_track) {
            case '1':
                $callback = $this->register();
                break;
            case '2':
                $callback = $this->end_session();
                break;
            default:
                $callback = "How embarrassing, Something went wrong! \nPress 0 to return to the main menu";
                unset($this->session);
                unset($track);
                unlink($this->session_file);
        }

        return $callback;
    }
    
    /*
     * Register function: Provides the registration questions and saves them into a "text session"
     * The menu option is decided by the count of the Session array
     */
    private function register() {
        if (!is_numeric($this->data["request"])) {
            $message = "user_details[]=" . $this->data["request"] . "&";
            file_put_contents($this->session_file, $message, FILE_APPEND);
            $this->session["user_details"][] = $this->data["request"];
        }
        
        $user_details = $this->session["user_details"];
        $user_entry = (count($user_details));
        switch ($user_entry) {
            case '0' :
                $callback = "Please enter your Name: ";
                break;
            case '1' :
                $callback = "Please enter your Surname: ";
                break;
            case '2' :
                $callback = $this->update_database();
                unset($this->session["user_details"]); //Consider unsetting after database entry confirmed
                break;
        }
        return $callback;
    }
    
    /*
     * Update database function: 
     * Simply we don't want duplicates, so a insert method wasn't enough.
     * An ID would be a more Unique field but the Name/Surname will do.
     * 
     * Updates or Inserts into the database. 
     */
    private function update_database() {
        //Set the  varibles to keep everything looking dynamic
        $this->data['name'] = $this->session['user_details'][0];
        $this->data['surname'] = $this->session['user_details'][1];
                
        $select_query = "SELECT id FROM users WHERE `name` = '{$this->data['name']}' AND `surname` = '{$this->data['surname']}'";
        $select_response = $this->con->query($select_query);
        $select_id = $select_response->fetchColumn();
            
        if ($select_id !== false) {
            $update_query = "UPDATE users 
            SET cell_number = ?, network = ?
            WHERE id = ?";
            
            $update_query = $this->con->prepare($update_query);
            $update_query->bindParam(1, $this->data['msisdn'], PDO::PARAM_STR, 45);
            $update_query->bindParam(2, $this->data['mno'], PDO::PARAM_STR, 45);
            $update_query->bindParam(3, $select_id);
            
            if ($update_query->execute()) {
                $callback = "We located a previous registration\n we processed an update on your details successfully!\n Press 2 to exit";
                unlink($this->session_file);
            } else {
                $callback = "Oh oh, something went wrong!\n\n Please try again later.";
                $error_info = <<<ERR
                Update|{$this->data['name']}|{$this->data['surname']}|{$this->data['msisdn']}|{$this->data['mno']}
ERR;
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/ussd/control/opt.txt', $error_info . "\n" . PDO::ERRMODE_EXCEPTION . "\n\n" , FILE_APPEND | LOCK_EX);
            }

        } else {
            
            $insert_query = "INSERT INTO users 
            (name, surname, cell_number, network, create_date)
            VALUES 
            (?, ?, ?, ?, NOW() + INTERVAL 6 HOUR)";

            $insert_query = $this->con->prepare($insert_query);
            $insert_query->bindParam(1, $this->data['name'], PDO::PARAM_STR, 45);
            $insert_query->bindParam(2, $this->data['surname'], PDO::PARAM_STR, 45);
            $insert_query->bindParam(3, $this->data['msisdn'], PDO::PARAM_STR, 45);
            $insert_query->bindParam(4, $this->data['mno'], PDO::PARAM_STR, 45);
            
            if ($insert_query->execute()) {
                $callback = "Thank you, you have registered successfully!\n Press 2 to exit";
                unlink($this->session_file);
            } else {
                $callback = "Oh oh, something went wrong!\n\n Please try again later.";
                $error_info = <<<ERR
                Update|{$this->data['name']}|{$this->data['surname']}|{$this->data['msisdn']}|{$this->data['mno']}
ERR;
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/ussd/control/opt.txt', $error_info . "\n" . PDO::ERRMODE_EXCEPTION . "\n\n" , FILE_APPEND | LOCK_EX);
            }
        }

        unset($this->session);
        unset($track);
        //
        return $callback;
    }
    
    /*
     * Extracts the content from the stored text file.
     * I realise there is most definately a better way to use the Menu.
     * This will have to do for now 
     */
    private function session_extract() {
        unset($this->session);
        $session_extract = file_get_contents($this->session_file);
        parse_str($session_extract, $this->session);
    }
    
    /*
     * End session function: Closes the session with a goodbye message.
     */
    private function end_session() {
        //Destroy Session
        unlink($this->session_file);
        
        $this->status = 0;
        return "Thank you for using our mobile menu, Goodbye";

    }
}

?> 