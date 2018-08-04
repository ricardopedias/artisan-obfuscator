<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace Obfuscator\Libs;

/**
 * Esta biblioteca ofusca o código PHP, adicionando dependencias que serão
 * exigidas no momento da reversão da ofuscação.
 * Para que os arquivos resultantes sejam executados com sucesso, o conteúdo
 * do arquivo RevertObfuscation.php deve estar acessível para o código ofuscado.
 */
class PhpObfuscator
{
    /**
     * Controla se o código, depois de ofuscado, pode disparar erros para o
     * usuário ou se eles devem ocorrer silenciosamente sem ser reportados
     *
     * @var bool
     */
    private $decode_errors = true;

    /**
     * O código resultanre do processo de ofuscação
     * é armazenado neste atributo.
     *
     * @var string
     */
    private $obfuscated    = '';

    /**
     * Armazena os erros ocorridos no momento de gerar o código ofuscado.
     *
     * @var array
     */
    private $encoding_messages        = [];

    /**
     * Função usada para desempacotar o código.
     *
     * @var string
     */
    private $packer_function  = null;

    /**
     * Função usada para parametrizar o desempacotamento do código.
     *
     * @var string
     */
    private $argumenter_function       = null;

    /**
     * Lista de funções ramdomicas com seus respectivos métodos de empacotamento/desempacotamento.
     * Os empacotadores/desempacotadores são fornecidos sem o sufixo.
     * Ex: 'packerOne' pode ser invocado
     * como 'packerOnePack' para empacotar código ou
     * como 'packerOneUnpack' para desempacotá-lo.
     *
     * @var array
     */
    protected $map_packer_functions = [
        'cfForgetShow'  => 'packerOne',
        'cryptOf'       => 'packerTwo',
        'unsetZeros'    => 'packerOne',
        'deflatingNow'  => 'packerTwo',
        'zeroizeCipher' => 'packerThree',
        'iqutZ'         => 'packerTwo',
        'sagaPlus'      => 'packerThree',
    ];

    /**
     * Lista com as funções usadas para parametrizar o desempacotamento.
     *
     * @var array
     */
    protected $map_argumenter_functions = [
        'decompressMD5',
        'unsetLogger',
        'loopNested',
        'vorticeData',
        'cipherBinary'
    ];

    /**
     * Controla se o código, depois de ofuscado, pode disparar erros para o
     * usuário ou se eles devem ocorrer silenciosamente sem ser reportados
     *
     * @param  boolean $enable
     * @return \Obfuscator\Libs\PhpObfuscator
     */
    public function enableDecodeErrors($enable = true)
    {
        $this->decode_errors = $enable;
        return $this;
    }

    /**
     * Devolve um nome randomicamente escolhido para o 'empacotador' que,
     * internamente, será responsável pela compressão/descompressão do código.
     *
     * @return string
     */
    protected function getPackerName()
    {
        if ($this->packer_function != null) {
            // Lazyload paa devolver apenas um nome por instância
            return $this->packer_function;
        }

        $list = array_keys($this->map_packer_functions);
        $this->packer_function = $list[array_rand($list)];
        return $this->packer_function;
    }

    /**
     * Devolve um nome randomicamente escolhido para o método 'empacotador' que,
     * internamente, será responsável pela compressão/descompressão do código.
     * Este método será copiado para dentro de uma função que acompanhará o
     * código ofuscado para permitir a descompressão.
     *
     * @return string
     */
    protected function getPackerMethodName()
    {
        $fake_name = $this->getPackerName();
        return $this->map_packer_functions[$fake_name];
    }

    /**
     * Devolve um nome randomicamente escolhido para a função
     * que será usada como argumento do desempacotador no ato
     * de desafazer a ofuscação.
     *
     * @return string
     */
    protected function getArgumenterName()
    {
        if ($this->argumenter_function  != null) {
            // Lazyload paa devolver apenas um nome por instância
            return $this->argumenter_function ;
        }

        $keys = $this->map_argumenter_functions;
        $this->argumenter_function  = $keys[array_rand($keys)];
        return $this->argumenter_function ;
    }

    /**
     * Ofusca o arquivo especificado e armazena-o na memória.
     *
     * @param  string $origin_file
     * @return \Obfuscator\Libs\PhpObfuscator
     */
    public function obfuscateFile($origin_file)
    {
        if (strtolower(pathinfo($origin_file, PATHINFO_EXTENSION)) != 'php') {
            throw new \Exception("Apenas arquivos PHP podem ser ofuscados!");
        }

        // Remove os espaços e comentários do arquivo
        $contents = php_strip_whitespace($origin_file);

        $this->obfuscated = $this->obfuscateString($contents);

        if ($this->obfuscated == false) {
            $this->encoding_messages[] = 'Código misto encontrado. Arquivo não ofuscado.';
            $this->obfuscated = $contents;
        }

        return $this;
    }

    /**
     * Ofusca o código especificado através de uma string.
     * Ativando o argumento $use_zencoding, a função 'php_zencoding'
     * será usada no lugar das funções ramdômicas de desempacotamento.
     * Isso torma mais fácil para um hacker descobrir a regra de
     * desempacotamento, mas não necessita que sejam incluidas
     * as funções randomicas junto com o código resultante da
     * ofuscação.
     *
     * @param  string  $php_code
     * @param bool $use_zencoding força o uso da função php_zenconding
     * @return string
     */
    public function obfuscateString(string $php_code, bool $use_zencoding = false)
    {
        $plain_code = $this->phpWrapperRemove($php_code);
        if ($plain_code == false) {
            return false;
        }

        return ($use_zencoding == true)
            ? $this->wrapZenCode($plain_code)
            : $this->wrapCode($plain_code);
    }

    /**
     * Remove os invólucros do PHP
     *
     * @param string $code Código php sem ofuscar
     * @return string
     */
    protected function phpWrapperRemove(string $code)
    {
        $matches = [];
        preg_match_all('/\<\?php|\<\?\=/i', $code, $matches);

        // Código misto não será ofuscado
        if(isset($matches[0]) && count($matches[0]) > 1) {
            return false;
        } else {
            return trim(str_replace(["<?php", "<?", "?>"], "", $code));
        }
    }

    /**
     * Embrulha o código num container de ofuscação.
     *
     * @param  string $code
     * @return string
     */
    public function wrapCode(string $code)
    {
        $prefix = ($this->decode_errors == false) ? '' : '@';

        $packer_method = $this->getPackerMethodName();
        $unpacker_function = $this->getPackerName();
        $argumenter  = $this->getArgumenterName();

        // Esconde o código com o desempacotador ramdômico
        $string = '';
        $string.= $this->toASCII($prefix. "eval({$unpacker_function}("); // esconde a função de descompressão
        $string.= "'" . $this->{$packer_method . "Pack"}($code) . "'";  // executa a função compactar
        $string.= $this->toASCII(",{$argumenter}()));");

        return "<?php eval(\"{$string}\");";
    }

    /**
     * Embrulha o código num container de ofuscação para o php_zencoding.
     *
     * @param  string $code
     * @return string
     */
    public function wrapZenCode(string $code)
    {
        $prefix = ($this->decode_errors == false) ? '' : '@';

        // A função php_zencodign é usada para ofuscar as 'funções de descompressão'
        // usadas para desafazer a ofuscação de todos os arquivos php
        $php_zencoding = "function php_zencoding(\$data)\n" . $this->extractMethod('packerOneUnpack');

        // Esconde a função zencoding no próprio arquivo
        $zen = '';
        $zen .= $this->toASCII($prefix. "eval(base64_decode("); // esconde a função de descompressão
        $zen .= "'" . base64_encode($php_zencoding) . "'";  // executa a função compactar
        $zen .= $this->toASCII("));");

        // Esconde o código com o desempacotador php_zencoding
        $string = '';
        $string.= $this->toASCII($prefix. "eval(php_zencoding("); // esconde a função de descompressão
        $string.= "'" . $this->packerOnePack($code) . "'";         // comprime o código
        $string.= $this->toASCII("));");

        return "<?php eval(\"{$zen}\"); eval(\"{$string}\");";
    }

    /**
     * Transforma a string em código hexadecimal ASCII.
     *
     * @param string $string
     * @return string
     */
    private function toASCII($string)
    {
        $ascii = "";

        for ($i = 0; $i < strlen($string); $i ++) {
            $ascii .= '\x' . bin2hex($string{$i});
        }

        return $ascii;
    }

    /**
     * Devolve o código resultante do processo de ofuscação.
     *
     * @return string
     */
    public function getObfuscated()
    {
        return $this->obfuscated;
    }

    /**
     * Salva o código ofuscado no destino especificado.
     *
     * @param  string $destiny
     * @return bool
     */
    public function save($destiny)
    {
        return (file_put_contents($destiny, $this->getObfuscated()) !== false);
    }

    /**
     * Salva as funções responsáveis pela reversão da ofuscação.
     *
     * @param  string $destiny
     * @return bool
     */
    public function saveRevertFile($destiny)
    {
        return (file_put_contents($destiny, $this->getRevertFileContents(true)) !== false);
    }

    /**
     * Coloca as funções de reversão no final do arquivo especificado.
     *
     * @param  string $destiny
     * @return bool
     */
    public function appendRevertFunctions($destiny)
    {
        $contents - $this->getRevertFileContents(true);
    }

    /**
     * Gera o conteúdo do arquivo com as funções de reversão.
     *
     * @param  boolean $obfuscate
     * @return string
     */
    protected function getRevertFileContents($obfuscate = true)
    {

        $lines = [];

        $sp = "    ";

        // Cria os desempacotadores falsos.
        // As chaves e valores são no formato 'função_falsa => metodo'.
        // Ex.:
        // [
        //     'cfForgetShow'  => 'packerOne',
        //     'cryptOf'       => 'packerTwo',
        //     ...
        // ]
        foreach($this->map_packer_functions as $fake_name => $method_name) {

            // Transforma o nome do metodo 'packerOne' para a função 'baseOne'
            $base_name = str_replace('packer', 'base', $method_name);

            $lines[] = $sp . "function {$fake_name}(\$data, \$revert = false){\n"
                     . $sp .$sp . "return {$base_name}(\$data);\n"
                     . $sp . "}\n";
        }

        $bases = array_unique($this->map_packer_functions);
        foreach($bases as $method_name) {

            // Transforma o nome do metodo 'packerOne' para a função 'baseOne'
            $base_name = str_replace('packer', 'base', $method_name);

            $lines[] = $sp . "function {$base_name}(\$data)\n"
                     . $this->extractMethod($method_name . 'Unpack');
        }

        foreach($this->map_argumenter_functions as $method_name) {
            $lines[] = $sp . "function {$method_name}() { return TRUE; }\n";
        }

        $contents = "<?php\n" . implode("\n", $lines);

        return ($obfuscate == true) ? $this->obfuscateString($contents, true) : $contents;
    }

    /**
     * Extrai o conteúdo de um metodo publico desta classe.
     *
     * @param  string $method_name
     * @return string
     */
    private function extractMethod($method_name)
    {
        $method = new \ReflectionMethod(__CLASS__, $method_name);
        $start_line = $method->getStartLine(); // it's actually - 1, otherwise you wont get the function() block
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        $source = file(__FILE__);
        return implode("", array_slice($source, $start_line, $length));
    }

    //
    // Métodos para empacotamento/desempacotamento de código
    // Estes métodos são adicionados automaticamente no
    // arquivo com as rotinas de desempacotamento.
    //

    public function packerOnePack($data)
    {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 10, "utf-8");
        $partTwo = mb_substr($encoded, 10, null, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;
    }

    /**
     * Remove 'Sg' para validar o base64
     *
     * @param  string  $data
     * @param  boolean $revert
     * @return string
     */
    public function packerOneUnpack($data, $revert = false)
    {
        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 10, "utf-8");
        $partTwo = mb_substr($data, 12, null, "utf-8");
        return base64_decode($partOne . $partTwo);
    }

    public function packerTwoPack($data)
    {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 5, "utf-8");
        $partTwo = mb_substr($encoded, 5, null, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;
    }

    /**
     * Remove 'Sg' para validar o base64
     *
     * @param  string  $data
     * @param  boolean $revert
     * @return string
     */
    public function packerTwoUnpack($data, $revert = false)
    {
        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 5, "utf-8");
        $partTwo = mb_substr($data, 7, null, "utf-8");
        return base64_decode($partOne . $partTwo);
    }

    public function packerThreePack($data)
    {
        $encoded = base64_encode($data);

        // Separa em dois pedaços
        $partOne = mb_substr($encoded, 0, 15, "utf-8");
        $partTwo = mb_substr($encoded, 15, null, "utf-8");

        // Insere 'Sg' para invalidar o base64
        return $partOne . 'Sg' . $partTwo;
    }

    /**
     * Remove 'Sg' para validar o base64
     *
     * @param  string  $data
     * @param  boolean $revert
     * @return string
     */
    public function packerThreeUnpack($data, $revert = false)
    {
        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 15, "utf-8");
        $partTwo = mb_substr($data, 17, null, "utf-8");
        return base64_decode($partOne . $partTwo);
    }
}
