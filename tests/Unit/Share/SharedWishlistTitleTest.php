<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Share;

use Bwlab\SyliusWishlistPlugin\Share\SharedWishlistTitle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Customer;

final class SharedWishlistTitleTest extends TestCase
{
    private static function customer(?string $first): Customer
    {
        $customer = new Customer();
        $customer->setFirstName($first);
        $customer->setLastName('Rossi-Secret');
        $customer->setEmail('mario@example.com');

        return $customer;
    }

    #[Test]
    public function the_title_has_the_list_name_and_only_the_first_name(): void
    {
        $title = SharedWishlistTitle::compose('Birthday', SharedWishlistTitle::firstName(self::customer(' Mario ')));

        self::assertSame('Birthday · Mario', $title);
        self::assertStringNotContainsString('Rossi', $title);
        self::assertStringNotContainsString('example.com', $title);
    }

    #[Test]
    public function without_a_first_name_the_title_is_just_the_list_name(): void
    {
        self::assertNull(SharedWishlistTitle::firstName(self::customer('  ')));
        self::assertNull(SharedWishlistTitle::firstName(null));
        self::assertSame('Birthday', SharedWishlistTitle::compose('Birthday', null));
    }

    #[Test]
    public function a_very_long_first_name_is_cut(): void
    {
        self::assertSame(40, mb_strlen((string) SharedWishlistTitle::firstName(self::customer(str_repeat('é', 100)))));
    }
}
