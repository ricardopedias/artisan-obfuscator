<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace Obfuscator\Commands;

use Illuminate\Console\Command;
use Obfuscator\Libs\PhpObfuscator;

class BaseCommand extends Command
{
    /**
     * Caminho completo até o diretório contendo
     * os arquivos que devem ser ofuscados.
     *
     * @var string
     */
    protected $files_path = null;

    /**
     * Nome do diretório onde os arquivos
     * ofuscados devem ser salvos.
     *
     * @var string
     */
    protected $obfuscated_dir = 'app_obfuscated';

    /**
     * Nome do diretório onde os arquivos originais
     * devem ser copiados como backup.
     *
     * @var string
     */
    protected $backup_dir = 'app_backup';

    /**
     * Nome do arquivo que que conterá as funções de reversão.
     * Este arquivo sejá gerado pelo processo de ofuscação automaticamente
     * e adicionado no arquivo 'autoloader.php' da aplicação.
     *
     * @var string
     */
    protected $unpack_file = 'App.php';

    /**
     * Armazena as mensagens disparadas pelo processo de ofuscação.
     *
     * @var array
     */
    protected messages = [];

    /**
     * Links encontrados no processo de ofuscação.
     *
     * @var array
     */
    private $links = [];

    /**
     * Atributo que armazena a instancia da biblioteca de ofuscação.
     *
     * @var Obfuscator\Libs\PhpObfuscator
     */
    protected $obfuscator;

    /**
     * Cria uma nova instância do comando.
     *
     * @param  Obfuscator\Libs\PhpObfuscator  $obfuscator
     * @return void
     */
    public function __construct()
    {
        $this->obfuscator = new PhpObfuscator;
        parent::__construct();
    }

    /**
     * Devolve a instância usada para ofuscar os arquivos.
     *
     * @return Obfuscator\Libs\PhpObfuscator
     */
    protected function getObfuscator()
    {
        return $this->obfuscator;
    }

    /**
     * Seta o caminho completo até o diretório contendo
     * os arquivos que devem ser ofuscados.
     *
     * @param string $path
     * @return Obfuscator\Libs\PhpObfuscator
     */
    protected function setFilesPath($path)
    {
        $this->files_path = rtrim($path, "/");

        if(is_file($this->getComposerFile()) === false) {
            throw new \InvalidArgumentException('Arquivo composer.json não foi encontrado');
        }

        return $this;
    }

    /**
     * Devolve a localização do diretório contendo
     * os arquivos que devem ser ofuscados.
     *
     * @return string
     */
    protected function getFilesPath()
    {
        return $this->files_path;
    }

    /**
     * Devolve a localização do diretório onde os arquivos
     * ofuscados devem ser salvos.
     *
     * @return string
     */
    protected function getObfuscatedPath()
    {
        return dirname($this->files_path) . DIRECTORY_SEPARATOR . $this->obfuscated_dir;
    }

    /**
     * Devolve a localização do diretório onde os
     * arquivos originais devem ser copiados como backup.
     *
     * @return string
     */
    protected function getBackupPath()
    {
        return dirname($this->files_path) . DIRECTORY_SEPARATOR . $this->backup_dir;
    }

    /**
     * Devolve a localização do arquivo que conterá as funções de reversão.
     * Este arquivo sejá gerado pelo processo de ofuscação automaticamente
     * e adicionado no arquivo 'autoloader.php' da aplicação.
     *
     * @return string
     */
    protected function getUnpackFile()
    {
        return $this->files_path . DIRECTORY_SEPARATOR . $this->unpack_file;
    }

    /**
     * Devolve a localização do arquivo composer.json.
     *
     * @return string
     */
    protected function getComposerFile()
    {
        return dirname($this->files_path) . DIRECTORY_SEPARATOR . 'composer.json';
    }

    /**
     * Varre o o diretório especificado, ofuscando os arquivos e
     * salvando no diretório de destino.
     *
     * @param  string $path_current
     * @param  string $path_obfuscated
     * @return boolean
     */
    protected function obfuscateDirectory($path_current, $path_obfuscated)
    {
        if (is_readable($path_current) == false) {
            $this->error("Você não tem permissão para ler o diretório {$path_current}");
            return false;
        }

        // Lista os arquivos do diretório
        $list = scandir($path_current);
        if (count($list) == 2) { // '.', '..'
            // se não houverem arquivos, ignora o diretório
            return true;
        }

        $this->line("-------------------------------------------------------");
        $this->line("Ofuscando '{$path_current}'");
        $this->line("-------------------------------------------------------");

        // Cria o mesmo diretório para os arquivos ofuscados
        if ($this->makeDir($path_obfuscated) == false) {
            return false;
        }

        foreach ($list as $item) {

            if (in_array($item, ['.', '..']) ) {
                continue;
            }

            $iterate_current_item    = $path_current . DIRECTORY_SEPARATOR . $item;
            $iterate_obfuscated_item = $path_obfuscated . DIRECTORY_SEPARATOR . $item;

            if (is_link($iterate_current_item)) {

                // LINKS
                // TODO: recriar os links
                $link = readlink($iterate_current_item);
                $this->links[$iterate_current_item] = $link;
                $this->info("-> Link encontrado: " . $iterate_current_item ." > " . $link);
                continue;

            } elseif (is_file($iterate_current_item) == true) {

                if ($this->isPhpFile($iterate_current_item) == true) {

                    // Arquivos PHP são ofuscados
                    if ($this->obfuscateFile($iterate_current_item, $iterate_obfuscated_item) == true) {
                        $this->info("- Arquivo " . $iterate_current_item . " ofuscado");
                    } else {
                        $this->error("x Arquivo " . $iterate_current_item . " não pôde ser ofuscado");
                    }

                } else {

                    // Arquivos não-PHP são simplesmente copiados
                    if (copy($iterate_current_item, $iterate_obfuscated_item) == true) {
                        $this->info("- Arquivo " . $iterate_current_item . " mantido");
                    } else {
                        $this->error("x Arquivo " . $iterate_current_item . " não pôde ser copiado");
                    }
                }

            } elseif (is_dir($iterate_current_item) ) {
                $this->obfuscateDirectory($iterate_current_item, $iterate_obfuscated_item);
            }
        }

        return true;
    }

    /**
     * Cria o diretório especificado no sistema de arquivos.
     *
     * @param  string  $path
     * @param  boolean $force
     * @return boolean
     */
    protected function makeDir($path, $force = false)
    {
        if (is_dir($path) && is_writable($path) == false) {
            // Diretório já existe, mas não é gravável
            throw new \RuntimeException("O diretório {$path} já existe mas você não tem permissão para escrever nele");
            return false;

        } elseif (is_dir($path) == true) {
            // O diretório já existe
            return true;
        }

        if (@mkdir($path, 0755, $force) == true) {
            return true;
        } else {
            throw new \RuntimeException("Não foi possível criar o diretório {$path}");
            return false;
        }

        return false;
    }

    /**
     * Verifica se o nome especificado é para um arquivo PHP.
     *
     * @param  string $filename
     * @return boolean
     */
    protected function isPhpFile($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return (strtolower($extension) == 'php');
    }

    /**
     * Ofusca um arquivo PHP e salva o resultado no destino especificado.
     *
     * @param  string $php_file  Caminho completo até o arquivo PHP
     * @param  string $obfuscated_file Localização do arquivo resultante da ofuscação
     * @return bool
     */
    protected function obfuscateFile($php_file, $obfuscated_file)
    {
        $ob = $this->getObfuscator()->obfuscateFile($php_file);
        if($ob == false) {
            throw new \RuntimeException("Ocorreu um erro ao tentar ofuscar o arquivo {$php_file}");
            return false;
        }

        if($ob->save($obfuscated_file) == false) {
            throw new \RuntimeException("Ocorreu um erro ao tentar salvar o arquivo ofuscado {$obfuscated_file}");
            return false;
        }

        return true;
    }

    /**
     * Configura os arquivos resultantes do processo de ofuscação
     * para que funcionem no mesmo ambiente que os originais funcionavem.
     *
     * @return bool
     */
    public function setupFiles()
    {
        $path_current = $this->getFilesPath();

        // Faz o backup dos arquivos originais
        if ($this->makeBackup() === false); {
            return false;
        }

        // Gera uma lista com todos os arquivos PHP
        // que foram ofuscados
        $index = $this->makeIndex($path_current);

        // Salva o arquivo contendo as funções
        // que desfazem a ofuscação do código
        $revert_file = $this->getUnpackFile();
        if($this->getObfuscator()->saveRevertFile($revert_file) == false) {
            throw new \RuntimeException("Ocorreu um erro ao tentar criar o arquivo de reversão");
            return false;
        }
        $this->info("- Arquivo {$revert_file} criado com sucesso");

        // Adiciona o arquivo de reversão como o
        // primeiro da lista no autoloader
        $index = array_merge([$revert_file], $index);

        // Cria o autoloader com os arquivos ofuscados
        if ($this->generateAutoloader($index) == false) {
            throw new \RuntimeException("Não foi possível gerar o autoloader em {$path_current}");
            return false;
        }

        return $this->setupComposer();
    }

    /**
     * Efetua o backup dos arquivos originais.
     *
     * @param  string $path_current
     * @param  string $path_obfuscated
     * @param  string $path_backup
     * @return bool
     */
    protected function makeBackup()
    {
        $path_current    = $this->getFilesPath();
        $path_obfuscated = $this->getObfuscatedPath();
        $path_backup     = $this->getBackupPath();

        // Renomeia o diretório com os arquivos originais
        // para um novo nome de backup
        if (is_dir($path_backup) == true) {
            $this->warning('Um backup efetuado anteriormente foi encontrado!');
            $this->warning('O novo backup foi abortado para manter a integridade do backup original!');

        } elseif(rename($path_current, $path_backup) === false) {
            throw new \RuntimeException("Não foi possível fazer o backup de {$path_current}");
            return false;
        }

        // Renomeia o diretório com os arquivos ofuscados
        // para ser o novo diretório em execução
        if(rename($path_obfuscated, $path_current) === false) {
            throw new \RuntimeException("Não foi possível tornar {$path_obfuscated} o diretório efetivo");
            return false;
        }

        return true;
    }

    /**
     * Devogera uma lista com os arquivos php
     * contidos no diretório especificado.
     *
     * @param  string $destiny
     * @return array
     */
    protected function makeIndex($path)
    {
        $index = [];

        $this->line("-------------------------------------------------------");
        $this->line("Indexando o diretório '{$path}'");

        $list = scandir($path);
        foreach ($list as $item) {

            if (in_array($item, ['.', '..']) ) {
                continue;
            }

            $iterator_index_item = $path . DIRECTORY_SEPARATOR . $item;

            if (is_link($iterator_index_item)) {
                // LINKS
                // são ignorados neste ponto
                continue;

            } elseif (is_file($iterator_index_item) == true) {

                if ($this->isPhpFile($iterator_index_item) == true) {
                    $index[] = $iterator_index_item;
                    $this->info("- Arquivo " . $iterator_index_item . " indexado");
                } else {
                    // Arquivos não-PHP
                    // são ignorados neste ponto
                    continue;
                }

            } elseif (is_dir($iterator_index_item) ) {
                // DIRETÓRIOS
                $list = $this->makeIndex($iterator_index_item);
                foreach ($list as $file) {
                    $index[] = $file;
                }
            }
        }

        return $index;
    }

    /**
     * Gera um carregador para os arquivos ofuscados.
     *
     * @param  array $list_files
     * @return bool
     */
    protected function generateAutoloader($list_files)
    {
        $file = $this->getFilesPath() . DIRECTORY_SEPARATOR . 'autoloader.php';

        // Se o autoloader existir, remove-o da lista
        // TODO: refatorar para isso não ser necessário jamais!!
        if (($key = array_search($file, $list_files)) !== false) {
            unset($list_files[$key]);
        }

        $contents = "<?php \n\n";

        $contents .= "\$includes = array(\n";
        $contents .= "    '" . implode("',\n    '", $list_files) . "'\n";
        $contents .= ");\n\n";

        $contents .= "foreach(\$includes as \$file) {\n";
        $contents .= "    require_once(\$file);\n";
        $contents .= "}\n\n";

        if (file_put_contents($file, $contents) !== false) {
            $this->info("- Autoloader {$file} criado com sucesso");
            return true;
        }

        return false;
    }

    /**
     * Configura o composer para usar os arquivos ofuscados
     * no lugar dos originais.
     *
     * @return bool
     */
    protected function setupComposer()
    {
        // Atualiza o arquivo composer.json
        $composer_file = $this->getComposerFile();
        $contents = json_decode(file_get_contents($composer_file), true);
        if (!isset($contents['autoload']['files'])) {
            // Cria a seção files
            $contents['autoload']['files'] = [];
        }

        $root_path = $this->getFilesPath();
        $revert_file_relative = str_replace($root_path, '', $this->getUnpackFile());
        if (array_search($revert_file_relative, $contents['autoload']['files']) !== false) {
            // Adiciona o arquivo com prioridade
            $contents['autoload']['files'] = array_merge(
                [$revert_file_relative],
                $contents['autoload']['files']
            );
        }

        if (file_put_contents($composer_file, json_encode($contents, JSON_PRETTY_PRINT)) === false) {
            throw new \RuntimeException("Não foi possível atualizar o arquivo  {$composer_file}");
            return false;
        }

        $this->info("O arquivo composer.json foi configurado com sucesso.");
        $this->info("Execute \"composer dump-autoload\" para atualizar as rotinas de carregamento.");

        return true;
    }

}
