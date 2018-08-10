<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Services;

use LightObfuscator\ObfuscateDirectory;

abstract class BaseObfuscator
{
    /**
     * Atributo que armazena a instancia da biblioteca de ofuscação.
     *
     * @var ArtisanObfuscator\Services\BaseObfuscator
     */
    protected $obfuscator;

    /**
     * Argumentos setados para a configuração da rotina de ofuscação.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Flag que define se a operação deve ser executada ou não.
     *
     * @var bool
     */
    protected $stop = false;

    /**
     * Cria uma nova instância do comando.
     *
     * @return BaseObfuscator
     */
    public function __construct()
    {
        $this->obfuscator = new ObfuscateDirectory;
    }

    /**
     * Seta os argumentos usados para a configuração da rotina.
     *
     * @param array $args
     * @return BaseObfuscator
     */
    public function setArguments(array $args)
    {
        $this->arguments = $args;
        return $this;
    }

    /**
     * Devolve a lista de argumentos disponíveis.
     *
     * @return array
     */
    protected function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Devolve o argumento especificado.
     *
     * @param string $name
     * @return mixed
     */
    protected function getArgument($name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Marca a execução/interrupção da rotina de ofuscação.
     *
     * @param bool $flag
     * @return BaseObfuscator
     */
    protected function stop($flag = true)
    {
        $this->stop = $flag;
        return $this;
    }

    /**
     * Verifica se a execução da rotina deve continuar.
     *
     * @return bool
     */
    protected function isStopped()
    {
        return ($this->stop == true);
    }

    /**
     * Devolve o caminho completo até o diretório que será ofuscado.
     *
     * @return string
     */
    abstract public function getPlainPath() : string;

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @return string
     */
    abstract public function getBackupPath() : string;

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @return string
     */
    abstract public function getObfuscatedPath() : string;

    /**
     * Devolve o caminho completo até o arquivo 'composer.json', usado para
     * disponibbilizar os arquivos da aplicação Laravel.
     *
     * @return string
     */
    abstract public function getComposerJsonFile() : string;

    /**
     * Devolve a instância do ofuscador usado para criptografar os arquivos.
     *
     * @return LightObfuscator\ObfuscateDirectory
     */
    public function getObfuscator()
    {
        return $this->obfuscator;
    }

    public function isJsonFile($filename)
    {
        // Arquivo existe?
        if (is_file($filename) == false) {
            return false;
        }

        // É um json válido?
        $contents = file_get_contents($filename);
        try {
            $data = json_decode($contents);
        } catch(\ErrorException $e) { }

        // Se não ocorrer erro na decodificação, é um json :)
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Devolve o caminho completo até o carregador da aplicação ofuscada.
     *
     * @return string
     */
    protected function getAppFile()
    {
        $app_path = $this->getPlainPath();
        $root_path = trim(dirname($this->getPlainPath()), '/');
        return str_replace($root_path, '', $app_path) . DIRECTORY_SEPARATOR . 'App.php';
    }

    /**
     * Devolve o caminho completo até o autoloader a ser incluido no composer.
     *
     * @return string
     */
    protected function getAutoloaderFile()
    {
        $app_path = $this->getPlainPath();
        $root_path = dirname($this->getPlainPath());
        $autoloader_file = str_replace($root_path, '', $app_path) . DIRECTORY_SEPARATOR . 'autoloader.php';
        return trim($autoloader_file, "/");
    }

    /**
     * Prepara os caminhos de carregamento para os arquivos do autoloader
     *
     * @return bool
     */
    private function prepareAutoloaderFile()
    {
        $plain_name      = pathinfo($this->getPlainPath(), PATHINFO_BASENAME);
        $obfuscated_name = pathinfo($this->getObfuscatedPath(), PATHINFO_BASENAME);

        $autoloader = $this->getPlainPath() . DIRECTORY_SEPARATOR . 'autoloader.php';
        $contents = file_get_contents($autoloader);
        $contents = str_replace($obfuscated_name, $plain_name, $contents);
        return (file_put_contents($autoloader, $contents) !== false);
    }

    /**
     * Efetua o backup dos arquivos originais
     * e adiciona os ofuscados no lugar
     *
     * @return bool
     */
    protected function makeBackup()
    {
        $path_plain      = $this->getPlainPath();
        $path_obfuscated = $this->getObfuscatedPath();
        $path_backup     = $this->getBackupPath();

        // Renomeia o diretório com os arquivos originais
        // para um novo diretório de backup
        if (is_dir($path_backup) == true) {
            $this->getObfuscator()->addRuntimeMessage('A previous backup already exists!');
            return false;

        } elseif(rename($path_plain, $path_backup) === false) {
            $this->getObfuscator()->addErrorMessage('Could not back up. Operation canceled!');
            return false;
        }

        // Renomeia o diretório com os arquivos ofuscados
        // para ser o novo diretório em execução
        if(rename($path_obfuscated, $path_plain) === false) {
            $this->getObfuscator()->addErrorMessage('Obfuscated directory could not become the main directory!');
            return false;
        }

        // Renomeia os includes do autoloader
        if ($this->prepareAutoloaderFile() == false) {
            $this->getObfuscator()->addErrorMessage('Unable to prepare autoloader file!');
            return false;
        }

        return true;
    }

    /**
     * Configura o composer para usar os arquivos ofuscados
     * no lugar dos originais.
     *
     * @return bool
     */
    protected function setupComposer()
    {
        $composer_file = $this->getComposerJsonFile();
        $contents = json_decode(file_get_contents($composer_file), true);
        if (!isset($contents['autoload']['files'])) {
            // Cria a seção files
            $contents['autoload']['files'] = [];
        }

        $autoloader_file = $this->getAutoloaderFile();
        if (array_search($autoloader_file, $contents['autoload']['files']) === false) {
            // Adiciona o arquivo com prioridade
            $contents['autoload']['files'] = array_merge(
                [$autoloader_file],
                $contents['autoload']['files']
            );
        }

        $json_contents = str_replace("\\/", "/", json_encode($contents, JSON_PRETTY_PRINT));
        if (file_put_contents($composer_file, $json_contents) === false) {
            $this->getObfuscator()->addErrorMessage("Could not update composer.json file");
            return false;
        }

        $this->getObfuscator()->addErrorMessage("O arquivo composer.json foi configurado com sucesso.");

        return true;
    }

    /**
     * Executa a rotina de ofuscação.
     *
     * @return mixed
     */
    public function execute()
    {
        $path_plain      = $this->getPlainPath();
        $path_obfuscated = $this->getObfuscatedPath();
        $path_backup     = $this->getBackupPath();

        if ($this->isStopped() == true) {
            return false;
        }

        $ob = $this->getObfuscator();
        $ob->setUnpackFile('App.php');

        if ($ob->obfuscateDirectory($path_plain) == false) {
            return false;
        }

        if ($ob->makeDir($path_obfuscated) == false) {
            return false;
        }

        if($ob->saveDirectory($path_obfuscated) == false) {
            return false;
        }

        if ($this->makeBackup() == false) {
            return false;
        }

        if ($this->setupComposer() == false) {
            return false;
        }

        return true;
    }
}
