<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
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
