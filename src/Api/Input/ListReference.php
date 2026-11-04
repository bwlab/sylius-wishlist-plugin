<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Input;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** A list given by a client: the numeric id (5, "5") or the IRI ".../shop/wishlists/5". Null/omitted = the default list. */
final class ListReference
{
    /** Query parameter used by the operations without a body (GET/PATCH/DELETE of a row). */
    public const QUERY_PARAMETER = 'list';

    public static function parse(int|string|null $value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (\is_string($value) && 1 === preg_match('#/wishlists/(\d+)$#', $value, $match)) {
            $value = $match[1];
        }
        if (\is_int($value) ? $value > 0 : (ctype_digit($value) && (int) $value > 0)) {
            return (int) $value;
        }

        throw new UnprocessableEntityHttpException('Invalid list: send the list id or its IRI.');
    }

    public static function fromRequest(?Request $request): ?int
    {
        $value = $request?->query->get(self::QUERY_PARAMETER);

        return self::parse(\is_string($value) ? $value : null);
    }
}
