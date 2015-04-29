<?php

class MY_Model extends CI_Model {

    /**
     * Tabela correspondente no banco de dados. Deve ser declarada em cada subclasse.
     * @var string
     */
    protected $_table = null;

    /**
     * Instância do objeto de conexão com o banco de dados. Não é necessário se preocupar com isso.
     * @var CI_Database
     */
    public $_database;

    /**
     * Nome do campo que é chave primária na tabela. Redeclare na subclasse se precisar.
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * Arrays que definem relacionamento.
     */

    /**
     * public $belongs_to = array("usuario") criará um método usuario() em cada objeto, assumindo que exista
     *      uma classe Usuario e um campo usuario_id para referenciar.
     * public $belongs_to = array("usuario_aprovador" => array("class" => "Usuario", "field" => "usuario_aprovacao_id"))
     *      criará um método usuario() em cada objeto, assumindo a classe passada e o campo usuario_aprovacao_id como referência.
     * Se omitido o parâmetro class será assumido o nome do relacionamento em camel case. ex.: dep_pai viraria DepPai.
     * Se omitido o parâmetro field será assumido o nome do relacionamento acrescido do sufixo _id.
     * @var array
     */
    public $belongs_to = array();

    /**
     * public $has_many = array("usuarios") criará um método usuarios($order = null) em cada objeto, assumindo
     *      que exista uma classe Usuario e um campo de relacionamento no padrão nome_da_classe_id, ordenando pelo $order,
     *      quando informado.
     * public $has_many = array("usuarios_logados" => array("class" => "Usuario", "field" => "departamento_id", "order" => "nome") 
     *      criará um método usuarios_logados($order = null) em cada objeto, indicando o que busca através da class, field e ordenando,
     *      opcionalmente, pelo campo desejado.
     * @var array
     */
    public $has_many = array();

    /**
     * Regras de validação idênticas às definidas em um form_validation
     * @var array 
     */
    static $validates = array();

    /**
     * Guarda os erros de validação do objeto após a função isValid ser executada
     * @var array
     */
    public $validation_errors = array();
    protected $_private_attributes = array();

    /**
     * Inicializa o modelo, tendo possibilidade de ser passado um array chave => valor ou
     * uma stdClass com atributos iguais aos da classe.
     */
    public function __construct($attributes = array()) {
        parent::__construct();
        $this->_database = $this->db;

        $table_columns = $this->columns($this);
        foreach ($table_columns as $column) {
            $this->_private_attributes[$column] = null;
        }

        $this->validation_errors = array();

        /**
         * Trecho que prepara o comportamento acts_as_list caso ele esteja setado
         */
        if (property_exists($this, "acts_as_list")) {
            if (gettype($this->acts_as_list) != "array") {
                $this->acts_as_list = array();
            }
            if (!array_key_exists("field", $this->acts_as_list)) {
                $this->acts_as_list["field"] = "ordem";
            }
            if (!array_key_exists("scope", $this->acts_as_list)) {
                $this->acts_as_list["scope"] = array();
            } elseif (gettype($this->acts_as_list["scope"]) != "array") {
                $this->acts_as_list["scope"] = array($this->acts_as_list["scope"]);
            }
        }

        /**
         * Trecho que prepara o comportamento acts_as_tree caso ele esteja setado
         */
        if (property_exists($this, "acts_as_tree")) {
            if (gettype($this->acts_as_tree) != "array") {
                $this->acts_as_tree = array();
            }
            if (!array_key_exists("field", $this->acts_as_tree)) {
                $this->acts_as_tree["field"] = "parent_id";
            }
            if (!array_key_exists("order", $this->acts_as_tree)) {
                $this->acts_as_tree["order"] = "id";
            }
            if (!array_key_exists("destroy_dependants", $this->acts_as_tree)) {
                $this->acts_as_tree["destroy_dependants"] = false;
            }
        }

        /**
         * Trecho que converte o array ou a stdClass passada para os atributos da própria classe
         */
        if (gettype($attributes) == "object" && get_class($attributes) == "stdClass") {
            $object = $attributes;
            $attributes = (array) $attributes;
        }
        if (gettype($attributes) == "array") {
            foreach ($attributes as $attr => $value) {
                eval('$is_null = isset($object) ? $object->' . $attr . ' === null : false;');
                if (!$is_null) {
                    if (property_exists(get_class($this), $attr)) {
                        if (method_exists($this, "set" . underscore_to_camel_case($attr, true))) {
                            eval('$this->set' . underscore_to_camel_case($attr, true) . '("' . str_replace('"', '\"', $value) . '");');
                        }
                    }
                }
                if (array_key_exists($attr, $this->_private_attributes)) {
                    $this->_private_attributes[$attr] = $value;
                }
            }
        }

        /**
         * Gera métodos de relacionamento para todos os campos da tabela que terminem com _id. ex.: usuario_id reflete em um método usuario()
         */
        $class_attributes = $this->classAttributes();
        $columns = $table_columns;
        foreach ($columns as $column) {
            if (substr($column, strlen($column) - 3, 3) == "_id") {
                $field = $column;
                $name = substr($field, 0, strlen($column) - 3);
                $class = underscore_to_camel_case($name, true);
                if (class_exists($class)) {
                    if (!property_exists($class, $name)) {
                        if (!in_array($name, $class_attributes)) {
                            eval('$id = $this->get' . underscore_to_camel_case($field, true) . '();');
                            eval('$this->' . $name . ' = create_function(\'\', \'return ' . $class . '::find(' . $id . ');\');');
                        }
                    }
                }
            }
        }

        /**
         * Gera métodos de relacionamento a partir do atributo público $belongs_to declarado na classe
         */
        foreach ($this->belongs_to as $nome => $relacionamento) {
            if (is_numeric($nome)) {
                $nome = $relacionamento;
                $relacionamento = array();
            }
            if (!isset($relacionamento["class"])) {
                $relacionamento["class"] = underscore_to_camel_case($nome, true);
            }
            if (!isset($relacionamento["field"])) {
                $relacionamento["field"] = $nome . "_id";
            }
            eval('$id = $this->get' . underscore_to_camel_case($relacionamento["field"], true) . '();');
            $id = isNull($id) ? 0 : $id;
            eval('$this->' . $nome . ' = create_function(\'\', \'return ' . $relacionamento["class"] . '::find(' . $id . ');\');');
        }

        /**
         * Gera métodos de relacionamento has_many, que retorna arrays com objetos do relacionamento
         */
        foreach ($this->has_many as $nome => $relacionamento) {
            if (is_numeric($nome)) {
                $nome = $relacionamento;
                $relacionamento = array();
            }
            if (!isset($relacionamento["class"])) {
                $relacionamento["class"] = underscore_to_camel_case($nome, true);
                $relacionamento["class"] = substr($relacionamento["class"], 0, strlen($relacionamento["class"]) - 1);
            }
            if (!isset($relacionamento["field"])) {
                $relacionamento["field"] = camel_case_to_underscore(get_class($this)) . "_id";
            }
            if (!isset($relacionamento["order"])) {
                $relacionamento["order"] = $this->_default_order();
            }
            eval('$id = $this->get' . underscore_to_camel_case($this->primary_key, true) . '();');
            $id = isNull($id) ? 0 : $id;
            if (!isset($relacionamento["through"])) {
                eval('$this->' . $nome . ' = create_function(\'$order = null, $default_order = "' . $relacionamento["order"] . '"\', \'$order = isNull($order) ? $default_order : $order; return ' . $relacionamento["class"] . '::collection(array("' . $relacionamento["field"] . '" => "' . $id . '"), $order);\');');
            } else {
                $foreign_key = isset($relacionamento["foreign_key"]) ? $relacionamento["foreign_key"] : camel_case_to_underscore($relacionamento["class"]) . "_id";
                eval('$relationship_table = ' . $relacionamento["through"] . '::getInstance()->_tablename();');
                eval('$this->' . $nome . ' = create_function(\'$order = null, $default_order = "' . $relacionamento["order"] . '"\', \' $order = isNull($order) ? $default_order : $order; return ' . $relacionamento["class"] . '::collection("id IN (SELECT ' . $foreign_key . ' FROM ' . $relationship_table . ' WHERE ' . $relacionamento["field"] . ' = ' . $id . ')", $order); \');');
            }
        }
    }

    /**
     * Insere de fato um registro no banco.
     * ESTE MÉTODO NUNCA DEVE SER CHAMADO DIRETAMENTE. Quem faz a chamada é o método save().
     */
    private function insert($data, $soft = false) {
        if (gettype($data) == "array") {
            foreach ($data as $key => $value) {
                if (isNull($value)) {
                    unset($data[$key]);
                }
            }
        }

        if ($data !== FALSE) {
            if (!$soft && isset($this->acts_as_list)) {
                $ultimo = $this->lastRecordOnList();
                $field = $this->acts_as_list["field"];
                if ($ultimo) {
                    $data[$field] = $ultimo->get($field) + 1;
                } else {
                    $data[$field] = 1;
                }
            }

            $this->_database->insert($this->_tablename(), $data);
            $insert_id = $this->_database->insert_id();
            $this->setId($insert_id);

            if (!$soft && isset($this->acts_as_tree)) {
                self::rebuildTree();
            }

            return self::find($insert_id);
        } else {
            return FALSE;
        }
    }

    /**
     * Processa o update.
     * ESTE MÉTODO NUNCA DEVE SER CHAMADO DIRETAMENTE. Quem faz a chamada é o método save().
     */
    private function update($primary_value, $data, $soft = false) {
        if (gettype($data) == "array") {
            foreach ($data as $key => $value) {
                if (isNull($value)) {
                    $data[$key] = null;
                }
            }
        }

        if ($data !== FALSE) {
            if (!$soft && isset($this->acts_as_list)) {
                $field = $this->acts_as_list["field"];
                $old_object = self::find($primary_value);
                $scope = $this->acts_as_list["scope"];
                if (gettype($scope) == "array" && !vazio($scope)) {
                    if ($old_object->whereClauseFromScope() != $this->whereClauseFromScope()) {
                        foreach ($old_object->nextRecordsOnList() as $record) {
                            $record->set($field, $record->get($field) - 1);
                            $record->save();
                        }
                        $data[$field] = $this->lastPositionOnList() + 1;
                    } elseif (!isset($data[$field])) {
                        $data[$field] = $old_object->get($field);
                    }
                } elseif (!isset($data[$field])) {
                    $data[$field] = $old_object->get($field);
                }
            }

            $result = $this->_database->where($this->primary_key, $primary_value)->set($data)->update($this->_tablename());

            if (!$soft && isset($this->acts_as_tree)) {
                self::rebuildTree();
            }

            return $result;
        } else {
            return FALSE;
        }
    }

    /**
     * Retorna a classe que originou a chamada. Útil para os métodos saberem que devem utilizar o nome da classe
     * específica e não o MY_Model.
     * @param boolean $bt
     * @param integer $l
     * @return type
     */
    static function get_called_class($bt = false, $l = 1) {
        if (!$bt)
            $bt = debug_backtrace();
        if (!isset($bt[$l]))
            throw new Exception("Cannot find called class -> stack level too deep.");
        if (!isset($bt[$l]['type']))
            throw new Exception('type not set');
        else
            switch ($bt[$l]['type']) {
                case '::':
                    $lines = file($bt[$l]['file']);
                    $i = 0;
                    $callerLine = '';
                    do {
                        $i++;
                        $callerLine = $lines[$bt[$l]['line'] - $i] . $callerLine;
                    } while (stripos($callerLine, $bt[$l]['function']) === false);
                    preg_match('/([a-zA-Z0-9\_]+)::' . $bt[$l]['function'] . '/', $callerLine, $matches);
                    if (!isset($matches[1]))
                        throw new Exception("Could not find caller class: originating method call is obscured.");
                    switch ($matches[1]) {
                        case 'self':
                        case 'parent':
                            return self::get_called_class($bt, $l + 1);
                        default:
                            return $matches[1];
                    }
                case '->': switch ($bt[$l]['function']) {
                        case '__get':
                            if (!is_object($bt[$l]['object']))
                                throw new Exception("Edge case fail. __get called on non object.");
                            return get_class($bt[$l]['object']);
                        default: return $bt[$l]['class'];
                    }
                default: throw new Exception("Unknown backtrace method type");
            }
    }

    /**
     * Retorna um array com todos os atributos públicos e protegidos de um objeto.
     * @return array
     */
    public function classAttributes() {
        $vars = $this;
        $instance_attributes = (array) ($vars);
        $attrs = array();
        $classname = get_class($this);
        foreach ($instance_attributes as $name => $value) {
            $name = trim($name);
            if (trim(substr($name, 0, strlen($classname) + 1)) == $classname) {
                $attrs[] = trim(substr($name, strlen($classname) + 1, strlen($name)));
            } elseif ($name[0] == "*") {
                $attrs[] = trim(substr($name, 1, strlen($name)));
            }
        }
        return $attrs;
    }

    /**
     * Converte o objeto atual para um array de chave => valor
     * @return array
     */
    public function toArray() {
        $attrs = $this->_private_attributes;
        $result = array();
        foreach ($attrs as $key => $attr) {
            if (method_exists($this, "get" . underscore_to_camel_case($key, true))) {
                eval('$result["' . $key . '"] = $this->get' . underscore_to_camel_case($key, true) . '();');
            } else {
                $result[$key] = $attr;
            }
        }
        return $result;
    }

    /**
     * Retorna o primeiro registro que bater onde $campo = $valor
     * @param string $campo
     * @param string $valor
     * @return current_class
     */
    static function findBy($campo, $valor) {
        $CI = get_instance();
        $CI->db->where($campo, $valor);
        $classname = get_called_class();
        eval('$object = new ' . $classname . '();');
        $result = $CI->db->get($object->_tablename())->result();
        if (count($result) > 0) {
            eval('$new = new ' . $classname . '($result[0]);');
            HowCore::setCachedObject($new);
            return $new;
        }
        return null;
    }

    /**
     * Encontra o registro na tabela da classe chamada que tenha id = ao $id passado
     * @param integer $id
     * @return current_class
     */
    static function find($id) {
        $cache = HowCore::getCachedObject(get_called_class(), $id);
        if (!($cache instanceof NullValue)) {
            return $cache;
        }

        return self::findBy("id", $id);
    }

    /**
     * Retorna todos os registros aonde $campo = $valor
     * @param string $campo
     * @param string $valor
     * @return array
     */
    static function findAllBy($campo, $valor, $order = "_default_order") {
        $CI = get_instance();
        $CI->db->where($campo, $valor);
        $CI->db->order_by($order == "_default_order" ? $this->_default_order() : $order);
        $classname = get_called_class();
        eval('$object = new ' . $classname . '();');
        $result = $CI->db->get($object->_tablename())->result();
        $array = array();
        foreach ($result as $value) {
            eval('$array[] = new ' . $classname . '($value);');
            HowCore::setCachedObject($array[count($array) - 1]);
        }
        return $array;
    }

    /**
     * Retorna um array com todos os registros da classe ordenados pela $order passada
     * @param string $order
     * @return array
     */
    static function getAll($order = "_default_order") {
        $CI = get_instance();
        $classname = get_called_class();
        eval('$object = new ' . $classname . '();');
        $CI->db->order_by($order == "_default_order" ? $object->_default_order() : $order);
        $result = $CI->db->get($object->_tablename())->result();
        $array = array();
        foreach ($result as $value) {
            eval('$array[] = new ' . $classname . '($value);');
            HowCore::setCachedObject($array[count($array) - 1]);
        }
        return $array;
    }

    /**
     * Alias para a função getAll()
     * @param string $order
     * @return array
     */
    static function all($order = "_default_order") {
        return self::getAll($order);
    }

    /**
     * Retorna um array de objetos da classe chamada atendendo as condições passadas, que podem ser por array ou diretamente como string (cláusula where).
     * ex.: Classe::collection(array("nome" => "PhP"))
     * ex.: Classe::collection(array("nome" => "PhP", "versao" => "5.3"), "nome")
     * ex.: Classe::collection("nome = 'PhP' AND versao <> '4.0'")
     * @param array|string $condicoes
     * @param string $order
     * @return array
     */
    static function collection($condicoes = array(), $order = "_default_order") {
        $CI = get_instance();
        $classname = get_called_class();
        eval('$object = new ' . $classname . '();');
        $sql = "SELECT * FROM " . $object->_tablename();
        if (gettype($condicoes) == "array") {
            if (sizeof($condicoes) > 0 || $condicoes != "") {
                foreach ($condicoes as $condicao => $valor) {
                    if (is_null($valor)) {
                        $where[] = $condicao . " IS NULL";
                    } else {
                        $where[] = $condicao . " = '" . $valor . "'";
                    }
                }
                if (sizeof($where) > 0) {
                    $sql .= " WHERE " . implode(" AND ", $where);
                }
            }
        } elseif (!empty($condicoes)) {
            $sql .= " WHERE " . $condicoes;
        }
        $sql .= " ORDER BY " . ($order == "_default_order" ? $object->_default_order() : $order);
        $result = $CI->db->query($sql)->result();
        $array = array();
        foreach ($result as $value) {
            eval('$array[] = new ' . $classname . '($value);');
            HowCore::setCachedObject($array[count($array) - 1]);
        }
        return $array;
    }

    /**
     * Retorna o count a partir das condições passadas, que podem ser por array ou diretamente como string (cláusula where).
     * ex.: Classe::collection(array("nome" => "PhP"))
     * ex.: Classe::collection(array("nome" => "PhP", "versao" => "5.3"), "nome")
     * ex.: Classe::collection("nome = 'PhP' AND versao <> '4.0'")
     * @param array|string $condicoes
     * @return int
     */
    static function count($condicoes = array()) {
        $CI = get_instance();
        $classname = get_called_class();
        eval('$object = new ' . $classname . '();');
        $sql = "SELECT COUNT(1) AS 'quantidade'
                  FROM " . $object->_tablename();
        if (gettype($condicoes) == "array") {
            if (sizeof($condicoes) > 0 || $condicoes != "") {
                foreach ($condicoes as $condicao => $valor) {
                    if (is_null($valor)) {
                        $where[] = $condicao . " IS NULL";
                    } else {
                        $where[] = $condicao . " = '" . $valor . "'";
                    }
                }
                if (sizeof($where) > 0) {
                    $sql .= " WHERE " . implode(" AND ", $where);
                }
            }
        } elseif (!empty($condicoes)) {
            $sql .= " WHERE " . $condicoes;
        }
        $result = $CI->db->query($sql)->result();
        return $result[0]->quantidade;
    }

    /**
     * Remove o objeto atual no banco de dados, verificando primeiro se ele está apto a ser removido
     * @return boolean
     */
    public function delete() {
        if ($this->isRemovable()) {
            if (!$this->getId()) {
                return false;
            }

            if (isset($this->acts_as_tree) && $this->acts_as_tree["destroy_dependants"]) {
                $children = $this->childrenOnTree();
                $children = array_reverse($children);
                foreach ($children as $child) {
                    $child->delete();
                }
            }

            foreach ($this->has_many as $relation_name => $relationship) {
                if (isset($relationship["destroy_dependants"]) && $relationship["destroy_dependants"]) {
                    eval('$objects = $this->' . $relation_name . '();');
                    foreach ($objects as $object) {
                        $object->delete();
                    }
                }
            }

            if (get_instance()->db->delete($this->_tablename(), array("id" => $this->getId()))) {
                HowCore::unsetCachedObject($this);
                if (isset($this->acts_as_list)) {
                    $field = $this->acts_as_list["field"];
                    $where = $this->whereClauseFromScope();
                    $posteriores = self::collection($where . (vazio($where) ? "" : " AND ") . $field . " > '" . $this->get($field) . "'");
                    foreach ($posteriores as $posterior) {
                        $posterior->set($field, $posterior->get($field) - 1);
                        $posterior->save();
                    }
                }

                if (isset($this->acts_as_tree)) {
                    self::rebuildTree();
                }

                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Executa o form_validation considerando a regras definidas na variável estática $validates.
     * Caso seja passado o parâmetro $skip_fields, os campos passados por array não serão validados.
     * @return boolean
     */
    public function isValid($skip_fields = array()) {
        $this->load->library("form_validation");
        $this->lang->load("form_validation");
        $this->validation_errors = array();
        $this->form_validation->reset_error_array();
        $classname = get_called_class();
        eval('$validates = ' . $classname . '::$validates;');
        $is_valid_as_tree = $this->isValidAsTree();
        if (count($validates) > 0 || !$is_valid_as_tree) {
            $old_post = $_POST;
            $array = $this->toArray();
            foreach ($array as $key => $item) {
                $_POST[$key] = $item;
            }
            $_POST["_current_class"] = $classname;
            $_POST["_object"] = $this;
            foreach ($validates as $validate) {
                list($field, $field_label, $rules) = $validate;
                if (!in_array($field, $skip_fields)) {
                    $this->form_validation->set_rules($field, $field_label, $rules);
                }
            }
            if ($this->form_validation->run() && $is_valid_as_tree) {
                $_POST = $old_post;
                return true;
            } else {
                if (!$is_valid_as_tree) {
                    $field = self::getInstance()->acts_as_tree["field"];
                    $this->form_validation->append_error_array($field, $this->lang->line("is_not_valid_as_tree"));
                }
                $this->validation_errors = array_merge($this->validation_errors, $this->form_validation->get_error_array());
                $_POST = $old_post;
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Retorna os erros de objeto em formato array("campo" => "Erro para o campo")
     * @return array
     */
    public function getErrorsAsArray() {
        return $this->validation_errors;
    }

    /**
     * Retorna os erros do objeto em formato string colando-os com o $separador informado
     * @param string $separator
     * @return string
     */
    public function getErrorsAsString($separator = "") {
        $errors = $this->getErrorsAsArray();
        $list = array();
        foreach ($errors as $field => $error) {
            $list[] = $error;
        }
        return join($separator, $list);
    }

    /**
     * Salva os valores dos atributos do objeto no banco, criando ou editando conforme necessidade.
     * Caso seja passado true como parâmetro, as validações serão ignoradas
     * @param boolean $skip_validation
     * @param boolean $soft Caso true então não faz nada além do insert ou do update
     * @return boolean or object
     */
    public function save($skip_validation = false, $soft = false) {
        if ($skip_validation === true || (gettype($skip_validation) == "array" && $this->isValid($skip_validation)) || $this->isValid()) {
            if ($skip_validation === false && $this->isPersisted() && !$this->isEditable()) {
                return false;
            }
            if (!$soft && !$this->isValidAsTree()) {
                return false;
            }
            $values = $this->toArray();
            unset($values["id"]);
            if ($this->isPersisted()) {
                $return = $this->update($this->getId(), $values, $soft) ? true : false;
            } else {
                $return = $this->insert($values, $soft);
            }
            if ($return) {
                HowCore::setCachedObject($this);
            }
            return $return;
        } else {
            return false;
        }
    }

    /**
     * Retorna se o registro já está existe no banco, baseado no fato do id estar setado ou não
     * @return boolean
     */
    public function isPersisted() {
        return $this->getId() > 0;
    }

    /**
     * Cada classe poderá implementar este método para definir se o objeto pode ser removido do banco ou não (por dependência, por exemplo).
     * No método delete() será verificado se o objeto isRemovable()
     * @return boolean
     */
    public function isRemovable() {
        if (isset($this->acts_as_tree) && !$this->acts_as_tree["destroy_dependants"]) {
            return vazio($this->childrenOnTree());
        }
        return true;
    }

    /**
     * Cada classe poderá implementar este método para definir se o objeto pode ser editado ou não.
     * No método save() será verificado se o objeto isEditable()
     * @return boolean
     */
    public function isEditable() {
        return true;
    }

    /**
     * Método mágico que faz chamada de método através de um atributo (difícil até de explicar).
     * Isso intercepta as chamadas de método para fazer esquema de entender relacionamento.
     * PODE IGNORAR A EXISTÊNCIA DESSE MÉTODO.
     * @param string $method
     * @param args $args
     * @return call
     */
    public function __call($method, $args) {
        if (property_exists($this, $method)) {
            if (is_callable($this->$method)) {
                return call_user_func_array($this->$method, $args);
            }
        }
        if (!method_exists($this, $method)) {
            if (substr($method, 0, 3) == "get") {
                $field = camel_case_to_underscore(substr($method, 3, strlen($method)));
                if (isset($this->_private_attributes[$field])) {
                    eval('$value = $this->_private_attributes["' . $field . '"];');
                    return call_user_func_array(create_function('$value', 'return $value;'), array($value));
                }
            } elseif (substr($method, 0, 3) == "set") {
                $field = camel_case_to_underscore(substr($method, 3, strlen($method)));
                if (array_key_exists($field, $this->_private_attributes)) {
                    $this->_private_attributes[$field] = $args[0];
                    return;
                }
            }
        }
    }

    /**
     * Retorna uma instância da classe atual para ser usado em métodos estáticos
     * @return class_instance
     */
    static function getInstance() {
        eval("\$instance = new " . (get_called_class()) . "();");
        if (get_class($instance) == "MY_Model") {
            eval("\$instance = new " . (self::get_called_class()) . "();");
        }
        return $instance;
    }

    /**
     * Retorna a lista de campos da tabela da classe
     * @return array
     */
    static function columns($instancia = null) {
        if ($instancia) {
            return fields_of($instancia->_tablename());
        } else {
            return fields_of(self::getInstance()->_tablename());
        }
    }

    /**
     * Retorna o primeiro registro da tabela ordenado por id
     * @return Current Class
     */
    static function first() {
        list($record) = self::collection(null, "id LIMIT 1");
        return isset($record) ? $record : null;
    }

    /**
     * Retorna o último registro da tabela ordenado por id
     * @return Current Class
     */
    static function last() {
        list($record) = self::collection(null, "id DESC LIMIT 1");
        return isset($record) ? $record : null;
    }

    /**
     * Retorna o valor de um atributo privado do objeto
     * @param string $attr
     * @return type
     */
    public function get($attr) {
        return array_key_exists($attr, $this->_private_attributes) ? $this->_private_attributes[$attr] : null;
    }

    /**
     * Seta o valor de um atributo privado do objeto
     * @param string $attr
     * @param type $value
     * @return boolean
     */
    public function set($attr, $value) {
        return array_key_exists($attr, $this->_private_attributes) ? $this->_private_attributes[$attr] = $value : false;
    }

    /**
     * Retorna o nome da tabela do objeto
     * @return string
     */
    public function _tablename() {
        if (isset($this->_table) && !isNull($this->_table)) {
            return $this->_table;
        } else {
            return camel_case_to_underscore(get_class($this));
        }
    }

    /**
     * Retorna o campo padrão para ordenação da tabela
     * @return string
     */
    public function _default_order() {
        if (isset($this->acts_as_tree)) {
            return "lft";
        }
        if (isset($this->acts_as_list)) {
            return $this->acts_as_list["field"];
        }
        return "id";
    }

    /**
     * Preenche somente os atributos do objeto que forem passado no $array de chave e valor
     * @param array $array
     */
    function setAttributesFromArray($array) {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

###########################################
## MÉTODOS DO COMPORTAMENTO ACTS_AS_LIST ##
###########################################

    /**
     * Retorna o objeto anterior na lista
     * @return record or null
     */
    public function previousRecordOnList() {
        if (isset($this->acts_as_list)) {
            $field = $this->acts_as_list["field"];
            if (vazio($this->acts_as_list["scope"])) {
                return array_first(self::collection(array($field => $this->get($field) - 1)));
            } else {
                return array_first(self::collection($this->whereClauseFromScope() . " AND " . $field . " = '" . ($this->get($field) - 1) . "'"));
            }
        }
        return null;
    }

    /**
     * Retorna o próximo objeto na lista
     * @return record or null
     */
    public function nextRecordOnList() {
        if (isset($this->acts_as_list)) {
            $field = $this->acts_as_list["field"];
            if (vazio($this->acts_as_list["scope"])) {
                return array_first(self::collection(array($field => $this->get($field) + 1)));
            } else {
                return array_first(self::collection($this->whereClauseFromScope() . " AND " . $field . " = '" . ($this->get($field) + 1) . "'"));
            }
        }
        return null;
    }

    /**
     * Retorna a lista de objetos posteriores na lista
     * @return array
     */
    public function nextRecordsOnList() {
        if (isset($this->acts_as_list)) {
            $field = $this->acts_as_list["field"];
            if (vazio($this->acts_as_list["scope"])) {
                return self::collection($field . " > '" . $this->get($field) . "'");
            } else {
                return self::collection($field . " > '" . $this->get($field) . "' AND " . $this->whereClauseFromScope());
            }
        }
        return array();
    }

    /**
     * Move o objeto uma posição acima na lista
     * @return boolean
     */
    public function moveUpOnList() {
        if (isset($this->acts_as_list)) {
            $field = $this->acts_as_list["field"];
            if ($this->get($field) > 1) {
                $anterior = $this->previousRecordOnList();
                if ($anterior) {
                    $anterior->set($field, $this->get($field));
                    $this->set($field, $this->get($field) - 1);
                    return $this->save() && $anterior->save();
                }
            }
        }
        return false;
    }

    /**
     * Move um objeto uma posição abaixo na lista
     * @return boolean
     */
    public function moveDownOnList() {
        if (isset($this->acts_as_list)) {
            $field = $this->acts_as_list["field"];
            $proximo = $this->nextRecordOnList();
            if ($proximo) {
                $proximo->set($field, $this->get($field));
                $this->set($field, $this->get($field) + 1);
                return $this->save() && $proximo->save();
            }
        }
        return false;
    }

    /**
     * Retorna o último registro da lista de um objeto, considerando seu scope
     * @return object or false
     */
    public function lastRecordOnList() {
        if (isset($this->acts_as_list)) {
            $acts_as_list = $this->acts_as_list;
            $field = $acts_as_list["field"];
            if (vazio($acts_as_list["scope"])) {
                $ultimo = array_first(self::collection("", $field . " DESC LIMIT 1"));
            } else {
                $ultimo = array_first(self::collection($this->whereClauseFromScope(), $field . " DESC LIMIT 1"));
            }
            return $ultimo;
        } else {
            return false;
        }
    }

    /**
     * Retorna a posição do último elemento da lista de um objeto
     * @return int
     */
    public function lastPositionOnList() {
        if (isset($this->acts_as_list)) {
            $ultimo = $this->lastRecordOnList();
            return $ultimo ? $ultimo->get($this->acts_as_list["field"]) : 0;
        }
        return 0;
    }

    /**
     * Retorna se o objeto é o último elemento em sua lista
     * @return boolean
     */
    public function isLastOnList() {
        if (isset($this->acts_as_list)) {
            $ultimo = $this->lastRecordOnList();
            return $ultimo && $ultimo->getId() == $this->getId();
        }
        return false;
    }

    /**
     * Retorna se o objeto é o primeiro elemento em sua lista
     * @return boolean
     */
    public function isFirstOnList() {
        if (isset($this->acts_as_list)) {
            return $this->get($this->acts_as_list["field"]) == 1;
        }
        return false;
    }

    /**
     * Retorna o texto da cláusula where baseado no scope do acts_as_list
     * @return string
     */
    public function whereClauseFromScope() {
        if (isset($this->acts_as_list)) {
            $acts_as_list = $this->acts_as_list;
            if (!vazio($acts_as_list["scope"])) {
                $field = $acts_as_list["field"];
                $scope = gettype($acts_as_list["scope"]) == "array" ? $acts_as_list["scope"] : array($acts_as_list["scope"]);
                $where = array();
                foreach ($scope as $attr) {
                    if (!isNull($this->get($attr))) {
                        $where[] = $attr . " = '" . $this->get($attr) . "'";
                    } else {
                        $where[] = $attr . " IS NULL ";
                    }
                }
                $where = implode(" AND ", $where);
                return $where;
            }
        }
        return "";
    }

##################################
## FIM DOS MÉTODOS ACTS_AS_LIST ##
##################################
###########################################
## MÉTODOS DO COMPORTAMENTO ACTS_AS_TREE ##
###########################################

    /**
     * Retorna o item imediatamente acima na hierarquia
     * @return object or null
     */
    function parentOnTree() {
        if (isset($this->acts_as_tree)) {
            $field = $this->acts_as_tree["field"];
            return self::find($this->get($field));
        }
        return null;
    }

    /**
     * Retorna o level do item na hierarquia
     * @return integer or null
     */
    function levelOnTree() {
        if (isset($this->acts_as_tree)) {
            return $this->getLvl();
        }
        return null;
    }

    /**
     * Retorna os filhos imediados do item
     * @return array of object
     */
    function childrenOnTree($order = "_default_order") {
        if (isset($this->acts_as_tree)) {
            $order = $order == "_default_order" ? $this->_default_order() : $order;
            $field = $this->acts_as_tree["field"];
            return self::collection(array($field => $this->getId()), $order);
        }
        return null;
    }

    /**
     * Retorna a árvore de filhos do item incluindo ele mesmo
     * @return array of object
     */
    function childrenTree() {
        if (isset($this->acts_as_tree)) {
            return self::collection("lft >= " . $this->getLft() . " AND lft < " . $this->getRgt());
        }
        return null;
    }

    /**
     * Retorna a lista de pais do item incluindo ele mesmo
     * @return array of object
     */
    function pathToRoot($reverse = false) {
        if (isset($this->acts_as_tree)) {
            $ary = self::collection("lft <= " . $this->getLft() . " AND rgt >= " . $this->getRgt());
            if ($reverse) {
                return array_reverse($ary);
            } else {
                return $ary;
            }
        }
        return null;
    }

    /**
     * Retorna a quantidade de descendentes do item
     * @return integer or null
     */
    function numberOfDescendants() {
        if (isset($this->acts_as_tree)) {
            return (($this->getRgt() - $this->getLft() - 1) / 2);
        }
        return null;
    }

    /**
     * Remonta a tree completamente com base no parent da tabela
     * @param object $parent
     * @return boolean or null
     */
    static function rebuildTree($parent = null) {
        $instance = self::getInstance();
        if (isset($instance->acts_as_tree)) {
            $field = $instance->acts_as_tree["field"];
            $order = $instance->acts_as_tree["order"];
            if (!$parent) {
                $branches = self::collection(array($field => null), $order);
                $right = 1;
            } else {
                $branches = $parent->childrenOnTree($order);
                $right = $parent->getLft();
            }
            if (!vazio($branches)) {
                $previous_branch = null;
                foreach ($branches as $branch) {
                    if (!$previous_branch) {
                        if (!$parent) {
                            $branch->setLft(1);
                        } else {
                            $branch->setLft($parent->getLft() + 1);
                        }
                    } else {
                        $branch->setLft($previous_branch->getRgt() + 1);
                    }
                    $branch->setLvl($parent ? $parent->getLvl() + 1 : 1);
                    $right = self::rebuildTree($branch);
                    $branch->setRgt($right);
                    $branch->save(true, true);
                    $previous_branch = $branch;
                }
            }
            return $right + 1;
        }
        return null;
    }

    /**
     * Verifica se a tree é válida verificando se não irá gerar hierarquia recursiva
     * @return boolean
     */
    function isValidAsTree() {
        if (isset($this->acts_as_tree) && $this->isPersisted()) {
            $children = $this->childrenTree();
            $field = self::getInstance()->acts_as_tree["field"];
            return !in_array($this->get($field), map($children, "id"));
        }
        return true;
    }

    /**
     * Retorna o valor do campo do objeto precedido de identação
     * @param string $field
     * @param string $separator
     * @return string
     */
    function getIdentedField($field, $separator = "&nbsp; &nbsp; &nbsp; ") {
        $level = $this->getLvl();
        if ($level >= 0) {
            $pad = str_pad("", ($this->getLvl() - 1) * strlen($separator), $separator, STR_PAD_LEFT);
            return $pad . $this->get($field);
        }
        return $this->get($field);
    }

##################################
## FIM DOS MÉTODOS ACTS_AS_TREE ##
##################################
}

?>
