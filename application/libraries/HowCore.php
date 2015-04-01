<?php

class HowCore {

    /**
     * Variável que armazema os caches de funções que utilizam esse recurso
     * @var array
     */
    static $cache_functions = array();

    /**
     * Variável que armazena os caches de objetos retornados do banco por algum método
     * @var array
     */
    static $cache_objects = array();

    /**
     * Controla a ativação ou desativação dos caches
     * @var boolean
     */
    static $use_cache = true;

    /**
     * Desativa o cache da aplicação
     */
    static function disableCache() {
        self::$use_cache = false;
    }

    /**
     * Ativa o cache da aplicação
     */
    static function enableCache() {
        self::$use_cache = true;
    }

    /**
     * Obtém o valor cacheado de uma chamada de função
     * @param string $function_name
     * @param array $args
     * @return retorno salvo em cache
     */
    static function getCachedFunction($function_name, $args) {
        if (!self::$use_cache) {
            return NullValue::instance();
        }
        if (array_key_exists($function_name, self::$cache_functions)) {
            $function = self::$cache_functions[$function_name];

            foreach ($function as $return) {
                if ($return["parameter"] == $args) {
                    return $return["return"];
                }
            }
        }
        return NullValue::instance();
    }

    /**
     * Salva o retorno de uma função no cache
     * @param string $function_name
     * @param array $args
     * @param whatever $value
     * @return null
     */
    static function setCachedFunction($function_name, $args, $value) {
        if (!self::$use_cache) {
            return;
        }

        if (!array_key_exists($function_name, self::$cache_functions)) {
            self::$cache_functions[$function_name] = array();
        }
        $function = self::$cache_functions[$function_name];

        foreach ($function as $return) {
            if ($return["parameter"] == $args) {
                return;
            }
        }

        self::$cache_functions[$function_name][] = array("parameter" => $args, "return" => $value);
    }

    /**
     * Obtém do cache o objeto da $classe informada com o $id informado, caso já exista em cache
     * @param classname $class
     * @param integer $id
     * @return MY_Model object
     */
    static function getCachedObject($class, $id) {
        if (!self::$use_cache) {
            return NullValue::instance();
        }
        if (array_key_exists($class, self::$cache_objects)) {
            $cached_objects = self::$cache_objects[$class];
            foreach ($cached_objects as $obj) {
                if ($obj["id"] == $id) {
                    return $obj["object"];
                }
            }
        }
        return NullValue::instance();
    }

    /**
     * Salva um objeto obtido do banco no cache
     * @param MY_Model object $object
     * @return null
     */
    static function setCachedObject($object) {
        if (!self::$use_cache) {
            return;
        }
        if ($object) {
            $class = get_class($object);
            $id = $object->getId();
            if ($class && $id > 0) {
                if (!array_key_exists($class, self::$cache_objects)) {
                    self::$cache_objects[$class] = array();
                }
                $cached_objects = self::$cache_objects[$class];
                foreach ($cached_objects as $obj) {
                    if ($obj["id"] == $id) {
                        self::unsetCachedObject($obj["object"]);
                    }
                }
                self::$cache_objects[$class][] = array("id" => $id, "object" => $object);
            }
        }
    }
    
    /**
     * Remove um objeto do cache caso ele seja deletado
     * @param MY_Model object $object
     */
    static function unsetCachedObject($object){
        if ($object){
            $class = get_class($object);
            $id = $object->getId();
            if ($class && $id > 0){
                $cached_objects = self::$cache_objects[$class];
                $i = 0;
                foreach ($cached_objects as $obj){
                    if ($obj["id"] == $id){
                        unset(self::$cache_objects[$class][$i]);
                    }
                    $i++;
                }
            }
        }
    }

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

class NullValue {

    private static $instance = null;

    static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
