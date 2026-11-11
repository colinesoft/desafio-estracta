<?php

declare(strict_types=1);

namespace ProvaSpider\Http;

use ProvaSpider\Exception\FalhaTransporteException;

/**
 * Decorator que tenta de novo apenas quando a requisição não obtém resposta
 * alguma (FalhaTransporteException) — o único cenário em que tentar de novo
 * pode mudar o resultado. Nunca tenta de novo por causa de um status HTTP de
 * erro ou de um corpo de resposta inesperado, porque isso não é recuperável
 * repetindo a mesma requisição.
 */
final class RetryingTransport implements Transport
{
    public function __construct(
        private readonly Transport $transport,
        private readonly int $maximoTentativas = 2,
    ) {
    }

    public function enviarFormulario(string $url, array $campos): RespostaHttp
    {
        $tentativa = 0;

        while (true) {
            $tentativa++;

            try {
                return $this->transport->enviarFormulario($url, $campos);
            } catch (FalhaTransporteException $exception) {
                if ($tentativa >= $this->maximoTentativas) {
                    throw $exception;
                }
            }
        }
    }
}
