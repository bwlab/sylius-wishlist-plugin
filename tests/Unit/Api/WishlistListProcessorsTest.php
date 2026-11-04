<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Tests\Unit\Api;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Bwlab\SyliusWishlistPlugin\Api\Error\WishlistErrorMapper;
use Bwlab\SyliusWishlistPlugin\Api\Factory\WishlistViewFactory;
use Bwlab\SyliusWishlistPlugin\Api\Input\CreateWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Input\RenameWishlistList;
use Bwlab\SyliusWishlistPlugin\Api\Processor\CreateWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\DeleteWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Api\Processor\RenameWishlistListProcessor;
use Bwlab\SyliusWishlistPlugin\Checker\VariantAvailabilityCheckerInterface;
use Bwlab\SyliusWishlistPlugin\Entity\Wishlist;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistNameException;
use Bwlab\SyliusWishlistPlugin\Exception\WishlistOperationException;
use Bwlab\SyliusWishlistPlugin\Resolver\CurrentWishlistResolverInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Create / rename / delete of a list: ownership (404), mapped errors, the default list is never deleted. */
final class WishlistListProcessorsTest extends TestCase
{
    /** @var CurrentWishlistResolverInterface&Stub */
    private CurrentWishlistResolverInterface $resolver;
    private WishlistViewFactory $views;
    private WishlistErrorMapper $errors;

    protected function setUp(): void
    {
        $this->resolver = $this->createStub(CurrentWishlistResolverInterface::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id) => 'T:' . $id);
        $locale = $this->createStub(LocaleContextInterface::class);
        $locale->method('getLocaleCode')->willReturn('it');
        $this->views = new WishlistViewFactory(
            $this->createStub(VariantAvailabilityCheckerInterface::class),
            $this->createStub(ProductVariantPricesCalculatorInterface::class),
            '/api/v2/shop',
            $translator,
            $locale,
        );
        $this->errors = new WishlistErrorMapper($translator, $locale);
    }

    private function list(int $id, string $name, bool $default = false): Wishlist
    {
        $list = new Wishlist();
        (new \ReflectionProperty($list, 'id'))->setValue($list, $id);
        $list->setName($name);
        $list->setDefault($default);

        return $list;
    }

    public function testCreateReturnsTheNewList(): void
    {
        $this->resolver->method('create')->willReturn($this->list(9, 'Natale'));
        $input = new CreateWishlistList();
        $input->name = 'Natale';

        $view = (new CreateWishlistListProcessor($this->resolver, $this->views, $this->errors))->process($input, new Post());

        self::assertSame(9, $view->id);
        self::assertSame('Natale', $view->name);
        self::assertFalse($view->isDefault);
    }

    public function testCreateWithBadNameIs422(): void
    {
        $this->resolver->method('create')->willThrowException(new WishlistNameException(WishlistNameException::BLANK));
        $input = new CreateWishlistList();
        $input->name = '  ';

        $this->expectException(UnprocessableEntityHttpException::class);
        (new CreateWishlistListProcessor($this->resolver, $this->views, $this->errors))->process($input, new Post());
    }

    public function testRenameOfAForeignOrMissingListIs404(): void
    {
        $this->resolver->method('findOwned')->willReturn(null);
        $input = new RenameWishlistList();
        $input->name = 'X';

        $this->expectException(NotFoundHttpException::class);
        (new RenameWishlistListProcessor($this->resolver, $this->views, $this->errors))->process($input, new Patch(), ['id' => 5]);
    }

    public function testRenameDuplicateIs422(): void
    {
        $this->resolver->method('findOwned')->willReturn($this->list(5, 'Natale'));
        $this->resolver->method('rename')->willThrowException(new WishlistNameException(WishlistNameException::DUPLICATE, ['%name%' => 'X']));
        $input = new RenameWishlistList();
        $input->name = 'X';

        $this->expectException(UnprocessableEntityHttpException::class);
        (new RenameWishlistListProcessor($this->resolver, $this->views, $this->errors))->process($input, new Patch(), ['id' => 5]);
    }

    public function testDeleteOfAForeignOrMissingListIs404(): void
    {
        $this->resolver->method('findOwned')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        (new DeleteWishlistListProcessor($this->resolver, $this->errors))->process(null, new Delete(), ['id' => 5]);
    }

    public function testDeleteOfTheDefaultListIs409(): void
    {
        $this->resolver->method('findOwned')->willReturn($this->list(1, 'My Favorites', true));
        $this->resolver->method('delete')->willThrowException(new WishlistOperationException(WishlistOperationException::DELETE_DEFAULT));

        $this->expectException(ConflictHttpException::class);
        (new DeleteWishlistListProcessor($this->resolver, $this->errors))->process(null, new Delete(), ['id' => 1]);
    }

    public function testDefaultListNameIsTranslatedOthersKeepTheirName(): void
    {
        self::assertSame('T:default_list_name', $this->views->listName($this->list(1, 'My Favorites', true)));
        self::assertSame('T:default_list_name', $this->views->listName(null));
        self::assertSame('Natale', $this->views->listName($this->list(2, 'Natale')));
    }
}
