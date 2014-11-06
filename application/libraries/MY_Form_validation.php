<?php

class MY_Form_validation extends CI_Form_validation {

    public function __construct() {
        parent::__construct();
        $this->_error_prefix = '<div class="alert alert-danger alert-dismissible" role="alert">';
        $this->_error_suffix = '</div>';
    }

    public function error_string($prefix = '', $suffix = '') {
        // No errrors, validation passes!
        if (count($this->_error_array) === 0) {
            return '';
        }

        if ($prefix == '') {
            $prefix = $this->_error_prefix;
        }

        if ($suffix == '') {
            $suffix = $this->_error_suffix;
        }

        // Generate the error string
        $str = '';
        foreach ($this->_error_array as $val) {
            if ($val != '') {
                $str .= $val . "<br>";
            }
        }

        return $prefix . $str . $suffix;
    }

    public function get_error_array() {
        return $this->_error_array;
    }

    /**
     * Executes the Validation routines
     * ** Alterada para reconhecer os validations feitos no próprio modelo **
     * @access	private
     * @param	array
     * @param	array
     * @param	mixed
     * @param	integer
     * @return	mixed
     */
    protected function _execute($row, $rules, $postdata = NULL, $cycles = 0) {
        // If the $_POST data is an array we will run a recursive call
        if (is_array($postdata)) {
            foreach ($postdata as $key => $val) {
                $this->_execute($row, $rules, $val, $cycles);
                $cycles++;
            }

            return;
        }

        // --------------------------------------------------------------------
        // If the field is blank, but NOT required, no further tests are necessary
        $callback = FALSE;
        if (!in_array('required', $rules) AND is_null($postdata)) {
            // Before we bail out, does the rule contain a callback?
            if (preg_match("/(callback_\w+(\[.*?\])?)/", implode(' ', $rules), $match)) {
                $callback = TRUE;
                $rules = (array('1' => $match[1]));
            } else {
                return;
            }
        }

        // --------------------------------------------------------------------
        // Isset Test. Typically this rule will only apply to checkboxes.
        if (is_null($postdata) AND $callback == FALSE) {
            if (in_array('isset', $rules, TRUE) OR in_array('required', $rules)) {
                // Set the message type
                $type = (in_array('required', $rules)) ? 'required' : 'isset';

                if (!isset($this->_error_messages[$type])) {
                    if (FALSE === ($line = $this->CI->lang->line($type))) {
                        $line = 'The field was not set';
                    }
                } else {
                    $line = $this->_error_messages[$type];
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']));

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }
            }

            return;
        }

        // --------------------------------------------------------------------
        // Cycle through each rule and run it
        foreach ($rules As $rule) {
            $_in_array = FALSE;

            // We set the $postdata variable with the current data in our master array so that
            // each cycle of the loop is dealing with the processed data from the last cycle
            if ($row['is_array'] == TRUE AND is_array($this->_field_data[$row['field']]['postdata'])) {
                // We shouldn't need this safety, but just in case there isn't an array index
                // associated with this cycle we'll bail out
                if (!isset($this->_field_data[$row['field']]['postdata'][$cycles])) {
                    continue;
                }

                $postdata = $this->_field_data[$row['field']]['postdata'][$cycles];
                $_in_array = TRUE;
            } else {
                $postdata = $this->_field_data[$row['field']]['postdata'];
            }

            // --------------------------------------------------------------------
            // Is the rule a callback?
            $callback = FALSE;
            if (substr($rule, 0, 9) == 'callback_') {
                $rule = substr($rule, 9);
                $callback = TRUE;
            }

            // Strip the parameter (if exists) from the rule
            // Rules can contain a parameter: max_length[5]
            $param = FALSE;
            if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                $rule = $match[1];
                $param = $match[2];
            }

            // Call the function that corresponds to the rule
            if ($callback === TRUE) {
                if (!method_exists($this->CI, $rule) && isset($_POST["_current_class"]) && !method_exists($_POST["_current_class"], $rule)) {
                    continue;
                }

                if (isset($_POST["_current_class"]) && method_exists($_POST["_current_class"], $rule)) {
                    eval('$instance = new ' . $_POST["_current_class"] . '();');
                    $result = $instance->$rule($postdata, $param);
                } else {
                    // Run the function and grab the result
                    $result = $this->CI->$rule($postdata, $param);
                }

                // Re-assign the result to the master data array
                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }

                // If the field isn't required and we just processed a callback we'll move on...
                if (!in_array('required', $rules, TRUE) AND $result !== FALSE) {
                    continue;
                }
            } else {
                if (!method_exists($this, $rule)) {
                    // If our own wrapper function doesn't exist we see if a native PHP function does.
                    // Users can use any native PHP function call that has one param.
                    if (function_exists($rule)) {
                        $result = $rule($postdata);

                        if ($_in_array == TRUE) {
                            $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                        } else {
                            $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                        }
                    } else {
                        log_message('debug', "Unable to find validation rule: " . $rule);
                    }

                    continue;
                }

                $result = $this->$rule($postdata, $param);

                if ($_in_array == TRUE) {
                    $this->_field_data[$row['field']]['postdata'][$cycles] = (is_bool($result)) ? $postdata : $result;
                } else {
                    $this->_field_data[$row['field']]['postdata'] = (is_bool($result)) ? $postdata : $result;
                }
            }

            // Did the rule test negatively?  If so, grab the error.
            if ($result === FALSE) {
                if (!isset($this->_error_messages[$rule])) {
                    if (FALSE === ($line = $this->CI->lang->line($rule))) {
                        $line = 'Unable to access an error message corresponding to your field name.';
                    }
                } else {
                    $line = $this->_error_messages[$rule];
                }

                // Is the parameter we are inserting into the error message the name
                // of another field?  If so we need to grab its "field label"
                if (isset($this->_field_data[$param]) AND isset($this->_field_data[$param]['label'])) {
                    $param = $this->_translate_fieldname($this->_field_data[$param]['label']);
                }

                // Build the error message
                $message = sprintf($line, $this->_translate_fieldname($row['label']), $param);

                // Save the error message
                $this->_field_data[$row['field']]['error'] = $message;

                if (!isset($this->_error_array[$row['field']])) {
                    $this->_error_array[$row['field']] = $message;
                }

                return;
            }
        }
    }

    /**
     * Adiciona a validação que verifica se o campo é nulo usando a function isNull() como genérica para os validates
     * @param string $str
     * @return boolean
     */
    public function is_not_null($str) {
        return !isNull($str);
    }

    /**
     * Adiciona a validação data_valida como genérica para os validates
     * @param string $str
     * @return boolean
     */
    public function data_valida($str) {
        return data_valida($str, "bd");
    }

    /**
     * Adiciona a validação para ver se a data é maior que hoje como genérica para os validates
     * @param string $str
     * @return boolean
     */
    public function not_data_futura($str) {
        return !data1_maior_que_data2($str, hoje());
    }

    /**
     * Adiciona a validação para ver se a data não anterior a x dias como genérica para os validates
     * @param string $str
     * @param int $dias_pra_tras
     * @return boolean
     */
    public function minimo_dias_anteriores($str, $dias_pra_tras) {
        if (data1_maior_que_data2(operacao_data(hoje(), "-" . $dias_pra_tras . " days"), $str)) {
            return false;
        }
        return true;
    }

    /**
     * Valida se o valor do campo está compreendido no intervalo passado.
     * O intervalo é passado no formato 1..6 (minimo..maximo)
     * @param string $valor
     * @param string $intervalo
     * @return boolean
     */
    public function valor_entre($valor, $intervalo) {
        list($minimo, $maximo) = explode("..", $intervalo);
        return $valor >= $minimo && $valor <= $maximo;
    }

    /**
     * Valida se a uma hora é menor que outra
     * @param string $hora1
     * @param string $campo_hora2
     * @return boolean
     */
    public function hora1_maior_hora2($hora1, $campo_hora2) {
        return !data1_maior_que_data2($hora1, $_POST[$campo_hora2]);
    }
    
    /**
     * Valida se uma data é menor que outra
     * @param string $data1
     * @param string $campo_data2
     * @return boolean
     */
    public function data1_maior_data2($data1, $campo_data2){
        return !data1_maior_que_data2($data1, $_POST[$campo_data2]);
    }

}

?>