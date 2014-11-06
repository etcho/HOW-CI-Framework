###### [H]and [O]n [W]heel [C]ode[I]gniter Framework

HOW é uma extensão do CodeIgniter que engloba basicamente um model genérico capaz de fornecer uma orientação a objetos mais forte que o CI_Model. Além disso, conta com um conjunto de helpers com funções dos mais variados gêneros.

### MY_Model
-------------------
Classe abstrata responsável pelo mapeamento objeto-relacional do banco. É recomendável que cada tabela do banco possua uma classe relacionada que herde desta. Por adotar convenções, a definição das classes pode ser muito simples:
```php
class Usuario extends MY_Model {
}
```
O exemplo acima é para uma classe que mapeará a tabela 'usuario' no banco de dados, escrita da forma mais básica possível.

##### Métodos get e set e atributos privados
Os atributos privados são gerados automaticamente a partir do schema do banco. Logo cada coluna da tabela resultará em um atributo da classe.<br>
Apenas a título de conhecimento, todos os atributos são armazenados em um único chamado ``` $_private_attributes ```, que é um array do tipo chave=>valor, onde a chave é o nome da coluna da tabela. De qualquer forma, esse array nunca deve ser acessado diretamente.<br>
Para acessar os atributos privados são criados, também automaticamente, métodos get e set para cada atributo. O padrão de nome desses métodos é: ```get``` + nome do atributo em camelcase. Assim, um atributo *nome* gerará os métodos ```getNome()``` e ```setNome($valor)```, e um atributo *nome_pai* gerará os métodos ```getNomePai()``` e ```setNomePai($valor)```.<br>
Além disso, existem os métodos ```get($atributo)``` e ```set($atributo, $valor)``` para acesso direto à variável ```$_private_attributes```. Neste caso, o ```$atributo``` passado deve ser em formado underscore.

##### Variável ``` $_table ```
Define o nome da tabela referente no banco de dados. Não é necessário declarar esta variável. Por convenção o nome da tabela será a conversão do nome da classe, que deverá estar em formato camelcase, para o formato underscore. Assim, uma classe chamada *Usuario* deverá estar associada, por convenção, a uma tabela *usuario*, e uma classe *PessoaFisica* a uma tabela *pessoa_fisica*.<br>
Caso o nome da tabela não esteja nesse padrão, a variável ``` $_table ``` deve ser declarada contendo o nome correto.

##### Variável ``` $primary_key ```
Esta variável teoricamente deveria representar o nome da chave primária da tabela, porém ainda não é totalmente utilizada. Então não altere o valor default *id* e certifique-se que sua tabela tenha uma chave primária chamada *id*.

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

##### Relacionamento ```$has_many```
Os relacionamentos do tipo *has_many* deve ser sempre declarados e não são assumidos por convenção.
```php
public $has_many = array(
  "dependentes" => array("class" => "Usuario", "field" => "pai_id", "order" => "nome"),
  "tarefas" => array("class" => "Tarefa", "field" => "usuario_id")
);
```
No caso dos relaciomentos *has_many*, apesar de existir convenção para ```class``` e ```field``` é recomendado que sejam explicitamente definidos.<br>
A chave ```order``` do array quando omitida será assumido o campo de ordenação padrão da classe, obtido através do método ```_default_order()``` (mais detalhes sobre isso mais adiante).<br>
Cada relacionamento gerará um método de mesmo nome contendo um array de objetos do tipo definido na chave ```class```. No exemplo, teríamos os métodos ```dependentes()``` e ```tarefas()```.

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

##### Métodos estáticos da classe MY_Model
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
Classe::collection(array("nome" => "PhP, "versao" => "5.3"), "nome");
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

##### Métodos públicos da classe MY_Model
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
