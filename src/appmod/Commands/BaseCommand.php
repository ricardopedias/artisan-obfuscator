<?php

namespace Obfuscator\Commands;

use Illuminate\Console\Command;
use Obfuscator\Libs\PhpObfuscator;

abstract class BaseCommand extends Command
{

    private $links = [];

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
     * @param  PhpObfuscator  $obfuscator
     * @return void
     */
    public function __construct(PhpObfuscator $obfuscator)
    {
        parent::__construct();

        $this->obfuscator = $obfuscator;
    }

    abstract protected function getBasePath();

    protected function getObfuscator()
    {
        return $this->obfuscator;
    }

    /**
     * Resolve a localização real do diretório especificado.
     *
     * @param  string $path
     * @return string
     */
    protected function parsePath($path)
    {
        if ($path == '.') {
            $path = $this->getBasePath();
        }
        elseif ($path[0] != '/') {
            $path = trim($path, $this->ds);
            $path = $this->getBasePath() . $this->ds . $path;
        }

        return $path;
    }

    /**
     * Cria o diretório especificado no sistema de arquivos.
     *
     * @param  string  $destiny
     * @param  boolean $force
     * @return boolean
     */
    protected function makeDestinyDir($destiny, $force = false)
    {
        if (is_dir($destiny) && is_writable($destiny) == false) {
            // Diretório já existe, mas não é gravável
            $this->error("O diretório {$destiny} já existe mas você não tem permissão para escrever nele");
            return false;

        } elseif (is_dir($destiny) == true) {
            // O diretório já existe
            $this->info("O diretório {$destiny} já existe");
            return true;
        }

        if (@mkdir($destiny, 0755, $force) == true) {
            // Tenta criar o diretório
            $this->info("O diretório {$destiny} foi criado com sucesso");
            return true;

        } else {
            $this->info("Não foi possível criar o diretório {$destiny}");
            return false;
        }

        return false;
    }

    /**
     * Cria o diretório de destino e grava nele os arquivos ofuscados.
     *
     * @param  string $origin
     * @param  string $destiny
     * @return boolean
     */
    protected function obfuscateDirectory($origin, $destiny)
    {
        $this->line("-------------------------------------------------------");
        $this->line("Ofuscando o diretório '{$origin}' para '{$destiny}'");

        if ($this->makeDestinyDir($destiny) == false) {
            return false;
        }

        if (is_readable($origin) == false) {
            $this->error("Você não tem permissão para ler o diretório {$origin}");
            return false;
        }

        $list = scandir($origin);

        foreach ($list as $item) {

            if (in_array($item, ['.', '..']) ) {
                continue;
            }

            if (is_link($origin . $this->ds . $item)) {

                // LINKS
                // Os links será recriados no final

                $this->links[$origin . $this->ds . $item] = readlink($origin . $this->ds . $item);
                $this->info("-> Link encontrado: " . $origin . $this->ds . $item
                    ." > " . $this->links[$origin . $this->ds . $item]
                    );
                continue;

            } elseif (is_file($origin . $this->ds . $item) == true) {

                // ARQUIVOS

                if ($this->isPhpFile($origin . $this->ds . $item) == true) {

                    if ($this->obfuscateFile($origin . $this->ds . $item, $destiny . $this->ds . $item) == true) {
                        $this->info("- Arquivo " . $origin . $this->ds . $item . " ofuscado");
                    } else {
                        $this->error("x Arquivo " . $origin . $this->ds . $item . " não pôde ser ofuscado");
                    }

                } else {

                    // Arquivos não-PHP
                    // Simplesmente copiados

                    if (copy($origin . $this->ds . $item, $destiny . $this->ds . $item) == true) {
                        $this->info("- Arquivo " . $origin . $this->ds . $item . " mantido");
                    } else {
                        $this->error("x Arquivo " . $origin . $this->ds . $item . " não pôde ser copiado");
                    }
                }

            } elseif (is_dir($origin . $this->ds . $item) ) {

                // DIRETÓRIOS
                $this->obfuscateDirectory($origin . $this->ds . $item, $destiny . $this->ds . $item);

            }

        }

        return true;
    }

    /**
     * Devolve a lista de arquivos php no diretório especificado.
     *
     * @param  string $destiny
     * @return array
     */
    protected function indexDirectory($destiny)
    {
        $index = [];

        $this->line("-------------------------------------------------------");
        $this->line("Indexando o diretório '{$destiny}'");

        $list = scandir($destiny);

        foreach ($list as $item) {

            if (in_array($item, ['.', '..']) ) {
                continue;
            }

            if (is_link($destiny . $this->ds . $item)) {

                // LINKS
                // são ignorados neste ponto
                continue;

            } elseif (is_file($destiny . $this->ds . $item) == true) {

                // ARQUIVOS

                if ($this->isPhpFile($destiny . $this->ds . $item) == true) {

                    $index[] = $destiny . $this->ds . $item;
                    $this->info("- Arquivo " . $destiny . $this->ds . $item . " indexado");

                } else {

                    // Arquivos não-PHP
                    // são ignorados neste ponto
                    continue;
                }

            } elseif (is_dir($destiny . $this->ds . $item) ) {

                // DIRETÓRIOS
                $list = $this->indexDirectory($destiny . $this->ds . $item);
                foreach ($list as $file) {
                    $index[] = $file;
                }
            }
        }

        return $index;
    }

    /**
     * Gera um arquivo com a lista de requires.
     *
     * @param  array $list_files
     * @param  string $destiny
     * @return bool
     */
    protected function generateAutoloader($list_files, $destiny)
    {
        $autoloader_file = $destiny . $this->ds . 'autoloader.php';

        // Remove o autoloader da lista
        if (($key = array_search($autoloader_file, $list_files)) !== false) {
            unset($list_files[$key]);
        }

        $contents = "<?php \n\n";

        $contents .= "\$includes = array(\n";
        $contents .= "    '" . implode("',\n    '", $list_files) . "'\n";
        $contents .= ");\n\n";

        $contents .= "foreach(\$includes as \$file) {\n";
        $contents .= "    require_once(\$file);\n";
        $contents .= "}\n\n";

        if (file_put_contents($autoloader_file, $contents) !== false) {
            return true;
        }

        return false;
    }

    /**
     * Verifica se o arquivo especificado é um arquivo PHP.
     *
     * @param  string $string
     * @return boolean
     */
    protected function isPhpFile($string)
    {
        $extension = pathinfo($string, PATHINFO_EXTENSION);
        return (strtolower($extension) == 'php');
    }

    protected function obfuscateFile($origin, $destiny)
    {
        $this->line("Ofuscando o arquivo '{$origin}' para '{$destiny}'");

        $ob = (new PhpObfuscator)->obfuscateFile($origin);
        if($ob == false) {
            $this->error("Ocorreu um erro ao tentar ofuscar o arquivo {$origin}");
            return false;
        }

        return $ob->save($destiny);
    }

}
