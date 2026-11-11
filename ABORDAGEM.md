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

# Como eu desenvolvi esse projeto

## O que era pra fazer

O desafio pedia pra eu construir uma parte específica de um programa já pronto: ele recebe um CNPJ, busca os dados desse CNPJ num site do governo da Bahia, e devolve essas informações organizadas. O resto do programa (validação do CNPJ, o formato dos dados de saída, os testes iniciais) já vinha pronto. Minha parte era: ir buscar o dado de verdade no site e trazer ele pra dentro do programa, do jeito certo.

## Por onde comecei

Antes de escrever qualquer código, entrei no site de verdade e fui ver como ele funciona por dentro: que formulário é esse, o que ele pede, o que ele devolve quando encontra um cadastro e o que devolve quando não encontra.

## Como organizei o código

Separei o trabalho em três partes que não se misturam:

1. Uma parte que só sabe "mandar uma informação pela internet e trazer a resposta de volta" — sem saber nada sobre CNPJ, nem sobre o site específico.
2. Uma parte que só sabe "ler uma página de resultado e tirar dali os dados que interessam" — sem saber de onde essa página veio.
3. Uma parte que junta as duas: monta o pedido certo, manda pra internet, pega a resposta e manda pra parte que lê os dados.

Separar assim foi proposital: deixou eu testar a parte de "ler os dados" sem precisar estar conectado à internet toda hora — eu guardei cópias de páginas (inventadas, sem dado real de ninguém) e testei contra elas. Isso deixou tudo mais rápido de testar e mais confiável.

Pra parte de "mandar e trazer a resposta pela internet", usei uma ferramenta pronta chamada Guzzle, bem conhecida e usada por muita gente em projetos PHP, em vez de escrever esse pedaço do zero. Ela já resolve coisas que eu precisava (tempo limite de espera, limite de quantas vezes seguir um redirecionamento) e também já vem com um jeito fácil de simular respostas falsas nos testes, sem precisar acessar a internet de verdade.

Também criei um jeito de avisar quando algo dá errado que diferencia os motivos: CNPJ que não existe no cadastro não é a mesma coisa que o site estar fora do ar, que também não é a mesma coisa que a internet ter caído no meio da consulta. Cada motivo tem seu próprio aviso, pra quem for usar o programa saber exatamente o que aconteceu.

## As dificuldades que apareceram pelo caminho

**Primeira: comecei com um nome provisório pra cada informação, e confirmei o nome certo depois.**
Pra não travar esperando a situação ideal, comecei montando a parte que lê os dados usando nomes prováveis pra cada campo (tipo "nome da empresa", "endereço"), só pra ter logo uma versão funcionando e testável. Antes de dar como pronto, fui no site de verdade conferir se os nomes que eu tinha usado batiam exatamente com o que aparece lá. Não batiam todos — então ajustei essa parte do código até ela refletir certinho o que a página realmente mostra, com calma, entendendo onde cada informação de fato fica.

**Segunda: um campo escondido que eu não tinha visto.**
O formulário do site tem um botão. Parece bobagem, mas esse botão manda uma informação escondida junto com o pedido, e o site usa essa informação pra saber que tipo de busca a pessoa quis fazer (por CNPJ, por CPF, ou por outro número). Se eu não mandasse exatamente essa informação, o site me devolvia um erro que não fazia sentido nenhum, até eu descobrir que faltava simular o clique nesse botão.

**Terceira (a mais chata de entender): o site se comporta diferente se eu não "visito" ele antes.**
Essa foi a mais difícil. Às vezes, quando eu mandava a busca direto, sem passar primeiro pela página inicial do site, ele me devolvia um erro estranho — como se o CNPJ não existisse, mesmo quando eu sabia que existia. Descobri, testando bastante, que o site guarda uma espécie de "crachá temporário" (dá pra comparar com pegar uma senha ao entrar numa fila) na primeira visita, e só funciona direito se esse crachá já existir antes de eu mandar a busca. Resolvi isso fazendo o programa "visitar" a página inicial primeiro, guardando esse crachá, e só depois mandando a busca de verdade — exatamente como uma pessoa faria clicando no site.

## O que eu aprendi com isso

A maior lição foi sobre quando vale a pena confirmar antes de seguir em frente. Começar com nomes provisórios pra andar rápido funcionou bem, mas o ajuste final só foi possível confirmando tudo contra a página de verdade — não dava pra ter certeza de nada só imaginando.

A outra lição foi sobre paciência pra investigar: o problema do "crachá temporário" não tinha uma mensagem de erro clara apontando a causa. Só descobri testando de várias formas diferentes, comparando o que mudava entre uma tentativa que funcionava e uma que não funcionava, até enxergar o padrão.
