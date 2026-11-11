<?php

declare(strict_types=1);

/*
 * Testes de integração: acessam o portal real da SEFAZ-BA pela internet.
 * NÃO fazem parte do `composer test` (veja phpunit.xml — a suíte padrão
 * só varre tests/Unit). Rodar com:
 *
 *   composer test:integration
 */

use ProvaSpider\Exception\CnpjNaoEncontradoException;
use ProvaSpider\Exception\CnpjRejeitadoException;
use ProvaSpider\Spider\SefazBaSpider;
use ProvaSpider\ValueObject\Consulta;

it('consulta um CNPJ real cadastrado na Bahia e devolve os dados', function () {
    $cadastro = (new SefazBaSpider())->consultar(new Consulta('15139629000194'));

    expect($cadastro->cnpj)->toBe('15139629000194')
        ->and($cadastro->razaoSocial)->toContain('ELETRICIDADE DO ESTADO DA BAHIA');
});

it('lança CnpjNaoEncontradoException para um CNPJ válido sem cadastro na Bahia', function () {
    expect(fn () => (new SefazBaSpider())->consultar(new Consulta('06990590000123')))
        ->toThrow(CnpjNaoEncontradoException::class);
});

it('lança CnpjRejeitadoException para um CNPJ com dígito verificador inválido', function () {
    expect(fn () => (new SefazBaSpider())->consultar(new Consulta('11111111111111')))
        ->toThrow(CnpjRejeitadoException::class);
});
