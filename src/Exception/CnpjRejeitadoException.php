<?php

declare(strict_types=1);

namespace ProvaSpider\Exception;

/**
 * A própria SEFAZ-BA rejeitou o CNPJ por dígito verificador incorreto
 * ("Cnpj incorreto - dígito verificador !").
 *
 * Não confundir com a validação estrutural feita por
 * {@see \ProvaSpider\ValueObject\Consulta}, que lança InvalidArgumentException
 * antes mesmo de qualquer consulta ser feita. Esta exceção só existe porque a
 * SEFAZ-BA aplica uma checagem de dígito verificador que o projeto-base não
 * reproduz localmente.
 */
final class CnpjRejeitadoException extends ConsultaException
{
}
