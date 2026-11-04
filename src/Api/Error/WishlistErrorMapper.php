<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Api\Error;

use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Domain errors of the lists -> HTTP errors with a translated message (locale of the request):
 * bad name / too many lists / same list = 422, deleting the default list = 409.
 */
final class WishlistErrorMapper
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LocaleContextInterface $localeContext,
    ) {
    }

    public function map(WishlistNameException|WishlistOperationException $e): HttpException
    {
        $message = $this->translator->trans(
            $e->getMessage(),
            $e instanceof WishlistNameException ? $e->parameters : [],
            'validators',
            $this->localeContext->getLocaleCode(),
        );

        return WishlistOperationException::DELETE_DEFAULT === $e->getMessage()
            ? new ConflictHttpException($message, $e)
            : new UnprocessableEntityHttpException($message, $e);
    }
}
