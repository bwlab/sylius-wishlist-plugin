<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Identity;

use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use Bwlab\SyliusWishlistPlugin\Identity\WishlistMerger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class WishlistMergerAllTest extends TestCase
{
    private static function list(string $name, bool $default = false, ?CustomerInterface $customer = null): Wishlist
    {
        $w = new Wishlist();
        $w->setName($name);
        $w->setDefault($default);
        $w->setCustomer($customer);

        return $w;
    }

    private static function add(Wishlist $w, ProductVariantInterface $v, int $qty): void
    {
        $i = new WishlistItem();
        $i->setVariant($v);
        $i->setQuantity($qty);
        $w->addItem($i);
    }

    #[Test]
    public function defaults_merge_same_names_merge_new_names_pass_to_the_account(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $v = $this->createStub(ProductVariantInterface::class);
        $cDefault = self::list('My Favorites', true, $customer);
        $cBirthday = self::list('Birthday', false, $customer);
        $gDefault = self::list('My Favorites', true);
        $gBirthday = self::list('BIRTHDAY');
        $gXmas = self::list('Xmas');
        self::add($gDefault, $v, 1);
        self::add($cBirthday, $v, 2);
        self::add($gBirthday, $v, 4);

        $r = (new WishlistMerger())->mergeAll([$gXmas, $gBirthday, $gDefault], [$cDefault, $cBirthday], $customer);

        self::assertEqualsCanonicalizing([$cDefault, $cBirthday, $gXmas], $r['save']);
        self::assertEqualsCanonicalizing([$gDefault, $gBirthday], $r['delete']);
        self::assertSame($customer, $gXmas->getCustomer());
        self::assertSame(1, $cDefault->countItems());
        self::assertSame(4, $cBirthday->getItemForVariant($v)?->getQuantity());
    }

    #[Test]
    public function without_customer_lists_the_guest_default_becomes_the_default(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $gDefault = self::list('My Favorites', true);

        $r = (new WishlistMerger())->mergeAll([$gDefault, self::list('A')], [], $customer);

        self::assertCount(2, $r['save']);
        self::assertSame([], $r['delete']);
        self::assertSame($customer, $gDefault->getCustomer());
    }

    #[Test]
    public function beyond_the_maximum_extra_lists_merge_into_the_default(): void
    {
        $customer = $this->createStub(CustomerInterface::class);
        $v = $this->createStub(ProductVariantInterface::class);
        $cDefault = self::list('My Favorites', true, $customer);
        $customerLists = array_merge([$cDefault], array_map(fn (int $i) => self::list('L' . $i, false, $customer), range(1, 19)));
        $extra = self::list('Extra');
        self::add($extra, $v, 3);

        $r = (new WishlistMerger())->mergeAll([$extra], $customerLists, $customer);

        self::assertSame([$extra], $r['delete']);
        self::assertSame([$cDefault], $r['save']);
        self::assertSame(3, $cDefault->getItemForVariant($v)?->getQuantity());
    }
}
