# 1. Artisan Obfuscator

## 1.1. Sobre o Artisan Obfuscator

O Artisan Obfuscator é uma ferramenta que adiciona a possibilidade de ofuscar o código PHP de projetos feitos com Laravel de forma fácil e rápida usando o **artisan**. A programação é ocultada, mantendo sua execução normal mas inviabilizando a alteração do código. 

Internamente, esta ferramenta usa o ***Light Obfuscator***, uma biblioteca bem testada que atualmente é executada em vários projetos reais em produção na internet. O Light Obfuscator surgiu da necessidade de ocultar o código, que possuía direitos autorais, e precisava ser executando no servidor de um cliente. Outra necessidade importante era que o código resultante da ofuscação fosse o mais rápido possível, custando o mínimo de recursos para o servidor executar.

Para saber mais sobre a biblioteca, visite o [repositório do Light Obfuscator](https://github.com/rpdesignerfly/light-obfuscator).

## 1.3. As versões da biblioteca

O método de versionamento utilizado para as evoluções da biblioteca seguem as regras da [Semantic Versioning](https://semver.org/lang/pt-BR/), uma especificação bastante utilizada na industria de Softwares, criada por Thom Preston Werner, criador do Gravatars e Co-Fundador do Github.

O formato das versões seguem a seguinte convenção:
```
X.Y.Z
```
Onde:

* X (major version): Muda quando temos incompatibilidade com versões anteriores.
* Y (minor version): Muda quando temos novas funcionalidades em nosso software.
* Z (patch version): Muda quando temos correções de bugs lançadas.

Explicando melhor:

**X**: é incrementado sempre que alterações **incompatíveis** com as versões anteriores da API forem implementadas. Por exemplo, sendo a versão atual 1.0.5, uma implementação precisou alterar campos no banco de dados, então a próxima versão será 2.0.0;

**Y**: é incrementado sempre que forem implementadas novas funcionalidades **compatíveis** e que não afetem o funcionamento normal da aplicação. Por exemplo, sendo a versão atual 1.9.5, uma nova funcionalidade foi adicionada, então a próxima versão será 1.10.0. Note que a versão do último número foi zerada para seguir a especificação da Semantic Versioning;

**Z**: é incrementado sempre que forem implementadas correções de falhas (bug fixes) que não afetem o funcionamento normal da aplicação. Por exemplo, sendo a versão atual 1.0.9, uma correção foi feita, gerando uma refatoração que otimizou o código, então a próxima versão será 1.0.10.

## Sumário

1. [Sobre](01-About.md)
2. [Instalação](02-Installation.md)
3. [Como Usar](03-Usage.md)
