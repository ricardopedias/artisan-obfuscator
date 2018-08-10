<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Services;

class ObfuscateApp extends BaseObfuscator
{
    /**
     * Devolve o caminho completo até o diretório que será ofuscado.
     *
     * @abstract
     * @return string
     */
    public function getPlainPath() : string
    {
        return base_path('app');
    }

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @abstract
     * @return string
     */
    public function getBackupPath() : string
    {
        return base_path('app_backup');
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
        $json_file = base_path('composer.json');

        if ($this->isJsonFile($json_file) === false) {
            // Notifica o sistema para não efetuar a ofuscação
            $this->getObfuscator()->addErrorMessage("Json file is invalid");
            $this->stop();
        }

        return $json_file;
    }
}
