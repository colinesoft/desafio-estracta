<?php

declare(strict_types=1);

namespace ProvaSpider\Exception;

/**
 * O servidor respondeu, mas com erro do próprio servidor (status 5xx) ou uma
 * página de manutenção reconhecível.
 */
final class ServicoIndisponivelException extends ConsultaException
{
}
