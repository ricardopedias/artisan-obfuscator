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
     * O código resultante do processo de ofuscação
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
     * Ex: 'packerOne' pode ser invocado:
     * - como 'packerOnePack' para empacotar código ou
     * - como 'packerOneUnpack' para desempacotá-lo.
     *
     * @var array
     * @todo Mudar isso para que os nomes sejam gerados dinamicamente
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
     * @todo Mudar isso para que os nomes sejam gerados dinamicamente
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
     * Remove os invólucros do PHP <?php e ?>
     * do código especificado
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
     * Devolve o código php especificado de forma ofuscada.
     *
     * @param  string  $php_code
     * @return string
     */
    public function obfuscateString(string $php_code)
    {
        $plain_code = $this->phpWrapperRemove($php_code);
        if ($plain_code == false) {
            return false;
        }

        return $this->wrapString($plain_code);
    }

    /**
     * Embrulha o código num container de ofuscação.
     *
     * @param  string $code
     * @return string
     */
    public function wrapString(string $code)
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
     * Devolve o código php especificado de forma ofuscada.
     * A string especificada deve conter apenas as funções de desempacotamento,
     * pois a forma de ofuscação é diferente para elas poderem funcionar.
     *
     * @param  string  $unpack_code
     * @return string
     */
    public function obfuscateUnpackFunctions(string $unpack_code)
    {
        $plain_code = $this->phpWrapperRemove($unpack_code);
        if ($plain_code == false) {
            return false;
        }

        return $this->wrapUnpackFunctions($plain_code);
    }

    /**
     * Embrulha o código num container de ofuscação.
     * Este método é usado apenas para empacotar as funções de desempacotamento.
     *
     * @param  string $code
     * @return string
     */
    public function wrapUnpackFunctions(string $code)
    {
        $prefix = ($this->decode_errors == false) ? '' : '@';

        // A função php_zencodign é usada para ofuscar as 'funções de descompressão'
        // usadas para desafazer a ofuscação de todos os arquivos php
        $php_zencoding = "function php_zencoding(\$data)\n" . $this->extractMethod('packerOneUnpack');

        // Esconde a função de desempacotamento no próprio arquivo
        $zen = '';
        $zen .= $this->toASCII($prefix . "eval(base64_decode("); // esconde a função de descompressão
        $zen .= "'" . base64_encode($php_zencoding) . "'";  // executa a função compactar
        $zen .= $this->toASCII("));");

        // Esconde o código com o desempacotador php_zencoding
        $string = '';
        $string.= $this->toASCII($prefix . "eval(php_zencoding("); // esconde a função de descompressão
        $string.= "'" . $this->packerOnePack($code) . "'";         // comprime o código
        $string.= $this->toASCII("));");

        return "<?php eval(\"{$zen}\"); eval(\"{$string}\");";
    }

    /**
     * Transforma a string em código hexadecimal ASCII.
     *
     * @see http://php.net/manual/en/function.bin2hex.php
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
     * Se a ofuscação ocorrer com sucesso, uma string ofuscada será devolvida,
     * caso contrário, a string original será retornada no lugar.
     *
     * @return string
     */
    public function getObfuscated()
    {
        return $this->obfuscated;
    }

    /**
     * Salva um arquivo com o código ofuscado no caminho especificado.
     *
     * @param  string $path_destiny
     * @return bool
     */
    public function save($path_destiny)
    {
        return (file_put_contents($path_destiny, $this->getObfuscated()) !== false);
    }

    /**
     * Salva um arquivo com as funções de desempacotamento no caminho especificado.
     * Este arquivo deve ser incluído no projeto antes de qualquer arquivo ofuscado,
     * para que as ofuscações possam ser revertidas em tempo real.
     *
     * @param  string $path_destiny
     * @return bool
     */
    public function saveRevertFile($path_destiny)
    {
        $contents = $this->getRevertFileContents(true); // true = conteudo ofuscado
        return (file_put_contents($path_destiny, $contents) !== false);
    }

    /**
     * Gera uma string contendo todas as funções de desempacotamento.
     *
     * @param  boolean $obfuscate
     * @return string
     */
    protected function getRevertFileContents($obfuscate = true)
    {
        $lines = [];

        $sp = "    ";

        // DESEMPACOTADORES RANDÔMICOS:
        // São várias funções 'falsas' com nomes ramdômicos que,
        // internamente, invocam as funções reais de desempacotamento.
        // Apenas para dificultar o entendimento do hacker :)
        //
        // As chaves e valores são no formato 'desempacotador => metodo'.
        // Ex.:
        // 'cfForgetShow'  => 'packerOne',
        // 'cryptOf'       => 'packerTwo',
        foreach($this->map_packer_functions as $packer_name => $method_name) {
            // Renomeia o prefixo do método 'packerOne'
            // para nomear na função de desempacotamento como 'baseOne'
            $base_name = str_replace('packer', 'base', $method_name);
            $lines[] = $sp . "function {$packer_name}(\$data, \$revert = false){\n"
                     . $sp .$sp . "return {$base_name}(\$data);\n"
                     . $sp . "}\n";
        }

        // DESEMPACOTADORES REAIS:
        // Os desempacotadores randômicos invocam três 'efetivos':
        // baseOne, baseTwo e baseThree
        $bases = array_unique($this->map_packer_functions);
        foreach($bases as $method_name) {
            // Renomeia o prefixo do método 'packerOne'
            // para nomear na função de desempacotamento como 'baseOne'
            $base_name = str_replace('packer', 'base', $method_name);
            $lines[] = $sp . "function {$base_name}(\$data)\n"
                     // Extrai o conteúdo do método 'packer???Unpack'
                     // para gerar a função 'base???'
                     . $this->extractMethod($method_name . 'Unpack');
        }

        // ARGUMENTADORES RAMDÔMICOS:
        // São várias funções 'falsas' com nomes ramdômicos que,
        // internamente apenas devolvem uma valor booleano e que
        // serão usadas como argumentos da função desempacotadora
        //
        // Veja como isso é feito no método wrapString
        foreach($this->map_argumenter_functions as $method_name) {
            $lines[] = $sp . "function {$method_name}() { return TRUE; }\n";
        }

        $contents = "<?php\n" . implode("\n", $lines);
        return ($obfuscate == true) ? $this->obfuscateUnpackFunctions($contents) : $contents;
    }

    /**
     * Extrai o conteúdo de um método público de desenpacotamento 'packer???Unpack',
     * existente nesta classe. O conteúdo do método será usado para gerar as funções
     * de desempacotamento.
     * Veja como isso é feito no método getRevertFileContents
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
    // Os métodos abaixo são responsáveis pelo empacotamento do código
    //
    // PACK: Os métodos com sufixo 'Pack', por exemplo 'packerOnePack',
    // são responsáveis pelo empacotamento do código.
    //
    // UNPACK: O conteúdo dos métodos com sufixo 'Unpack', por exemplo 'packerOneUnpack',
    // são extraídos para gerar as funções responsáveis pelo
    // desempacotamento do código ofuscado.
    //

    /**
     * Empacota o codigo especificado.
     *
     * @param  string $data
     * @return string
     * @todo Mudar a sigla 'Sg' para que seja gerada dinamica e randomicamente
     */
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

    /**
     * Empacota o codigo especificado.
     *
     * @param  string $data
     * @return string
     * @todo Mudar a sigla 'Sg' para que seja gerada dinamica e randomicamente
     */
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

    /**
     * Empacota o codigo especificado.
     *
     * @param  string $data
     * @return string
     * @todo Mudar a sigla 'Sg' para que seja gerada dinamica e randomicamente
     */
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
