# Roteiro de preparação do ambiente (Windows)

> Anotação pessoal do processo de preparar a máquina para rodar o Desafio estracta.

Contexto: a máquina já tinha PHP 8.1.32 instalado e funcionando (com `openssl` e
`mbstring` ativos) globalmente em `C:\php`.

## 1. Checar o que já existia

```bash
php -v        # já tinha PHP 8.1.32 em C:\php, funcionando
composer -V   # não encontrado
```

Conclusão: faltava instalar o Composer.

## 2. Instalar o Composer

Baixado e instalado manualmente via `Composer-Setup.exe`
(https://getcomposer.org/download/), apontando para o `php.exe` existente.
Confirmado com:

```bash
composer -V
# Composer version 2.11.2
```

## 3. Ativar extensões necessárias no `php.ini`

Em `C:\php\php.ini`, confirmadas/ativadas (sem `;` no início da linha):

- `extension_dir = "ext"`
- `extension=openssl`
- `extension=mbstring`

Validado com:

```bash
php -m        # openssl e mbstring aparecem na lista
```

## 4. Instalar as dependências e rodar o fluxo normal do projeto

```bash
composer install
composer test
php bin/consultar.php <cnpj>
```

## Pendências / próximos passos

- [x] Rodar `composer install` com sucesso — 53 pacotes instalados.
- [x] Rodar `composer test` — 6 testes passaram (9 assertions), suíte inicial ok
  sem o spider implementado.
- [ ] Implementar `SefazBaSpider::consultar()` (ver [README.md](README.md), seção
  "Escopo da implementação").
