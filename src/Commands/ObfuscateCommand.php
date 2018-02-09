<?php

namespace Obfuscator\Commands;

use Illuminate\Console\Command;
use Obfuscator\Libs\PhpObfuscator;

class ObfuscateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obfuscate 
                            {origin : O arquivo ou diretório a ser ofuscado} 
                            {destiny : O arquivo ou diretório que receberá os arquivos ofuscados}
                            {--p|path : Força a criação do diretório de destino caso não exista}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ofusca o código PHP para ser distribuido sem possibilitar sua leitura';

    /**
     * A biblioteca de ofuscação
     *
     * @var PhpObfuscator
     */
    protected $obfuscator;

    protected $ds = DIRECTORY_SEPARATOR;

    /**
     * Create a new command instance.
     *
     * @param  DripEmailer  $drip
     * @return void
     */
    public function __construct(PhpObfuscator $obfuscator)
    {
        parent::__construct();

        $this->obfuscator = $obfuscator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $origin  = $this->parsePath($this->argument('origin'));
        $destiny = $this->parsePath($this->argument('destiny'));

        $makedir = $this->option('path');

        // Diretório Origem
        if (is_dir($origin) == true ) {

            if ($this->makeDestinyDir($destiny, $makedir) == true) {
                return $this->obfuscateDirectory($origin, $destiny . $this->ds);
            }
        }
        // Arquivo Origem
        elseif(is_file($origin) == true){

            $php_destiny    = $this->isPhpFile($destiny);
            $create_destiny = ($php_destiny == true) ? dirname($destiny) : $destiny;
            $save_destiny   = ($php_destiny == true) ? $destiny : $destiny . $this->ds . basename($origin);

            if ($this->makeDestinyDir($create_destiny, $makedir) == true) {
                return $this->obfuscateFile($origin, $save_destiny);
            }
        } 
        
        return false;
    }

    private function parsePath($path)
    {
        if ($path[0] != '/') {
            $path = trim($path, $this->ds);
            $path = base_path($path);
        }
        
        return $path;
    }

    private function makeDestinyDir($destiny, $force = false)
    {
        // Diretório já existe, mas não é gravável
        if (is_dir($destiny) && is_writable($destiny) == false) {

            $this->error("Você não tem permissão para escrever no diretório '{$destiny}'");
            return false;
        }
        elseif (is_dir($destiny) == true) {
            return true;
        }

        // Diretório inexistente e criação não foi requerida
        if ($force == false) {

            $this->error("O diretório de destino '{$destiny}' não existe");
            return false;  
        }
        // Tenta criar o diretório
        elseif ($force == true && @mkdir($destiny, 0755, true) == true) {

            $this->info("Criado o diretório de destino '{$destiny}{$this->ds}'");
            return true;
        }

        $this->error("Um erro impediu que o diretório '{$destiny}{$this->ds}' fosse criado");
        return false;   
    }

    private function isPhpFile($string)
    {
        $extension = pathinfo($string, PATHINFO_EXTENSION);   
        return (strtolower($extension) == 'php');
    }

    private function obfuscateFile($origin, $destiny)
    {
        $extension = pathinfo($origin, PATHINFO_EXTENSION);

        if (strtolower($extension) !== 'php') {

            $this->error("Apenas arquivos PHP podem ser ofuscados!");
            return false;
        }

        if (is_readable($origin) == false) {

            $this->error("Você não tem permissão para ler o arquivo especificado!");
            return false;
        }

        $this->line("Ofuscando o arquivo '{$origin}' para '{$destiny}'");

        // ...
    }

    private function obfuscateDirectory($origin, $destiny)
    {
        if (is_readable($origin) == false) {

            $this->error("Você não tem permissão para ler o diretório especificado!");
            return false;
        }

        $this->line("Ofuscando o diretório '{$origin}' para '{$destiny}'");

        // ...

    }
}