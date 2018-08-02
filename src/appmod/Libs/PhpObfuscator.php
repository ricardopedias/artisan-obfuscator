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

    private $errors = [];

    private $function_name = null;

    private $key_name      = null;

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
     * Devolve o nome de uma função para descompressão de código.
     * As funções se encontram no arquivo RevertObfuscation e servem
     * para converter/desconverter uma string com código php.
     *
     * @return string
     */
    private function getDynamicFunctionName()
    {
        if ($this->function_name != null) {
            // Lazyload paa devolver apenas um nome por instância
            return $this->function_name;
        }

        $functions = [
            'cfForgetShow',
            'cryptOf',
            'unsetZeros',
            'deflatingNow',
            'zeroizeCipher',
            'iqutZ',
            'sagaPlus'
        ];

        $this->function_name = $functions[array_rand($functions)];
        return $this->function_name;
    }

    /**
     * Devolve o nome de uma função para setagem de parametros
     * nas funções aleatórias do método getDynamicFunctionName().
     * As funções se encontram no arquivo RevertObfuscation e servem
     * para dificultar um pouco o entendimento do hacker.
     *
     * @return string
     */
    private function getDynamicKeyName()
    {
        if ($this->key_name != null) {
            // Lazyload paa devolver apenas um nome por instância
            return $this->key_name;
        }

        $keys = [
            'decompressMD5',
            'unsetLogger',
            'loopNested',
            'vorticeData',
            'cipherBinary'
        ];

        $this->key_name = $keys[array_rand($keys)];
        return $this->key_name;
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

        $function_call = $this->getDynamicFunctionName();
        $keyname_call  = $this->getDynamicKeyName();

        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'RevertObfuscation.stub');

        $string = '';
        $string.= $this->toASCII($prefix. "eval({$function_call}("); // esconde a função descompactar
        $string.= "'" . $function_call($code) . "'";                 // executa a função compactar
        $string.= $this->toASCII(",{$keyname_call}())); ");

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









    private function encode__($code, $levels = 1, $host = NULL)
    {
        require_once('RevertObfuscation.php');

        $prefix = '';
        if ($this->show_decode_errors == FALSE) {
            $prefix = '@';
        }

        // Máximo de 3 levels
        $levels = $levels>3 ? 3 : $levels;

        $functions_called = array();
        for($x=0; $x < $levels; $x++) {

            $function_name = $this->getDynamicFunctionName();
            $functions_called[] = $function_name;
            $code = $function_name($code);
        }

        if ($levels == 1) {

            $function_name = $functions_called[0];
            $key_name = $this->getDynamicKeyName();

            $str = '';
            $str.= $this->toASCII($prefix. "eval({$function_name}(");
            $str.= "'" . $code . "'";
            $str.= $this->toASCII(",{$key_name}())); ");

        }
        elseif ($levels == 2) {

            $function_one = $functions_called[1];
            $key_name = $this->getDynamicKeyName();

            $oneStr = "";
            $oneStr.= $this->toASCII("{$function_one}(");
            $oneStr.= "'" . $code . "'";
            $oneStr.= $this->toASCII(",{$key_name}()) ");

            $function_two = $functions_called[0];
            $key_name = $this->getDynamicKeyName();

            $str = "";
            $str.= $this->toASCII($prefix. "eval({$function_two}(");
            $str.= $oneStr;
            $str.= $this->toASCII(",{$key_name}())); ");

        }

        elseif ($levels == 3) {

            $function_one = $functions_called[2];
            $key_name = $this->getDynamicKeyName();

            $oneStr = "";
            $oneStr.= $this->toASCII("{$function_one}(");
            $oneStr.= "'" . $code . "'";
            $oneStr.= $this->toASCII(",{$key_name}()) ");

            $function_two = $functions_called[1];
            $key_name = $this->getDynamicKeyName();

            $twoStr = "";
            $twoStr.= $this->toASCII("{$function_two}(");
            $twoStr.= $oneStr;
            $twoStr.= $this->toASCII(",{$key_name}()) ");

            $functionThree = $functions_called[0];
            $key_name = $this->getDynamicKeyName();

            $str = "";
            $str.= $this->toASCII($prefix. "eval({$functionThree}(");
            $str.= $twoStr;
            $str.= $this->toASCII(",{$key_name}())); ");

        }

        /*
        $host = $this->getHost();
        $str = str2ASCII("if(obfhost()==\"{$host}\"){ @eval(obfinflate(\" ");
        $str.= $encoded;
        $str.= str2ASCII(" \",obfcode())); }");
        */

        return $str;
    }









    public function encodesssssssssss($filename)
    {
        global $obfuscate;

        // Filtros a excluir
        $obfuscateExcludes = array(
            'vendor',
            'Plexi/autoloader.php',
            'Layout/Engines/Library',
            'Assets/Compilers/Library',
            'Admin/Assets',
            'Ads/Assets',
            'Banners/Assets',
            'Install/Assets',
            'Layouts/Assets',
            'Login/Assets',
            'Pages/Assets',
            'Place/Assets',
            'Products/Assets',
            'Sites/Assets',
            'Exception'
        );

        $allowable = TRUE;
        foreach ($obfuscateExcludes as $strip) {
            if (strpos($filename, $strip) != FALSE) {
                $allowable = FALSE;
                $console->showInfo("Filtro de exclusão encontrado: \"" . $strip . "\"!");
                break;
            }
        }

        if ($allowable == FALSE) {
            $console->showInfo("O arquivo não será ofuscado!");
            return FALSE;
        }


        // É uma aplicação?
        if (strpos($filename, 'App')) {
            $space = 'App';
        }
        elseif (strpos($filename, 'Plexi')) {
            $space = 'Plexi';
        }
        else {
            $console->showInfo("Apenas arquivos Plexi e App podem ser ofuscados!");
            $console->showInfo("Arquivo " . $filename . " ignorado!");
            return FALSE;
        }

        $onlyPath = str_replace('.php', '', $filename);

        $parts = explode($space, $onlyPath);
        if (isset($parts[1])) {

            //$fileContent = file_get_contents($filename);
            $fileContent = php_strip_whitespace($filename);


            // Ignora os diretórios
            $ignore = array(
                'Assets'
                );
            $currentDir = dirname($filename);
            foreach ($ignore as $dir) {
                if(strpos($currentDir, $dir)) {
                    $console->showInfo("Diretório " . $currentDir . " ignorado!");
                    return FALSE;
                }
            }

            // Determina se é uma classe
            $classNameSpace = str_replace('/', "\\", $space.$parts[1]);
            $className = basename($onlyPath);
            if (strpos($fileContent, 'class '.$className) == FALSE) {
                $console->showInfo("Apenas classes podem ser ofuscadas!");
                $console->showInfo("Arquivo " . $filename . " ignorado!");
                return FALSE;
            }

            // Muda o modo de execução no arquivo Client.php para PRODUÇÃO
            if($className == 'Client') {
                $fileContent = str_replace(
                    '$this->setEnvironment(Kernel::ENV_DEVELOPMENT);',
                    '$this->setEnvironment(Kernel::ENV_PRODUCTION);',
                    $fileContent);
            }

            $codeObfuscator = new CodeObfuscator;
            $encodeLevel = ($obfuscate != NULL && intval($obfuscate) > 0)
                        ? $obfuscate : 1;
            $code = $codeObfuscator->prepareCode($fileContent);


            // FASE 1 -----------------

            // No arquivo ArrayHash será inserida uma função camuflada
            // chamada php_zencoding(), que será usada para descompactar
            // o código ofuscado das próximas fases.
            // Nível de ofuscação: BAIXO
            if (basename($filename) == 'ArrayHash.php') {

                $encoded = "";

                // Esconde o ArrayHash
                $encoded.= $codeObfuscator->toASCII("eval(base64_decode('");
                $encoded.= base64_encode($code);
                $encoded.= $codeObfuscator->toASCII("'));");

                // Esconde a função
                $phpZencoding = "function php_zencoding(\$data){
                    \$partOne = mb_substr(\$data, 0, 10, 'utf-8');
                    \$partTwo = mb_substr(\$data, 12, NULL, 'utf-8');
                    return base64_decode(\$partOne . \$partTwo);
                }";

                $encoded.= $codeObfuscator->toASCII("eval(base64_decode('");
                $encoded.= base64_encode($phpZencoding);
                $encoded.= $codeObfuscator->toASCII("'));");

                $encodedFileContents = "<?php\n eval(\"{$encoded}\");";
            }

            // FASE 2 -----------------

            // No arquivo Object será inserida uma chamada de inclusão para
            // o arquivo \Plexi\Db\Connection.php, onde se encontram as outras
            // funções ocultas para reverter a ofuscação.
            //
            // Nível de ofuscação: MÉDIO
            // Para referter o código, o hacker usará ferramentas de automação
            // que revelam o código. Como estamos usando uma função personalizada
            // Este processo não poderá prosseguir sem primeiro declarar
            // a função php_zencoding.
            // Outro agravante é que todos os códigos base64 são quebrados na
            // criação dos arquivos e reestruturados na execução, impossibilitando
            // as ferramentas de validar o código.
            //
            elseif (basename($filename) == 'Object.php') {

                $insertion = "namespace Plexi\Common; require_once dirname(dirname(__FILE__)) "
                    . ". DIRECTORY_SEPARATOR . 'Db' "
                    . ". DIRECTORY_SEPARATOR . 'Connection.php'; ";

                $code = str_replace('namespace Plexi\Common;', $insertion, $code);

                $encoded = "";
                $encoded.= $codeObfuscator->toASCII("eval(php_zencoding('");
                $encoded.= baseOne($code);
                $encoded.= $codeObfuscator->toASCII("'));");
                $encodedFileContents = "<?php\n eval(\"{$encoded}\");";
            }

            // FASE 3 -----------------

            // No arquivo \Plexi\Db\Connection.php, ocultamos as funções
            // responsáveis pela execução dos códigos ofuscados.
            //
            // Nível de ofuscação: MÉDIO
            // Mesmo caso da faze anterior
            //
            elseif (basename($filename) == 'Connection.php') {

                $encoded = $codeObfuscator->encodeString($code, $encodeLevel);

                $functionsFile = dirname(dirname(__FILE__))
                    . DIRECTORY_SEPARATOR . 'Libs'
                    . DIRECTORY_SEPARATOR . 'RevertObfuscation.php';
                $functionsFileContent = file_get_contents($functionsFile);
                if ($functionsFileContent != FALSE) {

                    $functionsFileContent = $codeObfuscator->prepareCode($functionsFileContent);
                    $stageOne.= $codeObfuscator->toASCII("eval(php_zencoding('");
                    $stageOne.= baseOne($functionsFileContent);
                    $stageOne.= $codeObfuscator->toASCII("'));");

                    $encodedFileContents = "<?php\n eval(\"{$stageOne} {$encoded}\");";
                }
                else {
                    $encodedFileContents = "<?php\n eval(\"{$encoded}\");";
                }
            }

            // FASE 4 -----------------

            // Os arquivos seguintes são todos revertidos com base nas funções
            // ocultas geradas pelo CodeObfuscator, que são randômicas, ou seja,
            // cada arquivo chamará funções diferentes, dificultando imensamente a
            // possibilidade de criar rotinas para automatizar a tarefa de revelar
            // o código ofuscado.
            //
            // Nível de ofuscação: ALTO
            // Mesmo que o programador consiga chegar até aqui, ele terá que
            // analizar arquivo por arquivo, decifrar todas as funções existentes
            // e executá-las na sequencia certa. :)
            else {

                $encoded = $codeObfuscator->encodeString($code, $encodeLevel);
                $encodedFileContents = "<?php\n eval(\"{$encoded}\");";
            }

            $console->showInfo("Arquivo " . $filename . " ofuscado com sucesso!");
            return $encodedFileContents;
        }
        else {
            return FALSE;
        }
    }
}
