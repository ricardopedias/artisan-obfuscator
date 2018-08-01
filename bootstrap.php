<?php

    /*
    | Bootstrap PhpUnit
    | Ao executar os testes de unidade, é preciso notificar o autoloader
    | do composer sobre as classes personalizadas que possam existir
    | na implementação dos testes
    */

    error_reporting(E_ALL);

    // Invoca o loader do composer
    $autoload_path = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'vendor', 'autoload.php']);
    $loader = require $autoload_path;

    // Descobre o namespace do pacote
    $json      = json_decode(file_get_contents('composer.json'), true);
    $psr4      = $json['autoload-dev']['psr-4'];
    $namespace = key($psr4);
    $testspath = current($psr4);

    // Adiciona o namespace no loader
    $loader->addPsr4($namespace, __DIR__ . DIRECTORY_SEPARATOR . $testspath);
