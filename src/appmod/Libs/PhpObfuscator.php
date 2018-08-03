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
    private $decode_errors = true;

    private $obfuscated    = '';

    private $errors        = [];

    private $packer_function  = null;

    private $argumenter_function       = null;

    protected $map_packer_functions = [
        'cfForgetShow'  => 'breakOne',
        'cryptOf'       => 'breakTwo',
        'unsetZeros'    => 'breakOne',
        'deflatingNow'  => 'breakTwo',
        'zeroizeCipher' => 'breakThree',
        'iqutZ'         => 'breakTwo',
        'sagaPlus'      => 'breakThree',
    ];

    protected $map_argumenter_functions = [
        'decompressMD5',
        'unsetLogger',
        'loopNested',
        'vorticeData',
        'cipherBinary'
    ];

    /**
     * Habilita a liberação de erros de dentro do
     * código já ofuscado
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
     * Devolve o nome do 'empacotador' que, internamente,
     * será responsável pela compressão/descompressão do código.
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
     * Devolve o nome do método que será copiado para dentro
     * do 'empacotador' que, internamente, será responsável pela
     * compressão/descompressão do código.
     *
     * @return string
     */
    protected function getPackerMethodName()
    {
        $fake_name = $this->getPackerName();
        return $this->map_packer_functions[$fake_name];
    }

    /**
     * Devolve o nome da função falsa que será usada como argumento do
     * 'empacotador' no momento da descompressão do código.
     * Trata-se de uma função que sempre retorna 'true',
     * apenas com nomes ramdomicamente diferentes.
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
     * Seta a localização do arquivo que será usado para reversão.
     * Este arquivo deverá ser incluído para que a reversão possa acontecer.
     *
     * @param [type] $filename [description]
     */
    public function setRevertFilename($filename)
    {

    }

    /**
     * Ofusca o arquivo especificado.
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
            $this->errors[] = 'Código misto encontrado. Arquivo não ofuscado.';
            $this->obfuscated = $contents;
        }

        return $this;
    }



    /**
     * Ofusca o código espeficicado.
     *
     * @param  string  $php_code
     * @param  integer $levels   [description]
     * @param  [type]  $host     [description]
     * @return string
     */
    public function obfuscateString(string $php_code)
    {
        $plain_code = $this->phpWrapperRemove($php_code);
        if ($plain_code == false) {
            return false;
        }

        return $this->wrapCode($plain_code);
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

        $string = '';
        $string.= $this->toASCII($prefix. "eval({$unpacker_function}("); // esconde a função descompactar
        $string.= "'" . $this->{$packer_method}($code) . "'";            // executa a função compactar
        $string.= $this->toASCII(",{$argumenter}())); ");

        return $this->phpWrapperAdd($string);
    }

    /**
     * Adiciona os invólucros do PHP.
     *
     * @param  string $code
     * @return string
     */
    protected function phpWrapperAdd(string $code) : string
    {
        return "<?php\n eval(\"{$code}\");";
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


    protected function generateRevertFile()
    {
        $base_one = $this->extractMethod('breakOne', 'baseOne');
        dd($base_one);
    }



    protected function createInflateFunction($function_name)
    {
        $unbreak_name = $this->inflate_functions[$function_name];

        return "function {$function_name}(\$data, \$revert = false){ "
             . "return baseThree(\$data, \$revert); "
             . "}";
    }

    protected function createKeyFunction($function_name)
    {
        return "function {$function_name}(){ return true; }";
    }

    private function extractMethod($method_name, $function_name)
    {
        $method = new \ReflectionMethod(__CLASS__, $method_name);
        $start_line = $method->getStartLine(); // it's actually - 1, otherwise you wont get the function() block
        $end_line = $method->getEndLine();
        $length = $end_line - $start_line;

        $source = file(__FILE__);
        return $function_name . "(\$data, \$revert = false)"
            . implode("", array_slice($source, $start_line, $length));
    }

    //
    // Métodos para compactação e descompactação de código
    // Estes métodos são adicionados automaticamente nas rotinas
    // para que a ofuscação possa ser desfeita
    //

    public function breakOne($data, $revert = false)
    {
        if ($revert == false) {

            $encoded = base64_encode($data);

            // Separa em dois pedaços
            $partOne = mb_substr($encoded, 0, 10, "utf-8");
            $partTwo = mb_substr($encoded, 10, null, "utf-8");

            // Insere 'Sg' para invalidar o base64
            return $partOne . 'Sg' . $partTwo;
        }

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 10, "utf-8");
        $partTwo = mb_substr($data, 12, null, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }

    public function breakTwo($data, $revert = false)
    {
        if ($revert == false) {

            $encoded = base64_encode($data);

            // Separa em dois pedaços
            $partOne = mb_substr($encoded, 0, 5, "utf-8");
            $partTwo = mb_substr($encoded, 5, null, "utf-8");

            // Insere 'Sg' para invalidar o base64
            return $partOne . 'Sg' . $partTwo;
        }

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 5, "utf-8");
        $partTwo = mb_substr($data, 7, null, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }

    public function breakThree($data, $revert = false)
    {
        if ($revert == false) {

            $encoded = base64_encode($data);

            // Separa em dois pedaços
            $partOne = mb_substr($encoded, 0, 15, "utf-8");
            $partTwo = mb_substr($encoded, 15, null, "utf-8");

            // Insere 'Sg' para invalidar o base64
            return $partOne . 'Sg' . $partTwo;
        }

        // Separa em dois pedaços
        $partOne = mb_substr($data, 0, 15, "utf-8");
        $partTwo = mb_substr($data, 17, null, "utf-8");

        // Remove 'Sg' para validar o base64
        return base64_decode($partOne . $partTwo);
    }





}
