# 2. Instalação

## 2.1. Requisitos do usuário

Este guia leva em consideração que os usuários estejam utilizando terminal Unix (Linux ou Unix/Mac). Usuários que estejam usando Windows podem executar os mesmos comandos através de emuladores de terminal. Uma ótima alternativa para Windows é o [Git Bash](https://gitforwindows.org/), que acompanha o excelente [Git for Windows](https://gitforwindows.org/).

## 2.2. Requisitos do servidor

Para o correto funcionamento, o Artisan Obfuscator precisa que os seguintes requisitos básicos sejam atendidos, estando disponíveis no servidor:

* PHP >= 7.1.0

## 2.3. Instalando o pacote

O Artisan Obfuscator se encontra no [Packagist](https://packagist.org/), podendo ser alocado facilmente em qualquer projeto através do [Composer](http://getcomposer.org/).

Com o composer devidamente instalado no sistema operacional do desenvolvedor, execute o seguinte comando para instalar a última versão do Artisan Obfuscator:

```bash
$ cd /diretorio/meu/projeto/
$ composer require plexi/artisan-obfuscator
```

Se preferir instalar uma versão específica, basta substituir pelo comando:

```bash
$ composer require plexi/artisan-obfuscator:1.0.0
```

Os comandos acima vão adicionar automaticamente a chamada para o pacote no arquivo **composer.json** do projeto, excutando em seguida o processo de instalação.

## Sumário

1. [Sobre](01-About.md)
2. [Instalação](02-Installation.md)
3. [Como Usar](03-Usage.md)
