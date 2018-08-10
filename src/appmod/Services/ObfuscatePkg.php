<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Services;

class ObfuscatePkg extends BaseObfuscator
{
    /**
     * Devolve o caminho completo até o diretório que será ofuscado.
     *
     * @abstract
     * @return string
     */
    public function getPlainPath() : string
    {
        // Diretório especificado
        $path = $this->getArgument('package_path');
        $pkg_root = ($path[0] == "/") ? rtrim($path, "/") : rtrim(base_path($path), "/");

        // Contém o diretório src?
        $src_path = $pkg_root . DIRECTORY_SEPARATOR . "src";
        if (is_dir($src_path) == true) {
            return $src_path;
        }

        return $pkg_root;
    }

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @abstract
     * @return string
     */
    public function getBackupPath() : string
    {
        return $this->getPlainPath() . "_backup";
    }

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @abstract
     * @return string
     */
    public function getObfuscatedPath() : string
    {
        return $this->getPlainPath() . '_obfuscated';
    }

    /**
     * Devolve o caminho completo até o arquivo 'composer.json', usado para
     * disponibbilizar os arquivos da aplicação Laravel.
     *
     * @abstract
     * @return string
     */
    public function getComposerJsonFile() : string
    {
        $file = $this->getArgument('composer_file');
        if ($file == null) {
            // Tenta adivinhar onde o arquivo se encontra
            $file = dirname($this->getPlainPath()) . DIRECTORY_SEPARATOR . "composer.json";
        }

        $json_file = ($file[0] == "/") ? $file : base_path($file);
        if ($this->isJsonFile($json_file) === false) {
            // Notifica o sistema para não efetuar a ofuscação
            $this->getObfuscator()->addErrorMessage("Json file is invalid");
            $this->stop();
        }

        return $json_file;
    }

    public function execute()
    {
        parent::execute()

        // TODO
        // Remover ofuscação do Service Provider e
        // adicionar a chamada do autoloader nele
    }
}
