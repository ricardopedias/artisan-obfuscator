<?php
namespace Obfuscator\Commands;

use Illuminate\Console\Command;

class ObfuscateAppCommand extends BaseCommand
{
    private $exclude_dirs = [
        'vendor',
        'node_modules',
    ];

    private $exclude_files = [
        '.env',
    ];



    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate:app
                            {newapp? : Nome do diretório da aplicação ofuscada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ofusca o código PHP contido em uma aplicação Laravel para ser distribuido sem possibilitar sua leitura';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // nome do diretório ofuscado
        $appob_name = $this->argument('newapp') ?? 'appob';

        // Caminho ao diretório ofuscado
        $path_appob  = $this->parsePath($appob_name);

        // Caminho ao diretório original
        $path_app  = $this->parsePath('app');

        if ($this->obfuscateDirectory($path_app, $path_appob) == false) {
            $this->error("Erros ocorreram ao tentar ofuscar o diretório {$path_app}");
        }

        $index = $this->indexDirectory($path_appob);
        if ($this->generateAutoloader($index, $path_appob) == false) {
            $this->error("Não foi possível gerar o autoloader para {$path_appob}");
        }

        $this->info("A aplicação foi ofuscada com sucesso para o diretório {$path_appob}");
    }

}
