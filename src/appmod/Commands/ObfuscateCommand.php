<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace Obfuscator\Commands;

use Illuminate\Console\Command;

class ObfuscateCommand extends BaseCommand
{
    /**
     * Assinatuta do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'obfuscate
                            {plain_dir : nome do diretório com os arquivos que serão ofuscados}
                            {backup_dir : Nome do diretório com os arquivos originais}
                            {composer_file? : Caminho completo até o arquivo composer.json da aplicação}
                            ';

    /**
     * Descrição do comando no terminal
     *
     * @var string
     */
    protected $description = 'Ofusca o código PHP contido em uma aplicação Laravel
                              para ser distribuido sem possibilitar sua leitura';

    /**
     * Executa o algoritmo do comando de terminal.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setFilesPath(base_path($this->argument('plain_dir')));
        
        // Ofusca o diretório especificado
        $path_files = $this->getFilesPath();
        $path_obfuscated = $this->getObfuscatedPath();
        if ($this->obfuscateDirectory($path_files, $path_obfuscated) == false) {
            $this->error("Erros ocorreram ao tentar ofuscar o diretório {$path_files}");
        }

        if ($this->setupFiles() == false) {
            $this->error("Não foi possível configurar os arquivos após a ofuscação");
            return false;
        }

        $this->info("Todo o código foi ofuscado com sucesso");
        $path_backup = $this_>getBackupPath();
        $this->info("Um backup dos arquivos originais foi criado em {$path_backup}");
    }

}
