<?php

namespace Obfuscator\Libs;

class PhpObfuscator
{
	public function encode($filename)
	{
	    global $obfuscate;
	    
	    // Filtros a excluir
	    $obfuscateExcludes = array(
	        'vendor',
	        'Plexi/autoloader.php',
	        'Layout/Engines/Library',
	        'Assets/Compilers/Library',
	        'Admin/Assets',
	        'Ads/Assets',
	        'Banners/Assets',
	        'Install/Assets',
	        'Layouts/Assets',
	        'Login/Assets',
	        'Pages/Assets',
	        'Place/Assets',
	        'Products/Assets',
	        'Sites/Assets',
	        'Exception'
	    );
	    
	    $allowable = TRUE;
	    foreach ($obfuscateExcludes as $strip) {
	        if (strpos($filename, $strip) != FALSE) {
	            $allowable = FALSE;
	            $console->showInfo("Filtro de exclusão encontrado: \"" . $strip . "\"!");
	            break;
	        }
	    }
	    
	    if ($allowable == FALSE) {
	        $console->showInfo("O arquivo não será ofuscado!");
	        return FALSE;
	    }
	    
	    
	    // É uma aplicação?
	    if (strpos($filename, 'App')) {
	        $space = 'App';  
	    }
	    elseif (strpos($filename, 'Plexi')) {
	        $space = 'Plexi';    
	    }
	    else {
	        $console->showInfo("Apenas arquivos Plexi e App podem ser ofuscados!");
	        $console->showInfo("Arquivo " . $filename . " ignorado!");
	        return FALSE;
	    }

	    $onlyPath = str_replace('.php', '', $filename);    
	    
	    $parts = explode($space, $onlyPath);
	    if (isset($parts[1])) {
	        
	        //$fileContent = file_get_contents($filename);
	        $fileContent = php_strip_whitespace($filename);
	        
	        
	        // Ignora os diretórios
	        $ignore = array(
	            'Assets'
	            );
	        $currentDir = dirname($filename);
	        foreach ($ignore as $dir) {
	            if(strpos($currentDir, $dir)) {
	                $console->showInfo("Diretório " . $currentDir . " ignorado!");
	                return FALSE;
	            }
	        }
	        
	        // Determina se é uma classe
	        $classNameSpace = str_replace('/', "\\", $space.$parts[1]);
	        $className = basename($onlyPath);
	        if (strpos($fileContent, 'class '.$className) == FALSE) {
	            $console->showInfo("Apenas classes podem ser ofuscadas!");
	            $console->showInfo("Arquivo " . $filename . " ignorado!");
	            return FALSE;
	        }
	        
	        // Muda o modo de execução no arquivo Client.php para PRODUÇÃO
	        if($className == 'Client') {
	            $fileContent = str_replace(
	                '$this->setEnvironment(Kernel::ENV_DEVELOPMENT);',
	                '$this->setEnvironment(Kernel::ENV_PRODUCTION);',
	                $fileContent);
	        }
	        
	        $codeObfuscator = new CodeObfuscator;
	        $encodeLevel = ($obfuscate != NULL && intval($obfuscate) > 0)
	                    ? $obfuscate : 1;
	        $code = $codeObfuscator->prepareCode($fileContent);
	        
	        
	        // FASE 1 -----------------
	        
	        // No arquivo ArrayHash será inserida uma função camuflada 
	        // chamada php_zencoding(), que será usada para descompactar 
	        // o código ofuscado das próximas fases.
	        // Nível de ofuscação: BAIXO
	        if (basename($filename) == 'ArrayHash.php') {
	            
	            $encoded = "";
	            
	            // Esconde o ArrayHash
	            $encoded.= $codeObfuscator->toASCII("eval(base64_decode('");
	            $encoded.= base64_encode($code);
	            $encoded.= $codeObfuscator->toASCII("'));");
	            
	            // Esconde a função
	            $phpZencoding = "function php_zencoding(\$data){ 
	                \$partOne = mb_substr(\$data, 0, 10, 'utf-8');
	                \$partTwo = mb_substr(\$data, 12, NULL, 'utf-8');
	                return base64_decode(\$partOne . \$partTwo);
	            }";
	            
	            $encoded.= $codeObfuscator->toASCII("eval(base64_decode('");
	            $encoded.= base64_encode($phpZencoding);
	            $encoded.= $codeObfuscator->toASCII("'));");
	            
	            $encodedFileContents = "<?php\n eval(\"{$encoded}\");";  
	        }
	        
	        // FASE 2 -----------------
	        
	        // No arquivo Object será inserida uma chamada de inclusão para 
	        // o arquivo \Plexi\Db\Connection.php, onde se encontram as outras 
	        // funções ocultas para reverter a ofuscação. 
	        // 
	        // Nível de ofuscação: MÉDIO
	        // Para referter o código, o hacker usará ferramentas de automação 
	        // que revelam o código. Como estamos usando uma função personalizada 
	        // Este processo não poderá prosseguir sem primeiro declarar 
	        // a função php_zencoding.
	        // Outro agravante é que todos os códigos base64 são quebrados na 
	        // criação dos arquivos e reestruturados na execução, impossibilitando 
	        // as ferramentas de validar o código.
	        // 
	        elseif (basename($filename) == 'Object.php') {
	            
	            $insertion = "namespace Plexi\Common; require_once dirname(dirname(__FILE__)) "
	                . ". DIRECTORY_SEPARATOR . 'Db' "
	                . ". DIRECTORY_SEPARATOR . 'Connection.php'; ";
	        
	            $code = str_replace('namespace Plexi\Common;', $insertion, $code);
	            
	            $encoded = "";
	            $encoded.= $codeObfuscator->toASCII("eval(php_zencoding('");
	            $encoded.= baseOne($code);
	            $encoded.= $codeObfuscator->toASCII("'));");
	            $encodedFileContents = "<?php\n eval(\"{$encoded}\");";  
	        }
	        
	        // FASE 3 -----------------
	        
	        // No arquivo \Plexi\Db\Connection.php, ocultamos as funções 
	        // responsáveis pela execução dos códigos ofuscados.
	        // 
	        // Nível de ofuscação: MÉDIO
	        // Mesmo caso da faze anterior
	        //
	        elseif (basename($filename) == 'Connection.php') {

	            $encoded = $codeObfuscator->encodeString($code, $encodeLevel);
	            
	            $functionsFile = dirname(dirname(__FILE__)) 
	                . DIRECTORY_SEPARATOR . 'Libs'
	                . DIRECTORY_SEPARATOR . 'RevertObfuscation.php';
	            $functionsFileContent = file_get_contents($functionsFile);
	            if ($functionsFileContent != FALSE) {
	                
	                $functionsFileContent = $codeObfuscator->prepareCode($functionsFileContent);
	                $stageOne.= $codeObfuscator->toASCII("eval(php_zencoding('");
	                $stageOne.= baseOne($functionsFileContent);
	                $stageOne.= $codeObfuscator->toASCII("'));");
	                
	                $encodedFileContents = "<?php\n eval(\"{$stageOne} {$encoded}\");";
	            }
	            else {
	                $encodedFileContents = "<?php\n eval(\"{$encoded}\");";                
	            }
	        }
	        
	        // FASE 4 -----------------
	        
	        // Os arquivos seguintes são todos revertidos com base nas funções 
	        // ocultas geradas pelo CodeObfuscator, que são randômicas, ou seja, 
	        // cada arquivo chamará funções diferentes, dificultando imensamente a 
	        // possibilidade de criar rotinas para automatizar a tarefa de revelar 
	        // o código ofuscado.
	        // 
	        // Nível de ofuscação: ALTO
	        // Mesmo que o programador consiga chegar até aqui, ele terá que 
	        // analizar arquivo por arquivo, decifrar todas as funções existentes 
	        // e executá-las na sequencia certa. :)
	        else {
	            
	            $encoded = $codeObfuscator->encodeString($code, $encodeLevel);
	            $encodedFileContents = "<?php\n eval(\"{$encoded}\");";
	        }
	        
	        $console->showInfo("Arquivo " . $filename . " ofuscado com sucesso!");
	        return $encodedFileContents;
	    }
	    else {
	        return FALSE;
	    }
	}
}