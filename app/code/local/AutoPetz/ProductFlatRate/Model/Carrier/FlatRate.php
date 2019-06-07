<?php

/**
 * Product flat rate carrier model
 *
 * @category    Mage
 * @package     AutoPetz_ProductFlatRate
 */

use AutoPetz_ProductFlatRate_Helper_Definitions as Definitions;

class AutoPetz_ProductFlatRate_Model_Carrier_FlatRate
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'autopetz_flatratebyproduct';

    protected $_isFixed = true;

    /**
     * Collect Flat Rate Per Item shipping rates.
     *
     * @param \Mage_Shipping_Model_Rate_Request $request
     *
     * @return false|\Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active') || empty($request->getAllItems())) {
            return false;
        }
        $price = 0.00;
        $result = Mage::getModel('shipping/rate_result');

        // Add the cost of each item.
        foreach ($request->getAllItems() as $item) {
            /* @var \Mage_Sales_Model_Quote_Item $item */

            // Skip virtual & children.
            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                continue;
            }

            // Get the shipping cost for the product.
            $itemPrice = $this->getQuoteItemShippingPrice($item);

            // Cannot ship with this method if 1 or more products in the quote
            // do not return a flat rate cost.
            if($itemPrice === false) return false;

            // Add price to rate.
            $price += $itemPrice * $item->getQty();
        }

        // Setup our rate result.
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod('flatrate');
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($price);
        $method->setCost($price);
        $result->append($method);

        return $result;
    }

    /**
     * Get the shipping cost of a quote item.
     *
     * @param \Mage_Sales_Model_Quote_Item $quoteItem
     *
     * @return float
     */
    public function getQuoteItemShippingPrice(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        $price = 0.00;

        // Calculate costs based on product type.
        switch ($quoteItem->getProductType()) {

            // Cost depends on if the bundle ships together.
            case 'bundle':
                {
                    if ($quoteItem->isShipSeparately()) {
                        foreach ($quoteItem->getChildren() as $child) {
                            /* @var \Mage_Sales_Model_Quote_Item $child */
                            $itemPrice = $this->getProductShippingPrice($child->getProduct());

                            // Have to return false if any bundled item does not support
                            // flat rate shipping costs.
                            if($itemPrice === false) return false;

                            // Add to total.
                            $price += $itemPrice;
                        }
                    } else {
                        $itemPrice = $this->getProductShippingPrice($quoteItem->getProduct());

                        // Return false if the item doesn't support product flat-rate shipping.
                        if($itemPrice === false) return false;

                        // Add to total.
                        $price += $this->getProductShippingPrice($quoteItem->getProduct());
                    }
                    break;
                }

            // Configurable flat-rate price resolve order:
            // Check simple product => check configurable product => try to apply default.
            case 'configurable':
                {
                    $child = array_shift($quoteItem->getChildren());
                    $itemPrice = $this->getProductShippingPrice($child->getProduct(), false);
                    if ($itemPrice !== false) {
                        $price += $itemPrice;
                    } else {
                        $itemPrice = $this->getProductShippingPrice($quoteItem->getProduct());

                        // Product doesn't support flat-rate shipping
                        if($itemPrice === false) return false;

                        // Add to total
                        $price += $itemPrice;
                    }
                    break;
                }

            // Simple products & everything else.
            default:
                {
                    $itemPrice = $this->getProductShippingPrice($quoteItem->getProduct());
                    if($itemPrice !== false) {
                        $price += $itemPrice;
                    }
                    break;
                }
        }

        return $price;
    }

    /**
     * Returns the shipping price for a product & applies fallback logic.
     *
     * @param \Mage_Catalog_Model_Product $product
     * @param bool                        $useDefault
     *
     * @return false|float
     */
    public function getProductShippingPrice(Mage_Catalog_Model_Product $product, $useDefault = true)
    {
        $price = $product->getData(Definitions::ATTRIBUTE_CODE_FLAT_RATE_SHIPPING_PRICE);
        if ($price !== null) {
            return (float)$price;
        } elseif ($useDefault) {
            return $this->getDefaultProductFlatRatePrice();
        }

        return false;
    }

    /**
     * Returns the default flat-rate price per product if enabled
     * & defined in the config.
     *
     * @return false|float
     */
    public function getDefaultProductFlatRatePrice()
    {
        static $defaultPricePerItem;
        if (!isset($defaultPricePerItem)) {
            if ($this->getConfigData('enabled_by_default') && $this->getConfigData('default_price') !== '') {
                $defaultPricePerItem = (float)$this->getConfigData('default_price');
            } else {
                $defaultPricePerItem = false;
            }
        }

        return $defaultPricePerItem;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['flatrate' => $this->getConfigData('name')];
    }
}
