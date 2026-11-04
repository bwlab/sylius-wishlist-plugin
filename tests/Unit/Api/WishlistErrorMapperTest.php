<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Domain error -> HTTP status and translated message. */
final class WishlistErrorMapperTest extends TestCase
{
    private function mapper(): WishlistErrorMapper
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $params, ?string $domain, ?string $locale) => sprintf('[%s|%s|%s|%s]', $locale, $domain, $id, json_encode($params)),
        );
        $locale = $this->createStub(LocaleContextInterface::class);
        $locale->method('getLocaleCode')->willReturn('it');

        return new WishlistErrorMapper($translator, $locale);
    }

    public function testNameErrorsAre422WithTranslatedMessageAndParameters(): void
    {
        $error = $this->mapper()->map(new WishlistNameException(WishlistNameException::DUPLICATE, ['%name%' => 'Natale']));

        self::assertInstanceOf(UnprocessableEntityHttpException::class, $error);
        self::assertSame('[it|validators|bwlab_wishlist.list.name.unique|{"%name%":"Natale"}]', $error->getMessage());
    }

    public function testTooManyListsIs422(): void
    {
        self::assertInstanceOf(
            UnprocessableEntityHttpException::class,
            $this->mapper()->map(new WishlistNameException(WishlistNameException::MAX_LISTS, ['%limit%' => 20])),
        );
    }

    public function testDeletingTheDefaultListIs409(): void
    {
        $error = $this->mapper()->map(new WishlistOperationException(WishlistOperationException::DELETE_DEFAULT));

        self::assertInstanceOf(ConflictHttpException::class, $error);
        self::assertStringContainsString('bwlab_wishlist.list.delete_default', $error->getMessage());
    }

    public function testMovingToTheSameListIs422(): void
    {
        self::assertInstanceOf(
            UnprocessableEntityHttpException::class,
            $this->mapper()->map(new WishlistOperationException(WishlistOperationException::SAME_LIST)),
        );
    }
}
