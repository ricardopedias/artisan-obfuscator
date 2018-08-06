<?php
/**
 * @see       https://github.com/rpdesignerfly/artisan-obfuscator
 * @copyright Copyright (c) 2018 Ricardo Pereira Dias (https://rpdesignerfly.github.io)
 * @license   https://github.com/rpdesignerfly/artisan-obfuscator/blob/master/license.md
 */

declare(strict_types=1);

namespace Obfuscator\Commands;

use Illuminate\Console\Command;

class ObfuscatePkgCommand extends BaseCommand
{
    /**
     * Diretórios que, caso existam,
     * não serão ofuscados em nenhuma hipótese.
     *
     * @var array
     */
    private $exclude_dirs = [
        'vendor',
        'node_modules',
    ];

    /**
     * Arquivos que, caso existam,
     * não serão ofuscados em nenhuma hipótese
     *
     * @var array
     */
    private $exclude_files = [
        '.env',
    ];

    /**
     * Assinatuta do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'obfuscate:app
                            {composerfile? : Caminho completo até o arquivo composer.json da aplicação}
                            {appdir? : Nome do diretório com a aplicação ofuscada}
                            ';

    /**
     * Descrição do comando no terminal
     *
     * @var string
     */
    protected $description = 'Ofusca o código PHP contido em uma aplicação Laravel para ser distribuido sem possibilitar sua leitura';

    /**
     * Devolve a localização completa até o arquivo que conterá as funções de reversão.
     * Este arquivo sejá gerado pelo processo de ofuscação automaticamente
     * e adicionado no arquivo 'autoloader.php' da aplicação.
     *
     * @abstract
     * @return string
     */
    protected function getUnpackFunctionsFile()
    {
        return $this->getAppPath('app/App.php');
    }

    /**
     * Devolve o caminho completo até o arquivo 'composer.json', usado para
     * disponibbilizar os arquivos da aplicação.
     *
     * @abstract
     * @return string
     */
    protected function getComposerFile()
    {
        return $this->getAppPath('composer.json');
    }

    /**
     * Executa o algoritmo do comando de terminal.
     *
     * @return mixed
     */
    public function handle()
    {
        // nome do diretório ofuscado
        $appob_name = $this->argument('appob') ?? 'appob';

        // Caminho ao diretório ofuscado
        $path_appob  = $this->parsePath($appob_name);

        // Caminho ao diretório original
        $path_app  = $this->parsePath('app');

        // Ofusca o diretório
        if ($this->obfuscateDirectory($path_app, $path_appob) == false) {
            $this->error("Erros ocorreram ao tentar ofuscar o diretório {$path_app}");
        }

        // Renomeia o diretório ofuscado e efetua o backup do original
        $this->renameObfuscatedResult($path_app, $path_appob);

        // Gera uma lista com todos os arquivos PHP
        $index = $this->indexDirectory($path_app);

        // Salva o arquivo de reversão
        $revert_file = $path_app . $this->ds . $this->getFunctionsFilename();
        $ob = $this->getObfuscator()->saveRevertFile($revert_file);
        if($ob == false) {
            $this->error("Ocorreu um erro ao tentar gerar o arquivo de reversão");
            return false;
        }

        $static_loader = array_merge([$revert_file], $index);

        if ($this->generateAutoloader($index, $path_app) == false) {
            $this->error("Não foi possível gerar o autoloader para {$path_app}");
        }

        $this->updateComposerJson();

        $this->info("A aplicação foi ofuscada com sucesso para o diretório {$path_app}");
        $this->info("A aplicação original foi movida para backup");
    }

}
