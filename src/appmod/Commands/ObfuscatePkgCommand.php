<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace ArtisanObfuscator\Commands;

use Illuminate\Console\Command;
use ArtisanObfuscator\Services\ObfuscatePkg;

class ObfuscatePkgCommand extends Command
{
    /**
     * Assinatuta do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'obfuscate:pkg
        {package_path : O caminho completo até o diretório src do pacote a ser ofuscado}
        {composer_file? : O caminho completo até o arquivo composer.json do pacote}';

    /**
     * Descrição do comando no terminal
     *
     * @var string
     */
    protected $description = 'Faz um backup e ofusca um pacote do laravel';

    /**
     * Executa o algoritmo do comando de terminal.
     *
     * @return mixed
     */
    public function handle()
    {
        $command = new ObfuscatePkg;
        $command->setArguments($this->arguments());

        if ($command->execute() === true) {

            echo shell_exec("composer dump-autoload");

            $this->info("A aplicação foi ofuscada com sucesso");
            $path_backup = $command->getBackupPath();
            $this->info("Um backup da aplicação original se encontra em {$path_backup}");

            return true;
        }

        $errors = $command->getObfuscator()->getErrorMessages();
        foreach ($errors as $message) {
            $this->error($message);
        }

        $runtime = $command->getObfuscator()->getRuntimeMessages();
        foreach ($runtime as $message) {
            $this->info($message);
        }

        return false;

    }

}
