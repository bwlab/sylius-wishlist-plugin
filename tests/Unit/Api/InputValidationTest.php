<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\Input\AddWishlistItem;
use Bwlab\SyliusWishlistPlugin\Api\Input\ChangeWishlistItemQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class InputValidationTest extends TestCase
{
    private function violations(object $input): int
    {
        return \count(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($input));
    }

    public function testPatchWithoutQuantityIsInvalid(): void
    {
        self::assertSame(1, $this->violations(new ChangeWishlistItemQuantity()));
    }

    public function testQuantityRange(): void
    {
        foreach ([0, -1, 10000, \PHP_INT_MAX] as $bad) {
            $patch = new ChangeWishlistItemQuantity();
            $patch->quantity = $bad;
            self::assertSame(1, $this->violations($patch), (string) $bad);

            $add = new AddWishlistItem();
            $add->variantCode = 'X';
            $add->quantity = $bad;
            self::assertSame(1, $this->violations($add), (string) $bad);
        }

        $ok = new ChangeWishlistItemQuantity();
        $ok->quantity = 9999;
        self::assertSame(0, $this->violations($ok));
    }
}
