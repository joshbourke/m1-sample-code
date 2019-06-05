<?php
/**
 * AutoPetz Shipping Setup Installer
 * Adds EAV attribute to products for setting Flat Rates.
 */

/* @var $this \Mage_Eav_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();
$installer->addAttribute(
    Mage_Catalog_Model_Product::ENTITY,
    AutoPetz_ProductFlatRate_Helper_Definitions::ATTRIBUTE_CODE_SHIPPING_PRICE,
    [
        'group'            => 'Prices',
        'type'             => 'decimal',
        'backend'          => 'catalog/product_attribute_backend_price',
        'frontend'         => '',
        'label'            => 'Flat Rate Shipping Price',
        'input'            => 'price',
        'class'            => '',
        'source'           => '',
        'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
        'visible'          => false,
        'required'         => false,
        'user_defined'     => false,
        'searchable'       => false,
        'filterable'       => false,
        'comparable'       => false,
        'visible_on_front' => false,
        'unique'           => false,
        'apply_to'         => 'simple,configurable,bundle,grouped',
        'is_configurable'  => false,
    ]
);
$installer->endSetup();
