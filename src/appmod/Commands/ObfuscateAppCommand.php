<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Commands;

use Illuminate\Console\Command;

class ObfuscateAppCommand extends BaseCommand
{
    /**
     * Assinatuta do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'obfuscate:app';

    /**
     * Descrição do comando no terminal
     *
     * @var string
     */
    protected $description = 'Faz um backup e ofusca um projeto contido no diretório app do laravel';

    /**
     * Devolve o caminho completo até o diretório que será ofuscado.
     *
     * @abstract
     * @return string
     */
    protected function getPlainPath() : string
    {
        return base_path('app');
    }

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @abstract
     * @return string
     */
    protected function getBackupPath() : string
    {
        return base_path('app_backup');
    }

    /**
     * Devolve o caminho completo até o diretório que será usado para backup.
     *
     * @abstract
     * @return string
     */
    protected function getObfuscatedPath() : string
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
    protected function getComposerJsonFile() : string
    {
        return base_path('composer.json');
    }

    /**
     * Executa o algoritmo do comando de terminal.
     *
     * @return mixed
     */
    public function handle()
    {
        $path_plain      = $this->getPlainPath();
        $path_obfuscated = $this->getObfuscatedPath();
        $path_backup     = $this->getBackupPath();

        $ob = $this->getObfuscator();
        $ob->setUnpackFile('App.php');

        if ($ob->obfuscateDirectory($path_plain) == false) {
            foreach ($ob->getErrorMessages() as $message) {
                $this->error($message);
            }
            return false;
        }

        if ($ob->makeDir($path_obfuscated) == false) {
            foreach ($ob->getErrorMessages() as $message) {
                $this->error($message);
            }
            return false;
        }

        if($ob->saveDirectory($path_obfuscated) == false) {
            foreach ($ob->getErrorMessages() as $message) {
                $this->error($message);
            }
            return false;
        }

        // $autoloader_file = $this->getAutoloaderFile();
        // $app_file = $this->getAppFile();
        // dd($autoloader_file, $app_file);

        if ($this->makeBackup() == false) {
            return false;
        }

        if ($this->setupComposer() == false) {
            return false;
        }

        $this->info("A aplicação foi ofuscada com sucesso");
        $this->info("Um backup da aplicação original se encontra em {$path_backup}");
        $this->info("Execute \"composer dump-autoload\" para atualizar o carregador.");
    }

}
