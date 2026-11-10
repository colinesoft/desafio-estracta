<?php

declare(strict_types=1);

namespace ProvaSpider\Exception;

/**
 * O CNPJ tem formato válido, mas a SEFAZ-BA não possui cadastro para ele
 * ("Nenhum registro encontrado !").
 */
final class CnpjNaoEncontradoException extends ConsultaException
{
}
