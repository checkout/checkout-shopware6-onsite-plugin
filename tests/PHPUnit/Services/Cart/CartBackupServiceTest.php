<?php declare(strict_types=1);

namespace CheckoutCom\Shopware6\Tests\Services\Cart;

use CheckoutCom\Shopware6\Exception\DirectCartInvalidException;
use CheckoutCom\Shopware6\Service\Cart\CartBackupService;
use CheckoutCom\Shopware6\Tests\Traits\ContextTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartPersisterInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService as CoreCartService;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartBackupServiceTest extends TestCase
{
    use ContextTrait;

    /**
     * @var MockObject|CoreCartService
     */
    private $coreCartService;

    /**
     * @var CartPersisterInterface|MockObject
     */
    private $cartPersister;

    private CartBackupService $cartBackupService;

    /**
     * @var MockObject|SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->coreCartService = $this->createMock(CoreCartService::class);
        $this->cartPersister = $this->createMock(CartPersisterInterface::class);
        $this->salesChannelContext = $this->getSaleChannelContext($this);

        $this->cartBackupService = new CartBackupService(
            $this->coreCartService,
            $this->cartPersister,
        );
    }

    public function testGetDecoratedThrowException(): void
    {
        static::expectException(DecorationPatternException::class);
        $this->cartBackupService->getDecorated();
    }

    public function testCreateNewDirectTokenCart(): void
    {
        $directCart = new Cart('foo', 'bar');

        $this->coreCartService->expects(static::once())
            ->method('createNew')
            ->willReturn($directCart);

        $this->coreCartService->expects(static::once())
            ->method('recalculate')
            ->willReturn($directCart);

        $expectCart = $this->cartBackupService->createNewDirectTokenCart($this->salesChannelContext);

        static::assertInstanceOf(Cart::class, $expectCart);
    }

    public function testCopyOriginCartToCartContext(): void
    {
        $directCart = new Cart('foo', 'bar');

        $this->coreCartService->expects(static::once())
            ->method('getCart')
            ->willReturn($directCart);

        $this->cloneCartAndSave($directCart);

        $expectCart = $this->cartBackupService->copyOriginCartToCartContext($this->salesChannelContext);

        static::assertInstanceOf(Cart::class, $expectCart);
    }

    /**
     * @dataProvider copyDirectCartToCartContextProvider
     */
    public function testCopyDirectCartToCartContext(bool $hasLineItems): void
    {
        $directCart = new Cart('foo', 'bar');

        $this->coreCartService->expects(static::once())
            ->method('getCart')
            ->willReturn($directCart);

        if ($hasLineItems) {
            $directCart->setLineItems(
                new LineItemCollection([new LineItem('foo', 'bar')])
            );
            $this->cloneCartAndSave($directCart);
        } else {
            static::expectException(DirectCartInvalidException::class);
        }

        $expectCart = $this->cartBackupService->copyDirectCartToCartContext(
            'foo',
            $this->salesChannelContext
        );

        static::assertInstanceOf(Cart::class, $expectCart);
    }

    public function testDeleteCart(): void
    {
        $this->cartPersister->expects(static::once())
            ->method('delete');

        $this->cartBackupService->deleteCart('foo', $this->salesChannelContext);
    }

    public function copyDirectCartToCartContextProvider(): array
    {
        return [
            'Test is empty line items' => [
                false,
            ],
            'Test copy success' => [
                true,
            ],
        ];
    }

    private function cloneCartAndSave(Cart $cart): void
    {
        $this->coreCartService->expects(static::once())
            ->method('createNew')
            ->willReturn($cart);

        $this->coreCartService->expects(static::once())
            ->method('setCart');

        $this->coreCartService->expects(static::once())
            ->method('recalculate')
            ->willReturn($cart);
    }
}
