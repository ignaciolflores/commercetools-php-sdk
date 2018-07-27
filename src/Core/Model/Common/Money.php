<?php
/**
 * @author @jenschude <jens.schulze@commercetools.de>
 * @created: 04.02.15, 16:44
 */

namespace Commercetools\Core\Model\Common;

/**
 * @package Commercetools\Core\Model\Common
 * @link https://docs.commercetools.com/http-api-types.html#money
 * @method string getCurrencyCode()
 * @method int getCentAmount()
 * @method Money setCurrencyCode(string $currencyCode = null)
 * @method Money setCentAmount(int $centAmount = null)
 * @method string getType()
 * @method Money setType(string $type = null)
 * @method string getFractionDigits()
 * @method Money setFractionDigits(string $fractionDigits = null)
 */
class Money extends JsonObject
{
    const CURRENCY_CODE = 'currencyCode';
    const CENT_AMOUNT = 'centAmount';
    const FRACTION_DIGITS = 'fractionDigits';
    const TYPE_CENT_PRECISION = 'centPrecision';
    const TYPE_HIGH_PRECISION = 'highPrecision';

    public function fieldDefinitions()
    {
        return [
            static::CURRENCY_CODE => [self::TYPE => 'string'],
            static::CENT_AMOUNT => [self::TYPE => 'int'],
        ];
    }

    /**
     * @param array $data
     * @param Context|callable $context
     * @return Money
     */
    public static function fromArray(array $data, $context = null)
    {
        if (get_called_class() == Money::class && isset($data[static::TYPE])) {
            $className = static::moneyType($data[static::TYPE]);
            if (class_exists($className)) {
                return new $className($data, $context);
            }
        }
        return new static($data, $context);
    }

    protected static function moneyType($type)
    {
        $types = [
            static::TYPE_CENT_PRECISION => CentPrecisionMoney::class,
            static::TYPE_HIGH_PRECISION => HighPrecisionMoney::class,
        ];
        return isset($types[$type]) ? $types[$type] : Money::class;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getContext()->getCurrencyFormatter()->format($this->getCentAmount(), $this->getCurrencyCode());
    }

    /**
     * @param $currencyCode
     * @param $centAmount
     * @param Context|callable $context
     * @return Money
     */
    public static function ofCurrencyAndAmount($currencyCode, $centAmount, $context = null)
    {
        $money = static::of($context);
        return $money->setCurrencyCode($currencyCode)->setCentAmount($centAmount);
    }
}
