<?php

declare(strict_types=1);

use ProvaSpider\Exception\FalhaTransporteException;
use ProvaSpider\Http\RespostaHttp;
use ProvaSpider\Http\RetryingTransport;
use ProvaSpider\Http\Transport;

function transportContador(int $falhasAntesDeSuceder): Transport
{
    return new class ($falhasAntesDeSuceder) implements Transport {
        public int $chamadas = 0;

        public function __construct(private int $falhasAntesDeSuceder)
        {
        }

        public function enviarFormulario(string $url, array $campos, ?string $urlOrigem = null): RespostaHttp
        {
            $this->chamadas++;

            if ($this->falhasAntesDeSuceder > 0) {
                $this->falhasAntesDeSuceder--;

                throw new FalhaTransporteException('timeout de conexão');
            }

            return new RespostaHttp(200, 'ok');
        }
    };
}

it('tenta de novo quando a primeira tentativa falha e devolve a resposta da segunda', function () {
    $interno = transportContador(1);
    $transport = new RetryingTransport($interno, maximoTentativas: 2);

    $resposta = $transport->enviarFormulario('https://exemplo.test', []);

    expect($resposta->statusCode)->toBe(200)
        ->and($interno->chamadas)->toBe(2);
});

it('desiste depois do limite de tentativas e propaga a exceção', function () {
    $interno = transportContador(5);
    $transport = new RetryingTransport($interno, maximoTentativas: 2);

    expect(fn () => $transport->enviarFormulario('https://exemplo.test', []))
        ->toThrow(FalhaTransporteException::class);

    expect($interno->chamadas)->toBe(2);
});

it('não tenta de novo quando a primeira tentativa já funciona', function () {
    $interno = transportContador(0);
    $transport = new RetryingTransport($interno, maximoTentativas: 2);

    $transport->enviarFormulario('https://exemplo.test', []);

    expect($interno->chamadas)->toBe(1);
});
