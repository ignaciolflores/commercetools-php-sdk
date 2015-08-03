<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Core\Model\Cart;

use Commercetools\Core\Model\Common\JsonObject;
use Commercetools\Core\Model\Common\Money;

/**
 * @package Commercetools\Core\Model\Cart
 * @apidoc http://dev.sphere.io/http-api-projects-carts.html#discounted-line-item-price
 * @method Money getValue()
 * @method DiscountedLineItemPrice setValue(Money $value = null)
 * @method DiscountedLineItemPortionCollection getIncludedDiscounts()
 * @method DiscountedLineItemPrice setIncludedDiscounts(DiscountedLineItemPortionCollection $includedDiscounts = null)
 */
class DiscountedLineItemPrice extends JsonObject
{
    public function getFields()
    {
        return [
            'value' => [static::TYPE => '\Commercetools\Core\Model\Common\Money'],
            'includedDiscounts' => [
                static::TYPE => '\Commercetools\Core\Model\Cart\DiscountedLineItemPortionCollection'
            ]
        ];
    }
}
