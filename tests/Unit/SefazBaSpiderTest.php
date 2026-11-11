<?php

declare(strict_types=1);

use ProvaSpider\Exception\CnpjNaoEncontradoException;
use ProvaSpider\Exception\CnpjRejeitadoException;
use ProvaSpider\Exception\FalhaTransporteException;
use ProvaSpider\Exception\RespostaInesperadaException;
use ProvaSpider\Exception\ServicoIndisponivelException;
use ProvaSpider\Extraction\CadastroExtractor;
use ProvaSpider\Http\RespostaHttp;
use ProvaSpider\Http\Transport;
use ProvaSpider\Spider\SefazBaSpider;
use ProvaSpider\ValueObject\Consulta;

function transportFixo(int $statusCode, string $corpo): Transport
{
    return new class ($statusCode, $corpo) implements Transport {
        public ?string $urlRecebida = null;

        /** @var array<string, string>|null */
        public ?array $camposRecebidos = null;

        public function __construct(private readonly int $statusCode, private readonly string $corpo)
        {
        }

        public function enviarFormulario(string $url, array $campos): RespostaHttp
        {
            $this->urlRecebida = $url;
            $this->camposRecebidos = $campos;

            return new RespostaHttp($this->statusCode, $this->corpo);
        }
    };
}

it('monta os campos certos da consulta e devolve o cadastro extraído', function () {
    $html = file_get_contents(__DIR__ . '/../Fixtures/cadastro-completo.html');
    $transport = transportFixo(200, $html);

    $spider = new SefazBaSpider($transport, new CadastroExtractor());
    $cadastro = $spider->consultar(new Consulta('00000000000000'));

    expect($transport->camposRecebidos)->toBe([
        'sefp' => '1',
        'estado' => 'BA',
        'CGC' => '00000000000000',
        'B1' => 'CNPJ  ->',
    ])
        ->and($cadastro->razaoSocial)->toBe('EMPRESA FICTÍCIA DE TESTE LTDA')
        ->and($cadastro->cnpj)->toBe('12345678000199');
});

it('lança CnpjNaoEncontradoException quando a SEFAZ-BA não tem cadastro', function () {
    $transport = transportFixo(200, 'sefaz.ba :: Consulta Nenhum registro encontrado !');

    $spider = new SefazBaSpider($transport, new CadastroExtractor());

    expect(fn () => $spider->consultar(new Consulta('11111111111111')))
        ->toThrow(CnpjNaoEncontradoException::class);
});

it('lança CnpjRejeitadoException quando a SEFAZ-BA rejeita o dígito verificador', function () {
    $transport = transportFixo(200, 'sefaz.ba :: Consulta Cnpj incorreto - dígito verificador !');

    $spider = new SefazBaSpider($transport, new CadastroExtractor());

    expect(fn () => $spider->consultar(new Consulta('11111111111111')))
        ->toThrow(CnpjRejeitadoException::class);
});

it('lança ServicoIndisponivelException quando o status HTTP é 5xx', function () {
    $transport = transportFixo(503, 'Serviço indisponível no momento.');

    $spider = new SefazBaSpider($transport, new CadastroExtractor());

    expect(fn () => $spider->consultar(new Consulta('00000000000000')))
        ->toThrow(ServicoIndisponivelException::class);
});

it('lança RespostaInesperadaException quando o status HTTP não é 2xx nem 5xx', function () {
    $transport = transportFixo(404, 'não encontrado');

    $spider = new SefazBaSpider($transport, new CadastroExtractor());

    expect(fn () => $spider->consultar(new Consulta('00000000000000')))
        ->toThrow(RespostaInesperadaException::class);
});

it('propaga FalhaTransporteException sem tentar interpretar nada', function () {
    $transport = new class () implements Transport {
        public function enviarFormulario(string $url, array $campos): RespostaHttp
        {
            throw new FalhaTransporteException('timeout de conexão');
        }
    };

    $spider = new SefazBaSpider($transport, new CadastroExtractor());

    expect(fn () => $spider->consultar(new Consulta('00000000000000')))
        ->toThrow(FalhaTransporteException::class);
});
