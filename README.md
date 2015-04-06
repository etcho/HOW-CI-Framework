## HOW CI Framework
###### [H]and [O]n [W]heel [C]ode[I]gniter Framework

*Testado no CodeIgniter 2.2 e 3.0*

HOW é uma extensão do CodeIgniter que engloba:
- Um model genérico capaz de fornecer uma orientação a objetos mais forte que o CI_Model.
- Funções extras de validação na library form_validation.
- Um conjunto de helpers com funções dos mais variados gêneros.
- Utiliza o <a href="http://www.grocerycrud.com/codeigniter-simplicity">CodeIgniter Simplicity</a> para o gerenciamento de templates.
- Utiliza o <a href="https://github.com/ericbarnes/codeigniter-simpletest">CodeIgniter SimpleTest</a> para testes unitários (testes dos helpers já vem incluídos).

### MY_Model
-------------------
Classe abstrata responsável pelo mapeamento objeto-relacional do banco de dados. É recomendável que cada tabela do banco possua uma classe relacionada que herde desta. Por adotar convenções, a definição das classes pode ser muito simples:
```php
class Usuario extends MY_Model {
}
```
O exemplo acima é para uma classe que mapeará a tabela 'usuario' no banco de dados, escrita da forma mais básica possível.
<br>
##### Métodos get e set e atributos privados
Os atributos privados são gerados automaticamente a partir do schema do banco. Logo cada coluna da tabela resultará em um atributo da classe.<br>
Apenas a título de conhecimento, todos os atributos são armazenados em um único chamado ``` $_private_attributes ```, que é um array do tipo chave=>valor, onde a chave é o nome da coluna da tabela. De qualquer forma, esse array nunca deve ser acessado diretamente.<br>
Para acessar os atributos privados são criados, também automaticamente, métodos get e set para cada atributo. O padrão de nome desses métodos é: ```get``` + nome do atributo em camelcase. Assim, um atributo *nome* gerará os métodos ```getNome()``` e ```setNome($valor)```, e um atributo *nome_pai* gerará os métodos ```getNomePai()``` e ```setNomePai($valor)```.<br>
Além disso, existem os métodos ```get($atributo)``` e ```set($atributo, $valor)``` para acesso direto à variável ```$_private_attributes```. Neste caso, o ```$atributo``` passado deve ser em formado underscore.
<br>
##### Variável ``` $_table ```
Define o nome da tabela referente no banco de dados. Não é necessário declarar esta variável. Por convenção o nome da tabela será a conversão do nome da classe, que deverá estar em formato camelcase, para o formato underscore. Assim, uma classe chamada *Usuario* deverá estar associada, por convenção, a uma tabela *usuario*, e uma classe *PessoaFisica* a uma tabela *pessoa_fisica*.<br>
Caso o nome da tabela não esteja nesse padrão, a variável ``` $_table ``` deve ser declarada contendo o nome correto.
<br>
##### Variável ``` $primary_key ```
Esta variável teoricamente deveria representar o nome da chave primária da tabela, porém ainda não é totalmente utilizada. Então não altere o valor default *id* e certifique-se que sua tabela tenha uma chave primária chamada *id*.
<br>
##### Relacionamento ```$belongs_to```
Por convenção, todo campo terminado com *_id* gerará automaticamente um método de acesso ao objeto a que ele, teoricamente, faz referência. Em termos práticos, uma coluna com nome *perfil_id* gerará um método ```perfil()``` que assumirá que existe uma classe *Perfil* com um atributo *id*, e que retornará o objeto do tipo ```Perfil``` referente. Prático, não?<br>
Além disso, é possível declarar esses relacionamento explicitamente na classe através da variável ```$belongs_to```. Ela deve conter um array descrevendo todos os relaciomentos deste tipo. Por exemplo:
```php
public $belongs_to = array(
  "perfil" => array("field" => "perfil_id", "class" => "Perfil"),
  "chefe" => array("field" => "usuario_chefe_id", "class" => "Usuario"),
  "departamento"
);
```
No exemplo acima, várias coisas poderiam ser omitidas e tomadas por convenção. O relacionamento *departamento* irá assumir que exista a classe *Departamento* e que a coluna na tabela seja *departamento_id*. No relacionamento *perfil*, tanto o ```field``` quanto a ```class``` poderiam ser omitidos que a convenção iria assumi-los automaticamente. No caso do *chefe* são obrigatórios, já que o ```field``` e a ```class``` não seguem o padrão.<br>
Para cada relacionamento será gerado um método com o mesmo nome para permitir o acesso aos objetos das respectivas classes. No exemplo acima seriam criados os métodos ```perfil()```, ```chefe()``` e ```departamento()```.
<br>
##### Relacionamento ```$has_many```
Os relacionamentos do tipo *has_many* deve ser sempre declarados e não são assumidos por convenção.
```php
public $has_many = array(
  "dependentes" => array("class" => "Usuario", "field" => "pai_id", "order" => "nome"),
  "tarefas" => array("class" => "Tarefa", "field" => "usuario_id", "destroy_dependants" => true)
);
```
No caso dos relaciomentos *has_many*, apesar de existir convenção para ```class``` e ```field``` é recomendado que sejam explicitamente definidos.<br>
A chave ```order``` do array quando omitida será assumido o campo de ordenação padrão da classe, obtido através do método ```_default_order()``` (mais detalhes sobre isso mais adiante).<br>
Cada relacionamento gerará um método de mesmo nome contendo um array de objetos do tipo definido na chave ```class```. No exemplo, teríamos os métodos ```dependentes()``` e ```tarefas()```.<br>
Caso o parâmetro ```destroy_dependants``` seja passado com valor ```true```, quando um objeto da classe é removido, todos os objetos do relacionamento também serão. Use com cuidado.<br><br>
Em relacionamentos *n para n*, onde existe uma tabela intermediária fazendo a junção, é possível utilizar a chave *through* no relacionamento ```$has_many```. Quando utilizada, é preciso definir também a classe final dos objetos retornados:
```php
public $has_many = array(
  "permissoes" => array("class" => "Permissao", "through" => "UsuarioPermissao", "foreign_key" => "permissao_id", "field" => "usuario_id"),
  "perfis" => array("class" => "Perfil", "through" => "UsuarioPerfil", "order" => "nome")
);
```
No exemplo acima, um objeto da classe em questão (usuario) terá os métodos ```permissoes()``` e ```perfis()```, retornando o array de objetos dos respectivos tipos.<br>
A chave *foreign_key* pode ser omitida, sendo assumido o valor da chave *class* em formato underscore com o sufixo *_id*. A chave *field* pode ser omitida, sendo assumido o nome da classe do objeto em formato underscore com o sufixo *_id*.<br>
Para que isso funcione, a tabela de relacionamento deve conter uma coluna *id* e as duas colunas contendo os ids das duas tabelas, no mínimo.

<br>
##### Validates
Provavelmente o mais interessante da classe MY_Model são os validates.<br>
Validates permitem definir na própria classe as regras de validação para cada atributo, similar à maneira de como é feito no *Ruby on Rails*. Assim, não é mais necessário o uso do form_validation nos controllers, exceto em casos muito específicos, e fica garantido que em qualquer parte do sistema as regras de existência de um objeto sejam sempre as mesmas.<br>
As validações são feitas utilizando o próprio form_validation do CodeIgniter, então quem já está familiarizado não terá nenhuma dificuldade.
```php
static $validates = array(
  array("nome", "Nome", "required|min_length[20]"),
  array("idade", "Idade", "required|is_numeric"),
  array("sexo", "Sexo", "required|callback_validation_sexo")
);

public function validation_sexo($sexo){
  $sexos_aceitaveis = array("m", "f");
  return in_array($sexo, $sexos_aceitaveis);
}
```
Como mencionado, é idêntico ao uso no form_validation nos controllers.<br>
Para validações particulares, com o prefixo ```callback_```, os métodos de validação ficam na própria classe, assim como é feito nos controllers.<br>
As regras de validação podem ser consultadas <a href="https://ellislab.com/codeigniter/user-guide/libraries/form_validation.html#rulereference">aqui</a>.<br>
Mais adiante serão descritos os métodos de validação adicionais da library form_validation modificada.
<br>
##### Métodos estáticos (*class methods*) da classe MY_Model
###### get_called_class($bt = false, $l = 1)
Retorna a classe que originou a chamada ao método. Útil para os métodos saberem o nome exato da classe que executou a chamada.<br>
Normalmente é usado apenas em funções internas da framework. Raramente precisa ser invocada.
###### findBy($campo, $valor)
Retorna o primeiro registro da tabela que tiver a condição ```$campo = $valor```.
###### find($id)
Encontra o registro na tabela da classe chamada que tenha ```id = $id```. Apenas um alias para a função ```findBy()```.
###### findAllBy($campo, $valor, $order = "_default_order")
Retorna todos os registros da tabela com a condição ```$campo = $valor```, considerando a ```$order``` passada.
###### getAll($order = "_default_order")
Retorna um array com todos os registros da classe ordenados pela ```$order``` passada.
###### all($order = "_default_order")
Alias para a função ```getAll()```.
###### count()
Retorna o número de registros da tabela.
###### collection($condicoes = array(), $order = "_default_order")
Retorna um array de objetos da classe chamada atendendo as condições passadas, que podem ser por array ou diretamente como string (cláusula where). Exemplos:
```php
Classe::collection(array("nome" => "PhP"));
Classe::collection(array("nome" => "PhP", "versao" => "5.3"), "nome");
Classe::collection("nome = 'PhP' AND versao <> '4.0'");
```
###### getInstance()
Retorna uma instância da classe atual para ser usado em métodos estáticos que precisam de um objeto da classe para qualquer coisa. Funciona como um singleton.
###### columns($instancia = null)
Retorna a lista de campos da tabela da classe.<br>
Em alguns casos é preciso passar uma instância da classe como parâmetro, para evitar problemas de recursividade e loops infinitos.
###### first()
Retorna o primeiro registro da tabela ordenado por *id*.
###### last()
Retorna o último registro da tabela ordenado por *id*.
<br>
##### Métodos públicos (*instance methods*) da classe MY_Model
###### classAttributes()
Retorna um array com todos os atributos públicos e protegidos de um objeto.
###### toArray()
Retorna o objeto convertido para um array do tipo chave=>valor, onde cada atributo equivale a uma entrada desse array.
###### delete()
Remove o objeto atual do banco de dados, verificando primeiro se ele está apto a ser removido (através do método ```isRemovable()```).
###### isValid($skip_fields = array())
Executa o form_validation no objeto considerando as regras definidas na variável estátiva ```$validates```, retorna ```true``` ou ```false```.<br>
Caso seja passado o parâmetro ```$skip_fields```, esses campos não serão validados.
###### getErrorsAsArray()
Retorna os erros do objeto gerados pela chamada do ```isValid()``` em formato ```array("campo" => "Erro para o campo")```.
###### getErrorsAsString($separator = "")
Retorna os erros do objeto gerados pela chamada do ```isValid()``` em formado string concatenando-os com o ```$separator```.
###### save($skip_validation = false)
Salva os valores dos atributos do objeto no banco de dados, criando ou editando conforme necessidade.<br>
Caso seja passado ```true``` como parâmetro, as validações serão ignoradas.
###### isPersisted()
Retorna se o registro já existe no banco de dados, baseado no fato do *id* estar setado ou não.
###### isRemovable()
Cada classe poderá implementar este método para definir se um objeto pode ser removido do banco ou não.<br>
No método ```delete()``` será verificado se o objeto ```isRemovable()```.
###### __call($method, $args)
Método mágico que faz chamada de método através de um atributo (difícil até de explicar).<br>
Isso intercepta as chamadas de método para fazer o esquema de entender os relacionamentos e os métodos get e set.<br>
*Pode ignorar a existência desse método*
###### get($attr)
Retorna o valor de um atributo privado do objeto.
###### set($attr, $value)
Seta o valor de um atributo privado do objeto.
###### _tablename()
Retorna o nome da tabela do objeto.
###### _default_order()
Retorna o campo padrão para ordenação da tabela.
<br>
##### Comportamento *acts_as_list*
A classe MY_Model dá suporte a um comportamente especial chamado *acts_as_list*. Como o nome sugere, esse comportamento permite definir que determinada classe irá funcionar como uma lista ordenada de registro. A única exigêcia para utilizar este recurso é a tabela possuir uma coluna que irá armazenar a posição do objeto na lista. Exemplos.:
```php
public $acts_as_list = array("field" => "ordem", "scope" => "sexo");
```
No exemplo acima, definimos que a classe em questão agirá como uma lista, usando o campo *ordem* para armazenar a posição e *sexo* será o group by da lista. Considerando que temos 2 sexos ("f" e "m"), nossa tabela teria duas listas, uma para cada sexo.<br>
A chave ```field``` pode ser omitida, sendo assumido que o campo da tabela que cuidará de armazenar a posição chama-se *ordem*.<br>
A chave ```scope``` define quais campos devem ser iguais para que um conjunto de registros seja considerado uma lista. Se não for informado nenhum scope, toda a tabela funcionará como uma grande lista. Caso haja apenas um campo para agrupar os registros (como no caso do sexo), pode ser informado como string. Caso haja mais de um campo (por sexo e por perfil, por exemplo), deve ser informado em forma de array.<br>
É possível ainda não atribuir nenhum valor à variável. Neste caso será assumido o ```field``` padrão ("ordem") e nenhum ```scope```.<br>
Outros exemplos:
```php
public $acts_as_list;
public $acts_as_list = array("scope" => array("sexo", "perfil_id"));
```
Ao usar o ```$acts_as_list``` uma série de métodos públicos estarão disponíveis. São eles:
###### previousRecordOnList()
Retorna o objeto anterior na lista.
###### nextRecordOnList()
Retorna o próximo objeto na lista.
###### nextRecordsOnList()
Retorna a lista de objetos posteriores na lista.
###### moveUpOnList()
Move o objeto uma posição acima na lista.
###### moveDownOnList()
Move o objeto uma posição abaixo na lista.
###### lastRecordOnList()
Retorna o último registro da lista de um objeto.
###### lastPositionOnList()
Retorna a posição do último elemento da lista do objeto.
###### isLastOnList()
Retorna se o objeto é o último elemento em sua lista.
###### isFirstOnList()
Retorna se o objeto é o primeiro elemento em sua lista.
###### whereClauseFromScope()
Retorna o texto da cláusula where baseado no scope.

<br>
##### Comportamento *acts_as_tree*
A classe MY_Model dá suporte a um comportamente especial chamado *acts_as_tree*. Esse comportamento permite definir que determinada classe irá funcionar como uma hierarquia de registros. A única exigência para utilizar este recurso é a tabela possuir as colunas lft (left), rgt (right), lvl (level) e a coluna que servirá como base para guiar a hierarquia, por definição chamado de parent_id. Exemplo:
```php
public $acts_as_tree = array("order" => "nome");
```
No exemplo acima, definimos que a classe em questão agirá como uma árvore, ordenando por *nome*.<br>
A chave ```destroy_dependants``` define se na deleção os dependentes do registro em questão serão apagados (deleção em cascata).<br>
É possível ainda não atribuir nenhum valor à variável. Neste caso será assumido o ```order``` padrão ("lft") e  ```destroy_dependants``` padrão ("false").<br>
Outros exemplos:
```php
public $acts_as_tree;
public $acts_as_tree = array("order" => "nome", "destroy_dependants" => true);
```
Ao usar o ```$acts_as_tree``` uma série de métodos públicos estarão disponíveis. São eles:
###### parentOnTree()
Retorna o item imediatamente acima na hierarquia.
###### levelOnTree()
Retorna o level do item na hierarquia.
###### childrenOnTree($order = "_default_order")
Retorna os filhos imediados do item.
###### childrenTree()
Retorna a árvore de filhos do item incluindo ele mesmo.
###### pathToRoot($reverse = false)
Retorna a lista de pais do item incluindo ele mesmo.
###### numberOfDescendants()
Retorna a quantidade de descendentes do item.
###### rebuildTree($parent = null)
Remonta a tree completamente com base no parent da tabela.
###### isValidAsTree()
Verifica se a tree é válida verificando se não irá gerar hierarquia recursiva.

<br>
### Library form_validation
-----------
Algumas coisas foram alteradas no form_validation para suportar as validações via model.<br>
As mensagens de exibição agora são agrupadas. Caso existam 10 erros em um formulário todos eles estarão dentro de uma só div, e não um em cada separada como era antes.<br>
Validações adicionadas:
###### is_not_null($str)
Validação que verifica se o campo é nulo usando a function isNull().
###### data_valida($str)
Verifica se a data é válida.
###### not_data_futura($str)
Verifica se a data é maior que hoje.
###### minimo_dias_anteriores($str, $dias_pra_tras)
Verifica se a data não é anterior a x dias.
###### valor_entre($valor, $intervalo)
Verifica se o campo está compreendido no intervalo passado.<br>
O intervalo é passado no formato 1..6 (mínimo..máximo)
###### hora1_maior_hora2($hora1, $campo_hora2)
Verifica se a hora1 é menor que a hora do campo 2.
###### data1_maior_data2($data1, $campo_data2)
Verifica se a data1 é menor que a data do campo 2.

<br>
### Helpers
-----------
Os helpers contidos na HOW englobam uma séries de funções auxiliares, que vão desde manipulação de strings até validações, formatações e geradores de html.
<br>
##### Helper de arquivos
###### send_file($file)
"Cospe" um arquivo para download.
###### remover_arquivo($arquivo)
Remove um arquivo do disco.
###### carregar_arquivo($arquivo, $params = array())
Retorna uma string contendo o conteúdo do $arquivo passado.
<br>
##### Helper de arrays
###### map($array, $key)
Dado um $array de arrays ou de objetos, temos:
- Para $array de arrays é retornado um novo array contendo cada valor da posição $key no $array.
- Para $array de objeto é retornado um novo array contendo cada valor do atributo $key do $array.<br>
Exemplo:
```php
map(array("id" => 5, "id" => 3), "id"); //retornará array(5, 3)
```
###### array_first($array)
Retorna o primeiro elemento de um array.
<br>
##### Helper de banco de dados
###### fields_of($tabela)
Retorna um array contendo os campos da tabela passada.
<br>
##### Helper de codificações
###### inserir_acentos($string)
Substitui caracteres especiais por seus respectivos códigos em html.
###### inserir_acentos2($string)
Outra forma de substituir caracteres especiais por seus respectivos códigos em html.
###### normalizar($string)
Retira os acentos, caracteres especiais e espaços de uma string, além de converte-la para lowercase.
###### capitalizar_nome($string)
Converte um nome todo em maiúsculo para uma string com as primeiras letras maiúsculas, ignorando algumas palavras que não devem ser capitalizadas.
<br>
##### Helper de datas
###### data_br_to_bd($data)
Converte uma data do tipo dd/mm/aaaa para aaaa-mm-dd.
###### data_br_to_en($data)
Converte uma data do tipo mm/dd/aaaa para mm/dd/aaaa.
###### data_bd_to_br($data)
Converte uma data do tipo aaaa-mm-dd para dd/mm/aaaa.
###### datetime_to_br($datetime)
Converte algo do tipo "2014-11-06 15:54:30" para "06/11/2014 às 15:54".
###### data_valida($data, $padrao = "br")
Verifica se a data passada é válida de acordo com o padrão passado (br ou bd).
###### hora_from_datetime($datetime)
Retorna a hora de um datetime passado.
###### diferenca_entre_horas($inicial, $final, $retorno = "horas")
Retorna a diferença em horas entre as duas horas passadas.
###### dia_semana($data, $padrao = "bd")
Retorna o dia da semana da data passada.
###### operacao_data($data, $operacao)
Executa operações do tipo "+3 year" ou "-5 days" na data passada.
###### hora_float_to_time($hora)
Converte uma hora do tipo 5.5 para 05:30.
###### dias_entre_datas($data1, $data2, $conta_com_ultima_data = false)
Retorna a quantidade de dias entre duas datas.
###### data1_maior_que_data2($data1, $data2)
Verifica se a $data1 é maior que a $data2.
###### data_buscar_semanas_completa($data_inicio, $data_fim)
Retorna um array com todas as datas compreendidas entre a $data_inicio e a $data_fim junto com as datas extras para formar a semana. Da $data_inicio voltamos até encontrar um domingo e da $data_fim avançamos até encontrar um sábado.
###### hora_valida($hora)
Verifica se a hora passada está no formado hh:mm:ss e se é uma hora válida.
###### datetime_valido($datetime)
Verifica se o datetime passado é válido.
###### hoje($padrao = "bd")
Retorna a data de hoje.
###### now()
Retorna o datetime deste momento.
###### tempo_relativo($datetime)
Retorna algo do tipo "10 minutos atrás" de acordo com o $datetime passado.
###### data_is_entre($data, $periodo1, $periodo2)
Verifica se a $data está contida entre duas outras datas.
<br>
##### Helper de email
###### enviar_email_smtp($destinatario, $assunto, $corpo)
Processa um envio de email via smtp.
###### enviar_email_convencional($destinatario, $assunto, $corpo)
Processa um envio de email via função mail().
###### enviar_email($destinatario, $assunto, $corpo)
De acordo como a função for escrita, enviará email via smtp ou mail(). O ideal é configurar esta função e chamar somente ele.
<br>
##### Helper de formulários
###### obrigatorio()
Retorna aquele * para ser usado nos campos obrigatórios.
##### options_for_select($options, $selecionado = "", $prompt = false)
Retorna o html das options a partir de um array para ser usado em selects.
##### options_for_select_from_collection($collection, $value, $label, $selecionado = "", $prompt = true)
Retorna uma string de options a partir da collection passada. A collection é um array de objetos.
<br>
##### Helper de HTMLs auxiliares
###### breadcrumb($itens = array())
Gera o html do breadcrumb de acordo com o array passado.
##### leia_mais_automatico($texto, $limite = 250, $mostrar_link = true)
Corta o texto no comprimento passado e gera os links "mais" e "menos" quando necessário.
##### brs($number = 1)
Retorna uma string contendo um determinado número de tags <br>.
<br>
##### Helper de paginação
###### usar_paginacao($resultados_por_pagina = 10, $tamanho_paginacao = 2)
Inicia as constantes usadas na paginacao de resultados.
###### paginacao($total_resultados, $pagina, $url, $parametros = "")
Retorna o html da paginação.
<br>
##### Helper de strings, mensagens e validações
###### underscore_to_camel_case($string, $capitalizeFirstCharacter = false)
Converte algo do tipo "algo_do_tipo" para "algoDoTipo".
###### camel_case_to_underscore($string)
Converte algo do tipo "algoDoTipo" para "algo_do_tipo".
###### formatar_string($string, $tipo)
Formata a string passada de acordo com o tipo de máscada passada.<br>
Opções de máscaras: cpf, cnpj, cep e telefone.
###### custom_message($tipo, $texto)
Retorna o html de cada tipo de mensagem customizada de informação. A implementação das classes no css são necessárias para que cada tipo de mensagem possua uma cor adequada.
###### flash_messages()
Retorna o html das mensagens para serem exibidas nas páginas, tanto de erros de formulários quanto mensagens flash.
###### set_message($tipo, $mensagem)
Coloca na sessão as mensagens de flash para serem obtidas através da function ```flash_messages()``` posteriormente.
###### isNull($var)
Verifica se uma variável é nula, inclusive se seu conteúdo for "null" ou "{{null}}".
###### vazio($string)
Retorna se a string ou array passado é vazio. Verifica também se um texto possui somente enters em seu conteudo.

<br>
### Cache a nível de request
-----------
O HOW implementa um cache que permite armazenar resultados de funções e objetos de classes herdeiras de MY_Model. Com isso as idas ao banco diminuem consideravelmente, já um objeto de uma classe com determinado id será buscado no banco apenas uma vez a cada request.
###### ```HowCore::disableCache()```
Desativa o cache para todo o restante da execução do request.
###### ```HowCore::enableCache()```
Ativa o cache para todo o restante da execução do request.
###### ```HowCore::getCachedFunction($function_name, $args)```
Obtém o valor cacheado de uma chamada de função. Exemplo de uso encontra-se na função ```fields_of``` no helper de banco de dados.
###### ```HowCore::setCachedFunction($function_name, $args, $value)```
Salva o retorno de uma função no cache. Exemplo de uso encontra-se na função ```fields_of``` no helper de banco de dados.
###### ```HowCore::getCachedObject($class, $id)```
Obtém do cache o objeto da $class informada com o $id informado, caso já exista em cache. Exemplo de uso encontra-se no método ```findBy``` da classe MY_model.
###### ```HowCore::setCachedObject($object)```
Salva um objeto do banco no cache. Exemplo de uso encontra-se no método ```findBy``` da classe MY_model.
###### ```HowCore::unsetCachedObject($object)```
Remove um objeto do cache caso ele seja deletado do banco. Exemplo de uso encontra-se no método ```delete``` da classe MY_model.

<br>
### Gerenciamento de Template
-----------
O gerenciamento de templates é feito através do <a href="http://www.grocerycrud.com/codeigniter-simplicity">CI Simplicity</a>. Para informações de uso, consulte o site.

<br>
### Testes Unitários
-----------
Caso queira utilizar testes unitários, a HOW já conta com o <a href="https://github.com/ericbarnes/codeigniter-simpletest">CodeIgniter SimpleTest</a>.<br>
Todos os helpers da framework já possuem testes capazes de serem executados através do arquivo ```unit_test.php```.<br>
Para informações de uso, consulte o site.
