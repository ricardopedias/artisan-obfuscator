<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Commands;

use Illuminate\Console\Command;
use LightObfuscator\ObfuscateDirectory;

abstract class BaseCommand extends Command
{
    /**
     * Atributo que armazena a instancia da biblioteca de ofuscação.
     *
     * @var LightObfuscator\ObfuscateDirectory
     */
    protected $obfuscator;

    /**
     * Cria uma nova instância do comando.
     *
     * @return ObfuscateAppCommand
     */
    public function __construct()
    {
        $this->obfuscator = new ObfuscateDirectory;
        parent::__construct();
    }

    /**
     * Devolve a instância do ofuscador usado para criptografar os arquivos.
     *
     * @return LightObfuscator\ObfuscateDirectory
     */
    protected function getObfuscator()
    {
        return $this->obfuscator;
    }

    /**
     * Devolve o caminho completo até o diretório que será ofuscado.
     *
     * @return string
     */
    abstract protected function getPlainPath() : string;

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @return string
     */
    abstract protected function getBackupPath() : string;

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @return string
     */
    abstract protected function getObfuscatedPath() : string;

    /**
     * Devolve o caminho completo até o arquivo 'composer.json', usado para
     * disponibbilizar os arquivos da aplicação Laravel.
     *
     * @return string
     */
    abstract protected function getComposerJsonFile() : string;

    protected function getAppFile()
    {
        $app_path = $this->getPlainPath();
        $root_path = trim(dirname($this->getPlainPath()), '/');
        return str_replace($root_path, '', $app_path) . DIRECTORY_SEPARATOR . 'App.php';
    }

    protected function getAutoloaderFile()
    {
        $app_path = $this->getPlainPath();
        $root_path = dirname($this->getPlainPath());
        $autoloader_file = str_replace($root_path, '', $app_path) . DIRECTORY_SEPARATOR . 'autoloader.php';
        return trim($autoloader_file, "/");
    }

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
     * Efetua o backup dos arquivos originais e move os ofuscados
     * para execução.
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
            $this->warning('A previous backup was found!');
            return false;

        } elseif(rename($path_plain, $path_backup) === false) {
            $this->error('Could not back up. Operation canceled!');
            return false;
        }

        // Renomeia o diretório com os arquivos ofuscados
        // para ser o novo diretório em execução
        if(rename($path_obfuscated, $path_plain) === false) {
            $this->error('Obfuscated directory could not become the main directory!');
            return false;
        }

        // Renomeia os includes do autoloader
        if ($this->prepareAutoloaderFile() == false) {
            $this->error('Unable to prepare autoloader file!');
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

        if (file_put_contents($composer_file, json_encode($contents, JSON_PRETTY_PRINT)) === false) {
            $this->error("Could not update composer.json file");
            return false;
        }

        $this->info("O arquivo composer.json foi configurado com sucesso.");

        return true;
    }

}
