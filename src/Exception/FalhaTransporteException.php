<?php

declare(strict_types=1);

namespace ProvaSpider\Exception;

/**
 * A requisição não obteve resposta alguma: timeout, falha de conexão/DNS,
 * erro de TLS, ou limite de redirecionamentos excedido.
 */
final class FalhaTransporteException extends ConsultaException
{
}
