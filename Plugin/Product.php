<?php

namespace B2bzanden\SitemapUrl\Plugin;

class Product
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\Resource\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $productStatus;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;


    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility
    )
    {
        $this->_storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
    }

    public function afterGetCollection(\Magento\Sitemap\Model\ResourceModel\Catalog\Product $subject, $result, $storeId)
    {
        // create product url array (key = productId, value = product url)
        $productUrls = [];
        $productCollection = $this->collectionFactory->create()
            ->setStore($storeId)
            ->addAttributeToSelect('product_url')
            ->addAttributeToSelect('id')
            ->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()])
            ->setVisibility($this->productVisibility->getVisibleInSiteIds());


        foreach ($productCollection as $product) {
            $productId = (string)$product->getId();
            $storeUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();

            $fullProductUrl = $product->getProductUrl();
            $productUrlPath = str_replace($storeUrl, "", $fullProductUrl);

            $productUrls[$productId] = $productUrlPath;
        }

        // replace sitemap collection item's 'url' property with product's "product_url" attribute
        $collection = $result;
        foreach ($collection as $item) {
            $productId = (string)$item->getId();
            if ($replacementUrl = $productUrls[$productId]) {
                $item->setUrl($replacementUrl);
            }
        }

        return $result;
    }

}
