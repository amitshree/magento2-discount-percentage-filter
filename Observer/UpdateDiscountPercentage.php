<?php

namespace Amitshree\DiscountPercentageFilter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateDiscountPercentage implements ObserverInterface
{

    public function __construct(
            \Amitshree\DiscountPercentageFilter\Helper\Data $dataHelper
        ) {
        $this->dataHelper = $dataHelper;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getProduct();
        $product_price =  $product->getPrice();
        $product_sp_price =  $product->getSpecialPrice();
        $origData = $product->getOrigData();
        $origSpecialPrice = null;
        if (isset($origData['special_price']) && !empty($origData['special_price'])) {
            $origSpecialPrice = $origData['special_price'];
        }
        $origBasePrice = $origData['price'];
        if ($product_sp_price >= $product_price) {
            $product->setDiscountPercentage(null);
        } elseif ($product_price !== $origBasePrice || $product_sp_price !== $origSpecialPrice) {
            $discountPercentage = 100 - round(($product_sp_price / $product_price)*100);
            $discountVar = $this->dataHelper->getDiscountVar($discountPercentage);
            if ($discountVar) {
                $discountId = $product->getResource()->getAttribute("discount_percentage")->getSource()->getOptionId($discountVar);
                $product->setDiscountPercentage($discountId);
            }
        }
    }

}
