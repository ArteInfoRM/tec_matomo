<?php
/**
 * Purchase eligibility and minimal ecommerce payload validation.
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license See the module LICENSE file.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Central purchase contract, shared by the browser and server delivery paths. */
class TecMatomoPurchasePolicy
{
    /** Reject unsupported state identifiers before using configuration. */
    public static function stateIds($values)
    {
        if (!is_array($values) || count($values) > 200) {
            throw new InvalidArgumentException('Invalid qualifying order states.');
        }
        $ids = [];
        foreach ($values as $value) {
            if ((!is_int($value) && !is_string($value))
                || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)
                || (float) $value > 2147483647) {
                throw new InvalidArgumentException('Invalid qualifying order states.');
            }
            $ids[] = (int) $value;
        }

        return array_values(array_unique($ids));
    }

    /** Error states cannot qualify, including after an automatic backorder change. */
    public static function eligible($state, array $allowed, array $denied)
    {
        return $state > 0 && in_array((int) $state, $allowed, true)
            && !in_array((int) $state, $denied, true);
    }

    /** Accept configured HTTP endpoints without URL credentials or query injection. */
    public static function baseUrl($value)
    {
        if (!is_string($value) || strlen($value) > 2048 || preg_match('/[\x00-\x20\x7f]/', $value)) {
            throw new InvalidArgumentException('Invalid Matomo tracking URL.');
        }
        $parts = parse_url($value);
        if (!$parts || empty($parts['host']) || empty($parts['scheme'])
            || !in_array(strtolower($parts['scheme']), ['https', 'http'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Invalid Matomo tracking URL.');
        }

        return rtrim($value, '/') . '/';
    }

    /** Return a finite nonnegative amount; total_paid is not proof of payment. */
    public static function amount($value)
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0 || (float) $value > 1000000000000) {
            throw new InvalidArgumentException('Invalid purchase amount.');
        }

        return (float) $value;
    }

    /** Build a bounded payload from stored order lines, never a product presenter. */
    public static function payload($order, array $details)
    {
        if (count($details) > 1000 || !is_string($order->reference)
            || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $order->reference)) {
            throw new InvalidArgumentException('Invalid purchase data.');
        }
        $items = [];
        foreach ($details as $detail) {
            $ids = self::stateIds([$detail['product_id']]);
            $attribute = isset($detail['product_attribute_id']) ? $detail['product_attribute_id'] : 0;
            if ((!is_int($attribute) && !is_string($attribute)) || !preg_match('/^[0-9]{1,10}$/D', (string) $attribute)
                || (float) $attribute > 2147483647) {
                throw new InvalidArgumentException('Invalid purchase combination.');
            }
            $quantity = self::stateIds([$detail['product_quantity']]);
            $name = isset($detail['product_name']) ? $detail['product_name'] : '';
            if (!is_string($name) || strlen($name) > 1024 || preg_match('/[\x00-\x1f\x7f]/', $name)
                || preg_match('//u', $name) !== 1) {
                throw new InvalidArgumentException('Invalid purchase product name.');
            }
            $price = isset($detail['unit_price_tax_incl']) ? $detail['unit_price_tax_incl'] : $detail['product_price'];
            $category = isset($detail['product_category']) ? $detail['product_category'] : '';
            if (!is_string($category) || strlen($category) > 1024 || preg_match('//u', $category) !== 1
                || preg_match('/[\x00-\x1f\x7f]/', $category)) {
                throw new InvalidArgumentException('Invalid purchase product category.');
            }
            $items[] = [$ids[0] . 'v' . (int) $attribute, strip_tags($name), strip_tags($category), self::amount($price), $quantity[0]];
        }
        $total = self::amount($order->total_paid);
        $subtotal = self::amount($order->total_paid_tax_excl);

        return [
            'ec_id' => $order->reference,
            'revenue' => $total,
            'ec_st' => isset($order->total_products) ? self::amount($order->total_products) : max(0, $subtotal - self::amount($order->total_shipping)),
            'ec_tx' => max(0, $total - $subtotal),
            'ec_sh' => self::amount($order->total_shipping),
            'ec_dt' => self::amount($order->total_discounts),
            'ec_items' => $items,
        ];
    }
}
