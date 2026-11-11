<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use ProvaSpider\Exception\FalhaTransporteException;
use ProvaSpider\Http\HttpTransport;

it('devolve status e corpo da resposta recebida com sucesso', function () {
    $mock = new MockHandler([
        new Response(200, [], 'corpo de teste'),
    ]);
    $transport = new HttpTransport(new Client(['handler' => HandlerStack::create($mock)]));

    $resposta = $transport->enviarFormulario('https://exemplo.test/consulta', ['CGC' => '00000000000000']);

    expect($resposta->statusCode)->toBe(200)
        ->and($resposta->corpo)->toBe('corpo de teste');
});

it('devolve a resposta normalmente mesmo quando o status HTTP indica erro', function () {
    $mock = new MockHandler([
        new Response(503, [], 'indisponível'),
    ]);
    $transport = new HttpTransport(new Client(['handler' => HandlerStack::create($mock)]));

    $resposta = $transport->enviarFormulario('https://exemplo.test/consulta', []);

    expect($resposta->statusCode)->toBe(503)
        ->and($resposta->corpo)->toBe('indisponível');
});

it('lança FalhaTransporteException quando a requisição não obtém resposta', function () {
    $mock = new MockHandler([
        new ConnectException('timeout de conexão', new Request('POST', 'https://exemplo.test/consulta')),
    ]);
    $transport = new HttpTransport(new Client(['handler' => HandlerStack::create($mock)]));

    expect(fn () => $transport->enviarFormulario('https://exemplo.test/consulta', []))
        ->toThrow(FalhaTransporteException::class);
});

it('visita a urlOrigem antes do POST e carrega a sessão da primeira para a segunda requisição', function () {
    $historico = [];
    $mock = new MockHandler([
        new Response(200, ['Set-Cookie' => 'ASPSESSION=abc123; path=/']),
        new Response(200, [], 'resultado final'),
    ]);
    $pilha = HandlerStack::create($mock);
    $pilha->push(Middleware::history($historico));
    $transport = new HttpTransport(new Client(['handler' => $pilha]));

    $resposta = $transport->enviarFormulario(
        'https://exemplo.test/result.asp',
        ['CGC' => '00000000000000'],
        urlOrigem: 'https://exemplo.test/form.asp'
    );

    expect($resposta->corpo)->toBe('resultado final')
        ->and($historico)->toHaveCount(2)
        ->and((string) $historico[0]['request']->getUri())->toBe('https://exemplo.test/form.asp')
        ->and($historico[0]['request']->getMethod())->toBe('GET')
        ->and((string) $historico[1]['request']->getUri())->toBe('https://exemplo.test/result.asp')
        ->and($historico[1]['request']->getMethod())->toBe('POST')
        ->and($historico[1]['request']->getHeaderLine('Cookie'))->toContain('ASPSESSION=abc123');
});

it('não faz a visita prévia quando urlOrigem não é informada', function () {
    $historico = [];
    $mock = new MockHandler([new Response(200, [], 'ok')]);
    $pilha = HandlerStack::create($mock);
    $pilha->push(Middleware::history($historico));
    $transport = new HttpTransport(new Client(['handler' => $pilha]));

    $transport->enviarFormulario('https://exemplo.test/result.asp', []);

    expect($historico)->toHaveCount(1);
});
