<?php

declare(strict_types=1);

namespace ProvaSpider\Exception;

/**
 * A resposta teve status 2xx, mas o HTML não corresponde a nenhum dos
 * padrões conhecidos (cadastro encontrado, sem registro, dígito rejeitado) —
 * provável mudança de layout da página ou HTML corrompido.
 */
final class RespostaInesperadaException extends ConsultaException
{
}
