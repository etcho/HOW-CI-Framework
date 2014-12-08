<?php

class HowCore {

    /**
     * Returna um array contendo todas a funções disponíveis para serem usadas nos validates
     * @return array
     */
    static function validationFunctions() {
        $ci = get_instance();
        $ci->load->library("form_validation");
        $ci->load->helper("dds_arquivos");
        $file = array_merge(file("application/libraries/MY_Form_validation.php"), file("system/libraries/Form_validation.php"));
        $excluded_methods = array("__construct", "error_string", "get_error_array", "reset_error_array", "append_error_array", "set_rules", "set_message", "set_error_delimiters", "error", "run", "set_value", "set_radio", "set_select", "set_checkbox", "prep_for_form", "prep_url", "strip_image_tags", "xss_clean", "encode_php_tags");
        $return = array();
        foreach ($file as $line) {
            if (strpos($line, "public function")) {
                $function_name = substr($line, strpos($line, "public function") + 16, (strpos($line, "(") - (strpos($line, "public function") + 16)));
                if (!in_array($function_name, $excluded_methods)) {
                    $exists = false;
                    foreach ($return as $key => $value) {
                        if ($value["name"] == $function_name) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $arr_temp = array("name" => $function_name);
                        $params = explode(",", substr($line, strpos($line, "(") + 1, (strpos($line, ")") - strpos($line, "(") - 1)));
                        if (count($params) == 2) {
                            $arr_temp["param"] = substr(trim($params[1]), 1, strlen($params[1]));
                        }
                        $return[] = $arr_temp;
                    }
                }
            }
        }
        //colocando os validates required e is_not_null nas primeiras posições
        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i]["name"] == "required" && $i != 0) {
                $temp = $return[0];
                $return[0] = $return[$i];
                $return[$i] = $temp;
                $i--;
            }
            if ($return[$i]["name"] == "is_not_null" && $i != 1) {
                $temp = $return[1];
                $return[1] = $return[$i];
                $return[$i] = $temp;
                $i--;
            }
        }
        return $return;
    }

}
