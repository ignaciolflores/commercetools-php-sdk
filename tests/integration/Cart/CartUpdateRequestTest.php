<?php
/**
 * @author @jayS-de <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Cart;

use Commercetools\Core\ApiTestCase;
use Commercetools\Core\Model\Cart\CartDraft;
use Commercetools\Core\Model\Cart\CustomLineItem;
use Commercetools\Core\Model\Cart\CustomLineItemCollection;
use Commercetools\Core\Model\Cart\CustomLineItemDraft;
use Commercetools\Core\Model\Cart\CustomLineItemDraftCollection;
use Commercetools\Core\Model\Common\Address;
use Commercetools\Core\Model\Common\LocalizedString;
use Commercetools\Core\Model\Common\Money;
use Commercetools\Core\Model\Common\PriceDraft;
use Commercetools\Core\Model\CustomField\CustomFieldObjectDraft;
use Commercetools\Core\Model\CustomField\FieldContainer;
use Commercetools\Core\Model\ShippingMethod\ShippingRate;
use Commercetools\Core\Request\Carts\CartByIdGetRequest;
use Commercetools\Core\Request\Carts\CartCreateRequest;
use Commercetools\Core\Request\Carts\CartDeleteRequest;
use Commercetools\Core\Request\Carts\CartUpdateRequest;
use Commercetools\Core\Request\Carts\Command\CartAddCustomLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddDiscountCodeAction;
use Commercetools\Core\Request\Carts\Command\CartAddLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartAddPaymentAction;
use Commercetools\Core\Request\Carts\Command\CartChangeLineItemQuantityAction;
use Commercetools\Core\Request\Carts\Command\CartRecalculateAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveCustomLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveDiscountCodeAction;
use Commercetools\Core\Request\Carts\Command\CartRemoveLineItemAction;
use Commercetools\Core\Request\Carts\Command\CartRemovePaymentAction;
use Commercetools\Core\Request\Carts\Command\CartSetBillingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetCountryAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerEmailAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomerIdAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemCustomFieldAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomLineItemCustomTypeAction;
use Commercetools\Core\Request\Carts\Command\CartSetCustomShippingMethodAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemCustomFieldAction;
use Commercetools\Core\Request\Carts\Command\CartSetLineItemCustomTypeAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingAddressAction;
use Commercetools\Core\Request\Carts\Command\CartSetShippingMethodAction;
use Commercetools\Core\Request\Customers\CustomerLoginRequest;
use Commercetools\Core\Request\CustomField\Command\SetCustomFieldAction;
use Commercetools\Core\Request\CustomField\Command\SetCustomTypeAction;
use Commercetools\Core\Request\Products\Command\ProductChangePriceAction;
use Commercetools\Core\Request\Products\Command\ProductPublishAction;
use Commercetools\Core\Request\Products\ProductUpdateRequest;

class CartUpdateRequestTest extends ApiTestCase
{
    public function testLineItemsOfRemovedProducts()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertFalse($response->isError());

        $this->deleteProduct();

        $request = CartByIdGetRequest::ofId($cart->getId());
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->assertNotEmpty($cart->getLineItems());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRecalculateAction::of())
        ;
        $response = $request->executeWithClient($this->getClient());
        $result = $request->mapResponse($response);
        $this->assertTrue($response->isError());

        $request = CartByIdGetRequest::ofId($cart->getId());
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);

        $lineItem = $cart->getLineItems()->current()->getId();
        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRemoveLineItemAction::ofLineItemId($lineItem))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertFalse($response->isError());
        $this->assertEmpty($cart->getLineItems());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRecalculateAction::of())
        ;
        $response = $request->executeWithClient($this->getClient());
        $result = $request->mapResponse($response);
        $this->deleteRequest->setVersion($result->getVersion());
        $this->assertFalse($response->isError());
    }

    public function testLineItem()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($product->getId(), $cart->getLineItems()->current()->getProductId());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartChangeLineItemQuantityAction::ofLineItemIdAndQuantity($cart->getLineItems()->current()->getId(), 2)
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame(2, $cart->getLineItems()->current()->getQuantity());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartRemoveLineItemAction::ofLineItemId($cart->getLineItems()->current()->getId())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertCount(0, $cart->getLineItems());
    }

    public function testCustomLineItem()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $name = LocalizedString::ofLangAndText('en', 'test-' . $this->getTestRun());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddCustomLineItemAction::ofNameQuantityMoneySlugAndTaxCategory(
                    $name,
                    1,
                    Money::ofCurrencyAndAmount('EUR', 100),
                    $name->en,
                    $this->getTaxCategory()->getReference()
                )
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($name->en, $cart->getCustomLineItems()->current()->getName()->en);

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartRemoveCustomLineItemAction::ofCustomLineItemId($cart->getCustomLineItems()->current()->getId())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertCount(0, $cart->getLineItems());
    }

    public function testCustomLineItemMerge()
    {
        $name = LocalizedString::ofLangAndText('en', 'test-' . $this->getTestRun());
        $anonName = LocalizedString::ofLangAndText('en', 'anon-' . $this->getTestRun());
        $customerDraft = $this->getCustomerDraft();
        $customer = $this->getCustomer($customerDraft);

        $draft = $this->getDraft();
        $draft->setCustomerId($customer->getId());
        $customerCart = $this->createCart($draft);

        $anonCartDraft = $this->getDraft();
        $anonCartDraft->setCustomLineItems(
            CustomLineItemDraftCollection::of()
                ->add(
                    CustomLineItemDraft::of()
                        ->setName($anonName)
                        ->setQuantity(1)
                        ->setMoney(Money::ofCurrencyAndAmount('EUR', 100))
                        ->setSlug($anonName->en)
                        ->setTaxCategory($this->getTaxCategory()->getReference())
                )
        );
        $request = CartCreateRequest::ofDraft($anonCartDraft);
        $response = $request->executeWithClient($this->getClient());
        $anonCart = $request->mapResponse($response);

        $this->assertNotSame($customerCart->getId(), $anonCart->getId());
        $this->cleanupRequests[] = CartDeleteRequest::ofIdAndVersion($anonCart->getId(), $anonCart->getVersion());

        $loginRequest = CustomerLoginRequest::ofEmailAndPassword(
            $customerDraft->getEmail(),
            $customerDraft->getPassword(),
            $anonCart->getId()
        );
        $response = $loginRequest->executeWithClient($this->getClient());
        $result = $loginRequest->mapResponse($response);
        $loginCart = $result->getCart();

        if ($loginCart->getCustomLineItems()->count() == 0) {
            $this->markTestSkipped(
                'Merging custom line items from anon carts to customer cart not yet supported by API.'
            );
        }
        $this->assertCount(2, $loginCart->getCustomLineItems());
    }

    public function testCustomerEmail()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $email = 'test-' . $this->getTestRun() . '@example.com';

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartSetCustomerEmailAction::of()->setEmail($email))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($email, $cart->getCustomerEmail());
    }

    public function testShippingAddress()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $address = Address::of()
            ->setFirstName('test-' . $this->getTestRun() . '@example.com')
            ->setCountry('DE')
        ;

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartSetShippingAddressAction::of()->setAddress($address))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($address->getFirstName(), $cart->getShippingAddress()->getFirstName());
    }

    public function testBillingAddress()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $address = Address::of()
            ->setFirstName('test-' . $this->getTestRun() . '@example.com')
            ->setCountry('DE')
        ;

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartSetBillingAddressAction::of()->setAddress($address))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($address->getFirstName(), $cart->getBillingAddress()->getFirstName());
    }

    public function testSetCountry()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $country = 'UK';

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartSetCountryAction::of()->setCountry($country))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($country, $cart->getCountry());
    }

    public function testSetShippingMethod()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $shippingMethod = $this->getShippingMethod();
        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetShippingAddressAction::of()->setAddress(
                    Address::of()->setCountry('DE')->setState($this->getRegion())
                )
            )
            ->addAction(
                CartSetShippingMethodAction::of()->setShippingMethod($shippingMethod->getReference())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($shippingMethod->getName(), $cart->getShippingInfo()->getShippingMethodName());
    }

    public function testSetCustomShippingMethod()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $shippingMethod = 'test-' . $this->getTestRun();
        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetShippingAddressAction::of()->setAddress(
                    Address::of()->setCountry('DE')->setState($this->getRegion())
                )
            )
            ->addAction(
                CartSetCustomShippingMethodAction::of()->setShippingMethodName($shippingMethod)
                    ->setShippingRate(ShippingRate::of()->setPrice(Money::ofCurrencyAndAmount('EUR', 100)))
                    ->setTaxCategory($this->getTaxCategory()->getReference())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($shippingMethod, $cart->getShippingInfo()->getShippingMethodName());
    }

    public function testCustomerId()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $customer = $this->getCustomer();
        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartSetCustomerIdAction::of()->setCustomerId($customer->getId()))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($customer->getId(), $cart->getCustomerId());
    }

    public function testRecalculate()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $request = ProductUpdateRequest::ofIdAndVersion($product->getId(), $product->getVersion())
            ->addAction(
                ProductChangePriceAction::ofPriceIdAndPrice(
                    $variant->getPrices()->current()->getId(),
                    PriceDraft::ofMoney(Money::ofCurrencyAndAmount('EUR', 200))
                )
            )
            ->addAction(ProductPublishAction::of())
        ;
        $response = $request->executeWithClient($this->getClient());
        $this->product = $request->mapResponse($response);

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRecalculateAction::of())
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame(200, $cart->getTotalPrice()->getCentAmount());
    }

    public function testCustomType()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'order');

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                SetCustomTypeAction::ofTypeKey($type->getKey())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($type->getId(), $cart->getCustom()->getType()->getId());
    }

    public function testCustomField()
    {
        $type = $this->getType('key-' . $this->getTestRun(), 'order');
        $draft = $this->getDraft();
        $draft->setCustom(CustomFieldObjectDraft::ofType($type->getReference()));
        $cart = $this->createCart($draft);


        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                SetCustomFieldAction::ofName('testField')
                    ->setValue($this->getTestRun())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($this->getTestRun(), $cart->getCustom()->getFields()->getTestField());
    }

    public function testLineItemCustomType()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'line-item');
        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetLineItemCustomTypeAction::ofTypeKey($type->getKey())
                    ->setLineItemId($cart->getLineItems()->current()->getId())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($type->getId(), $cart->getLineItems()->current()->getCustom()->getType()->getId());
    }

    public function testAddLineItemWithCustomType()
    {
        $this->markTestSkipped('Must be fixed by API');

        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'line-item');
        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
                    ->setCustom(
                        CustomFieldObjectDraft::ofTypeKey($type->getKey())
                            ->setFields(
                                FieldContainer::of()
                                    ->setTestField('1')
                            )
                    )
            )
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
                    ->setCustom(
                        CustomFieldObjectDraft::ofTypeKey($type->getKey())
                            ->setFields(
                                FieldContainer::of()
                                    ->setTestField('2')
                            )
                    )
            )
        ;

        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertCount(2, $cart->getLineItems());
    }

    public function testCustomLineItemCustomType()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'custom-line-item');

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddCustomLineItemAction::ofNameQuantityMoneySlugAndTaxCategory(
                    LocalizedString::ofLangAndText('en', 'item-' . $this->getTestRun()),
                    1,
                    Money::ofCurrencyAndAmount('EUR', 100),
                    'item-' . $this->getTestRun(),
                    $this->getTaxCategory()->getReference()
                )
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetCustomLineItemCustomTypeAction::ofTypeKey($type->getKey())
                    ->setCustomLineItemId($cart->getCustomLineItems()->current()->getId())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($type->getId(), $cart->getCustomLineItems()->current()->getCustom()->getType()->getId());
    }

    public function testLineItemCustomField()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'line-item');
        $product = $this->getProduct();
        $variant = $product->getMasterData()->getCurrent()->getMasterVariant();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddLineItemAction::ofProductIdVariantIdAndQuantity($product->getId(), $variant->getId(), 1)
                    ->setCustom(CustomFieldObjectDraft::ofType($type->getReference()))
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetLineItemCustomFieldAction::ofName('testField')
                    ->setLineItemId($cart->getLineItems()->current()->getId())
                    ->setValue($this->getTestRun())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame(
            $this->getTestRun(),
            $cart->getLineItems()->current()->getCustom()->getFields()->getTestField()
        );
    }

    public function testCustomLineItemCustomField()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $type = $this->getType('key-' . $this->getTestRun(), 'custom-line-item');

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartAddCustomLineItemAction::ofNameQuantityMoneySlugAndTaxCategory(
                    LocalizedString::ofLangAndText('en', 'item-' . $this->getTestRun()),
                    1,
                    Money::ofCurrencyAndAmount('EUR', 100),
                    'item-' . $this->getTestRun(),
                    $this->getTaxCategory()->getReference()
                )
                    ->setCustom(CustomFieldObjectDraft::ofType($type->getReference()))
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(
                CartSetCustomLineItemCustomFieldAction::ofName('testField')
                    ->setCustomLineItemId($cart->getCustomLineItems()->current()->getId())
                    ->setValue($this->getTestRun())
            )
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame(
            $this->getTestRun(),
            $cart->getCustomLineItems()->current()->getCustom()->getFields()->getTestField()
        );
    }

    public function testPayment()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $payment = $this->getPayment();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartAddPaymentAction::of()->setPayment($payment->getReference()))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($payment->getId(), $cart->getPaymentInfo()->getPayments()->current()->getId());
        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRemovePaymentAction::of()->setPayment($payment->getReference()))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());
        $this->assertNull($cart->getPaymentInfo());
    }

    public function testDiscountCode()
    {
        $draft = $this->getDraft();
        $cart = $this->createCart($draft);

        $discountCode = $this->getDiscountCode();

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartAddDiscountCodeAction::ofCode($discountCode->getCode()))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());

        $this->assertSame($discountCode->getId(), $cart->getDiscountCodes()->current()->getDiscountCode()->getId());

        $request = CartUpdateRequest::ofIdAndVersion($cart->getId(), $cart->getVersion())
            ->addAction(CartRemoveDiscountCodeAction::ofDiscountCode($discountCode->getReference()))
        ;
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);
        $this->deleteRequest->setVersion($cart->getVersion());
        $this->assertEmpty($cart->getDiscountCodes());
    }

    /**
     * @return CartDraft
     */
    protected function getDraft()
    {
        $draft = CartDraft::ofCurrency('EUR')->setCountry('DE');

        return $draft;
    }

    protected function createCart(CartDraft $draft)
    {
        $request = CartCreateRequest::ofDraft($draft);
        $response = $request->executeWithClient($this->getClient());
        $cart = $request->mapResponse($response);

        $this->deleteRequest = CartDeleteRequest::ofIdAndVersion($cart->getId(), $cart->getVersion());
        $this->cleanupRequests[] = $this->deleteRequest;

        return $cart;
    }
}
