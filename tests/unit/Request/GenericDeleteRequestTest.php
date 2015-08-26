<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Request\CartDiscounts;


use Commercetools\Core\Request\AbstractApiRequest;
use Commercetools\Core\RequestTestCase;

class GenericDeleteRequestTest extends RequestTestCase
{
    /**
     * @param $className
     * @param array $args
     * @return AbstractApiRequest
     */
    protected function getRequest($className, array $args = [])
    {
        $request = call_user_func_array($className . '::ofIdAndVersion', $args);

        return $request;
    }

    public function mapResultProvider()
    {
        return [
            [
                '\Commercetools\Core\Request\CartDiscounts\CartDiscountDeleteRequest',
                '\Commercetools\Core\Model\CartDiscount\CartDiscount',
            ],
            [
                '\Commercetools\Core\Request\Carts\CartDeleteRequest',
                '\Commercetools\Core\Model\Cart\Cart',
            ],
            [
                '\Commercetools\Core\Request\Categories\CategoryDeleteRequest',
                '\Commercetools\Core\Model\Category\Category',
            ],
            [
                '\Commercetools\Core\Request\Channels\ChannelDeleteRequest',
                '\Commercetools\Core\Model\Channel\Channel',
            ],
            [
                '\Commercetools\Core\Request\CustomerGroups\CustomerGroupDeleteRequest',
                '\Commercetools\Core\Model\CustomerGroup\CustomerGroup',
            ],
            [
                '\Commercetools\Core\Request\Customers\CustomerDeleteRequest',
                '\Commercetools\Core\Model\Customer\Customer',
            ],
            [
                '\Commercetools\Core\Request\DiscountCodes\DiscountCodeDeleteRequest',
                '\Commercetools\Core\Model\DiscountCode\DiscountCode',
            ],
            [
                '\Commercetools\Core\Request\Inventory\InventoryDeleteRequest',
                '\Commercetools\Core\Model\Inventory\InventoryEntry',
            ],
            [
                '\Commercetools\Core\Request\ProductDiscounts\ProductDiscountDeleteRequest',
                '\Commercetools\Core\Model\ProductDiscount\ProductDiscount',
            ],
            [
                '\Commercetools\Core\Request\Products\ProductDeleteRequest',
                '\Commercetools\Core\Model\Product\Product',
            ],
            [
                '\Commercetools\Core\Request\ProductTypes\ProductTypeDeleteRequest',
                '\Commercetools\Core\Model\ProductType\ProductType',
            ],
            [
                '\Commercetools\Core\Request\States\StateDeleteRequest',
                '\Commercetools\Core\Model\State\State',
            ],
            [
                '\Commercetools\Core\Request\TaxCategories\TaxCategoryDeleteRequest',
                '\Commercetools\Core\Model\TaxCategory\TaxCategory',
            ],
            [
                '\Commercetools\Core\Request\Zones\ZoneDeleteRequest',
                '\Commercetools\Core\Model\Zone\Zone',
            ],
        ];
    }

    /**
     * @dataProvider mapResultProvider
     * @param $requestClass
     * @param $resultClass
     */
    public function testMapResult($requestClass, $resultClass)
    {
        $result = $this->mapResult($requestClass, ['id', 1]);
        $this->assertInstanceOf($resultClass, $result);
    }

    /**
     * @dataProvider mapResultProvider
     * @param $requestClass
     * @param $resultClass
     */
    public function testMapEmptyResult($requestClass, $resultClass)
    {
        $result = $this->mapEmptyResult($requestClass, ['id', 1]);
        $this->assertNull($result);
    }
}