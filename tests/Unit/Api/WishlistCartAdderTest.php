<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use Bwlab\SyliusWishlistPlugin\Api\Cart\WishlistCartAdder;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Checker\WishlistAvailability;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Entity\WishlistItem;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WishlistCartAdderTest extends TestCase
{
    private ChannelInterface $channel;

    /** @var list<object> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $this->channel = $this->createStub(ChannelInterface::class);
        $this->dispatched = [];
    }

    /** @param array<string, WishlistAvailability> $availability variant code => status */
    private function adder(array $availability, bool $cartExists = true, ?string $violation = null): WishlistCartAdder
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findCartByTokenValueAndChannel')->willReturn($cartExists ? $this->createStub(OrderInterface::class) : null);

        $checker = $this->createStub(VariantAvailabilityCheckerInterface::class);
        $checker->method('check')->willReturnCallback(static fn (ProductVariantInterface $v) => $availability[$v->getCode()]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('validate')->willReturn(new ConstraintViolationList(
            null === $violation ? [] : [new ConstraintViolation($violation, null, [], null, '', null)],
        ));

        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(function (object $message): Envelope {
            $this->dispatched[] = $message;

            return new Envelope($message);
        });

        return new WishlistCartAdder($orders, $checker, $validator, $bus);
    }

    private function wishlist(): Wishlist
    {
        $wishlist = new Wishlist();
        foreach ([1 => ['A', 2], 2 => ['B', 1], 3 => ['C', 1]] as $id => [$code, $qty]) {
            $variant = $this->createStub(ProductVariantInterface::class);
            $variant->method('getCode')->willReturn($code);
            $item = new WishlistItem();
            (new \ReflectionProperty($item, 'id'))->setValue($item, $id);
            $item->setVariant($variant);
            $item->setQuantity($qty);
            $wishlist->addItem($item);
        }

        return $wishlist;
    }

    public function testAllAddsOnlyPurchasableRowsAndReportsTheOthers(): void
    {
        $result = $this->adder(['A' => WishlistAvailability::Available, 'B' => WishlistAvailability::OutOfStock, 'C' => WishlistAvailability::Unavailable])
            ->add($this->wishlist(), $this->channel, 'cart', null);

        self::assertSame([['itemId' => 1, 'variantCode' => 'A', 'quantity' => 2]], $result->added);
        self::assertSame(['out_of_stock', 'unavailable'], array_column($result->skipped, 'reason'));
        self::assertCount(1, $this->dispatched);
        self::assertEquals(new AddItemToCart('cart', 'A', 2), $this->dispatched[0]);
    }

    public function testSelectedRowsOnlyAndUnknownIdIsReported(): void
    {
        $all = array_fill_keys(['A', 'B', 'C'], WishlistAvailability::Available);
        $result = $this->adder($all)->add($this->wishlist(), $this->channel, 'cart', [2, 99]);

        self::assertSame([2], array_column($result->added, 'itemId'));
        self::assertSame('not_in_wishlist', $result->skipped[0]['reason']);
        self::assertSame(99, $result->skipped[0]['itemId']);
    }

    public function testCartRuleViolationSkipsTheRowWithoutDispatch(): void
    {
        $result = $this->adder(['A' => WishlistAvailability::Available, 'B' => WishlistAvailability::Available, 'C' => WishlistAvailability::Available], true, 'Not enough stock')
            ->add($this->wishlist(), $this->channel, 'cart', [1]);

        self::assertSame([], $result->added);
        self::assertSame('rejected', $result->skipped[0]['reason']);
        self::assertSame('Not enough stock', $result->skipped[0]['message']);
        self::assertSame([], $this->dispatched);
    }

    public function testMissingCartOrOtherChannelIsNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->adder([], false)->add($this->wishlist(), $this->channel, 'nope', null);
    }
}
