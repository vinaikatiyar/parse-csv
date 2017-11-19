<?php

class importcsv {

    const FILENAME_EXTENSION_ERROR = 'Sorry, you have attempted to upload an invalid file format. Only csv files are allowed.';
    const FILENAME_SPECIAL_CHARACTER_ERROR = 'Only Alphabets/numbers/./_/- with 50 characters in filename is allowed';
    const EMPTY_FILE_ERROR = 'Empty Header/Data file cannot be uploaded!! Please re-upload it with details';
    const HEADER_MISSING_ERROR = 'Difference in supported headers. Standard Header(s): -';
    const SERVER_ERROR = 'Sorry, Your File could not be uploaded at the moment.Please try again or contact admin if this problem persist';
    const FILESIZE_ERROR = 'File exceeded 2 MB max allowed file size. Please upload a file of less than 2MB in size to proceed.';
    const SUCCESS = 'CSV is parsed successfully. Total Records Inserted: - ';
    const STRING_ERROR = ' String Not Acceptable (Click Here):-';
    const UPLOAD_ERROR = ' Error(s) in uploaded CSV (Click Here):-';
    const VALIDATING_MSG = ' Successfully validated CSV';
    const COMMON_WORDS = 'Allowed Character Lists And Blacklist Words Have Common Words:';

    private $lineerr = array();
    private $err;
    private $data = array();
    private $partialfine_data = array();
    public $finalarray = array();
    public $header_val = array();
    private $key_data = array();
    
    /**
     * 
     * @param array $header_arr
     * @return boolean (false) or array(key_data)
     */
    /**
     * 
     * @param array $header_arr
     * @return boolean (false) or array(key_data)
     */
    private function get_all_mandatory_fields($header_arr) {
        if (empty($header_arr)) {
            return false;
        }
        foreach ($header_arr as $k => $value) {
        	if(in_array($value[1], array('M'))){
                array_push($this->key_data, $k);
            }
        }
        return $this->key_data;
    }

    /**
     * 
     * @param string $string
     * @return boolean
     */
    public static function is_stringmatch($string, $allowed_char_list=array() ){
        $allowed_char_list_string = implode('', $allowed_char_list);
        if (empty($string) || !preg_match('/^[a-zA-Z0-9' . $allowed_char_list_string . ']+$/i', $string)) {
            $data = substr($string, 0, 40);
            return $data;
        } else {
            return true;
        }
    }
    
    /**
     * 
     * @param string $string
     * @return boolean
     */
    public static function is_blacklist_word($text,$findme) {
       if(!empty($findme)){
       foreach ($findme as $word) {
           $pos = stripos($text, $word);
           if ($pos !== false) {
               return $word;
           } else {
               continue;
           }
       }
        return true;
       }else{
           return true;
       }
    }

    /**
     * 
     * @param numeric $numeric
     * @return boolean
     */
    public static function is_numeric($numeric) {

        if (is_numeric($numeric)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $date
     * @return boolean
     */
    public static function is_date($date) {
        $date_regex='/^(((0[1-9]|[12]\d|3[01])\/(0[13578]|1[02])\/((19|[2-9]\d)\d{2}))|((0[1-9]|[12]\d|30)\/(0[13456789]|1[012])\/((19|[2-9]\d)\d{2}))|((0[1-9]|1\d|2[0-8])\/02\/((19|[2-9]\d)\d{2}))|(29\/02\/((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))$/';
        if (!preg_match($date_regex, $date)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 
     * @param type $dateTime
     * @return boolean
     */
    public static function isDateTime($dateTime) {
        $date_regex='/^(((0[1-9]|[12]\d|3[01])\/(0[13578]|1[02])\/((19|[2-9]\d)\d{2}))|((0[1-9]|[12]\d|30)\/(0[13456789]|1[012])\/((19|[2-9]\d)\d{2}))|((0[1-9]|1\d|2[0-8])\/02\/((19|[2-9]\d)\d{2}))|(29\/02\/((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))) (0[0-9]|1[\d]|2[0-3]):(0[0-9]|[1-5][\d])$/';
        if (preg_match($date_regex, $dateTime)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 
     * @param array $records of csv data
     * @param array $header_arr of csv headers type
     * $param boolean $oyam 
     * @param array $findme of blacklist characters array
     * @param array $allowed_char_list of allowed characters in csv data 
     * @return array|boolean
     */
    public function validate_csv_data($records, $header_arr, $oyam = false,$findme=array(), $allowed_char_list=array(),$dateTime = FALSE,$flag_seq_str="") {
        
        $kt = 1;
        if (empty($records) || empty($header_arr)) {
            return false;
        }
        if(!empty($allowed_char_list) && (!empty($findme) && $oyam == true)){
            $find_common = array_intersect($allowed_char_list, $findme);
            if(!empty($find_common)){
                 $this->err = importcsv::COMMON_WORDS . implode(',',$find_common);
                 array_push($this->lineerr, $this->err);

                 $finalarray['lineerr'] = $this->lineerr;
                 return $finalarray;
            }
         }
         
        foreach ($records as $k => $row) {
            $r_no = $kt + 1;
            $flag = 0;
            if (!empty($row['TP_MAX']) && !empty($row['SP_MAX'])) {
                if ($row['TP_MIN'] >= $row['TP_MAX']) {
                    $this->err = "Row No $r_no TP MAX should be greater than TP MIN ";
                    array_push($this->lineerr, $this->err);
                    $flag = 1;
                }
                if ($row['SP_MIN'] >= $row['SP_MAX']) {
                    $this->err = "Row No $r_no SP MAX should be greater than SP MIN ";
                    array_push($this->lineerr, $this->err);
                    $flag = 1;
                }
            }

            foreach ($row as $key => $val) {
                //$flag = 0;
               // $r_no = $kt + 1;
                $newval = $header_arr[$key][0];
                $priorityFlag = $header_arr[$key][1];
				$val = trim($val);
                if($priorityFlag == 'B' && empty($val)){
					continue;
                }				
                if ($newval == 'string') {
                    if(!empty($val) || !isset($header_arr[$key][2]) || $header_arr[$key][2] !== true){  
                        $data = self::is_stringmatch($val, $allowed_char_list);
                        if ($data !== true) {
                            if (empty($data)) {
                                $this->err = "ROW no $r_no COLUMN $key string type is not supported ";
                                array_push($this->lineerr, $this->err);
                                $flag = 1;
                            } else {
                                $this->err = "ROW no $r_no COLUMN $key string type is not supported " . "(" . $data . ")";
                                array_push($this->lineerr, $this->err);
                                $flag = 1;
                            }
                        }

                        if ($oyam == true) {
                            $word = self::is_blacklist_word($val,$findme);
                            if ($word !== true) {
                                $this->err = "ROW no $r_no COLUMN $key Black Listed word  " . $word . " not supported";
                                array_push($this->lineerr, $this->err);
                                $flag = 1;
                            }
                        }
                    }
                }
                if ($newval == 'int' || $newval == 'float') {
                    if (self::is_numeric($val) == false) {

                        $this->err = "ROW no $r_no COLUMN $key is not numeric";
                        array_push($this->lineerr, $this->err);
                        $flag = 1;
                    }
                }
                if ($newval == 'date') {
                    $resDateTime = $this->isDateTime($val);
                    $resIsdate = self::is_date($val);
                    if ((empty($dateTime) && empty($resIsdate)) || (!empty($dateTime) && empty($resDateTime))) {
                        
                        $format = !empty($dateTime)?"dd/mm/yyyy HH:mm":"dd/mm/yyyy";
                        $this->err = "ROW no $r_no COLUMN $key supported date is ".$format;
                        array_push($this->lineerr, $this->err);
                        $flag = 1;
                    }
                }
                
		if ($newval == 'char') {
                    if (strlen($val) != 1) {
                        $this->err = "ROW no $r_no COLUMN $key is not character";
                        array_push($this->lineerr, $this->err);
                        $flag = 1;
                    }
                }
				
                if($newval == 'flag' && !empty($flag_seq_str))       
                {   
                    if(!empty($val) || !isset($header_arr[$key][2]) || $header_arr[$key][2] !== true){
                        require_once("class.bitmap.php");
                        $bitMapObj = new bitmap($flag_seq_str,$val);
                        $res = $bitMapObj->convert();

                        if(is_array($res))
                            foreach($res as $v){
                                $this->err = "ROW no $r_no COLUMN $key ".$v;
                                array_push($this->lineerr, $this->err);
                                $flag = 1;
                            }
                    }
                }
            }
            if ($flag == 0) {
                $this->data = $row;
                array_push($this->partialfine_data, $this->data);
            }
            $kt++;
        }

        $finalarray['lineerr'] = $this->lineerr;
        $finalarray['partialfine_data'] = $this->partialfine_data;
        $finalarray['error_count'] = count($this->lineerr);
        return $finalarray;
    }
 
    /**
     * 
     * @param array $header_arr : - Config header
     * @param array $sanitized_header_arr :- header passed in csv
     * @return boolean
     */
    public function csv_header_validation($header_arr, $sanitized_header_arr) {       
        $sanitized_header_arr = array_map("strtoupper", $sanitized_header_arr);
        
        $headers_name=array_keys($header_arr);
        $diff_arr=array_diff($sanitized_header_arr,$headers_name);
        if(!empty($diff_arr)){ 
           return false; 
        }
        $this->key_data = $this->get_all_mandatory_fields($header_arr);
       
        if ($this->key_data) {
            foreach ($this->key_data as $value) {
                if (!in_array($value, $sanitized_header_arr)) {
                    
                    return false;
                }
            }
            return true; 
        }
        else{ 
            if(in_array("MARKETPLACE_PRODUCT_VISIBILITY", $headers_name)) 
                return true ;
            else
                return FALSE;
        }
    }
    
    /**
     * 
     * @param array $header_arr : - Config header
     * @param array $sanitized_header_arr :- header passed in csv
     * @return mixed
     */
    public function get_csv_invalid_headers($header_arr, $sanitized_header_arr) {
        $sanitized_headers_array = array_map("strtoupper", $sanitized_header_arr);        
        $invalid_headers = array();
        $headers_name = array_keys($header_arr);
        $diff_arr = array_diff($sanitized_headers_array, $headers_name);        
        if (!empty($diff_arr)) {
            $invalid_headers['Unknown_Headers'] = array_values($diff_arr);
            return $invalid_headers;
        }
        $this->key_data = $this->get_all_mandatory_fields($header_arr);        
        if ($this->key_data) {
            foreach ($this->key_data as $value) {
                if (!in_array($value, $sanitized_headers_array)) {
                    $invalid_headers['Missing_Mandatory_Headers'][] = $value;
                }
            }if (!empty($invalid_headers)) {
                return $invalid_headers;
            }
            return true;
        } else {
            return true;
        }
    }

    /**
     * 
     * @param array $allowed_extensions
     * @param string $extension
     */
    public function file_extenstion_check($allowed_extension, $file_location) {

        if (empty($file_location) || empty($allowed_extension)) {
            return false;
        }

        if (in_array(pathinfo($file_location, PATHINFO_EXTENSION), $allowed_extension)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 
     * @param string $filename 
     * @param array $allowed_char_list
     * @return boolean 
     */
    public static function filename_validation($filename, $allowed_char_list=array()) {
        $regular_exp = "/^[a-zA-Z0-9_.-]+$/";
        if(!empty($allowed_char_list)){
            $allowed_char_list_string = implode('', $allowed_char_list);
            $regular_exp = "/^[a-zA-Z0-9_.'.$allowed_char_list_string.'-]+$/";
        }
        if (preg_match($regular_exp, $filename)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param array $data
     * @return array $value
     */
    public static function remove_junk_data($data) {
        $value = array();
        foreach ($data as $val) {
            $csvdata = iconv("UTF-8", "ASCII", trim($val));
            array_push($value, $csvdata);
        }
        return $value;
    }
}
?>
