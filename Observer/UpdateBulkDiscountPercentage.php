<?php

namespace Amitshree\DiscountPercentageFilter\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateBulkDiscountPercentage implements  ObserverInterface
{

	public function __construct(
		\Amitshree\DiscountPercentageFilter\Helper\Data $dataHelper,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
		\Magento\Catalog\Model\ResourceModel\Product\Action $action,
		\Magento\Store\Model\StoreManagerInterface $storeManager
		) {
		$this->dataHelper = $dataHelper;
		$this->productRepository = $productRepository;
		$this->action = $action;
		$this->storeManager = $storeManager; 
	}

	public function execute(Observer $observer){
		try {
			$bunch = $observer->getBunch();
			foreach($bunch as $product){ 
				if ($product['special_price']) {
					$sku = $product['sku'];
					$newSpecialPrice = $product['special_price'];

					$productData = $this->productRepository->get($sku);
					$product_price = isset($product['price']) ? $product['price']: $productData->getPrice();
					$origSpecialPrice = $productData->getSpecialPrice();

					$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pr.log');
					$logger = new \Zend\Log\Logger();
					$logger->addWriter($writer);
					$logger->info('product_sp_price : '.$newSpecialPrice);
					$logger->info('orig sp pr : '.$origSpecialPrice);

					if ($newSpecialPrice >= $product_price) {
			            //$productData->setDiscountPercentage(null);
			            $this->action->updateAttributes([$productData->getEntityId()], ['discount_percentage' => null], $this->getStoreId());	
			            $logger->info('set to null called');
			        } else if($newSpecialPrice != $origSpecialPrice || $product_price != $productData->getPrice()){
						$discountPercentage = 100 - round(($newSpecialPrice / $product_price)*100);
						$discountVar = $this->dataHelper->getDiscountVar($discountPercentage);
						$logger->info('dis perc: '.$discountPercentage);
						$logger->info('dis var: '.$discountVar);
						$discountId = $productData->getResource()->getAttribute("discount_percentage")->getSource()->getOptionId($discountVar);
						$this->action->updateAttributes([$productData->getEntityId()], ['discount_percentage' => $discountId], $this->getStoreId());
			        }

				}
			}
		} catch(\Exception $e) {
			echo $e->getMessage();
		}
	}

	public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}