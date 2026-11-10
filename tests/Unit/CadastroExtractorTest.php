<?php

declare(strict_types=1);

use ProvaSpider\Extraction\CadastroExtractor;

it('extrai os campos de um cadastro completo a partir do HTML', function () {
    $html = file_get_contents(__DIR__ . '/../Fixtures/cadastro-completo.html');

    $campos = (new CadastroExtractor())->extrair($html);

    expect($campos)->toBe([
        'inscricaoEstadual' => '123456789',
        'cnpj' => '12345678000199',
        'razaoSocial' => 'EMPRESA FICTÍCIA DE TESTE LTDA',
        'nomeFantasia' => 'LOJA TESTE',
        'situacaoCadastral' => 'ATIVO',
        'endereco' => 'RUA DAS FLORES, 123, CENTRO, SALVADOR - BA, CEP 40000-000',
        'atividadeEconomica' => '4711301 - Comércio varejista de mercadorias em geral',
        'regimeApuracao' => 'NORMAL',
    ]);
});

it('representa campos ausentes como null, sem quebrar a extração dos demais', function () {
    $html = file_get_contents(__DIR__ . '/../Fixtures/cadastro-campos-ausentes.html');

    $campos = (new CadastroExtractor())->extrair($html);

    expect($campos)->toBe([
        'inscricaoEstadual' => '987654321',
        'cnpj' => '98765432000111',
        'razaoSocial' => 'OUTRA EMPRESA FICTÍCIA LTDA',
        'nomeFantasia' => null,
        'situacaoCadastral' => 'ATIVO',
        'endereco' => 'RUA SEM NOME, S/N, ZONA RURAL, BARREIRAS - BA',
        'atividadeEconomica' => null,
        'regimeApuracao' => null,
    ]);
});
