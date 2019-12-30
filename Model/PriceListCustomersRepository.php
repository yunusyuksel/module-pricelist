<?php
/**
 * Daniel Coull <d.coull@suttonsilver.co.uk>
 * 2019-2020
 *
 */

namespace SuttonSilver\PriceLists\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\DataObjectHelper;
use SuttonSilver\PriceLists\Api\Data\PriceListCustomersInterfaceFactory;
use SuttonSilver\PriceLists\Api\Data\PriceListCustomersSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use SuttonSilver\PriceLists\Model\ResourceModel\PriceListCustomers\CollectionFactory as PriceListCustomersCollectionFactory;
use SuttonSilver\PriceLists\Model\ResourceModel\PriceListCustomers as ResourcePriceListCustomers;
use Magento\Framework\Exception\NoSuchEntityException;
use SuttonSilver\PriceLists\Api\PriceListCustomersRepositoryInterface;

class PriceListCustomersRepository implements PriceListCustomersRepositoryInterface
{

    protected $dataObjectHelper;

    private $storeManager;

    protected $searchResultsFactory;

    protected $dataObjectProcessor;

    protected $extensionAttributesJoinProcessor;

    private $collectionProcessor;

    protected $extensibleDataObjectConverter;
    protected $priceListCustomersCollectionFactory;

    protected $resource;

    protected $priceListCustomersFactory;

    protected $dataPriceListCustomersFactory;


    /**
     * @param ResourcePriceListCustomers $resource
     * @param PriceListCustomersFactory $priceListCustomersFactory
     * @param PriceListCustomersInterfaceFactory $dataPriceListCustomersFactory
     * @param PriceListCustomersCollectionFactory $priceListCustomersCollectionFactory
     * @param PriceListCustomersSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourcePriceListCustomers $resource,
        PriceListCustomersFactory $priceListCustomersFactory,
        PriceListCustomersInterfaceFactory $dataPriceListCustomersFactory,
        PriceListCustomersCollectionFactory $priceListCustomersCollectionFactory,
        PriceListCustomersSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->priceListCustomersFactory = $priceListCustomersFactory;
        $this->priceListCustomersCollectionFactory = $priceListCustomersCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPriceListCustomersFactory = $dataPriceListCustomersFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \SuttonSilver\PriceLists\Api\Data\PriceListCustomersInterface $priceListCustomers
    ) {
        /* if (empty($priceListCustomers->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $priceListCustomers->setStoreId($storeId);
        } */
        
        $priceListCustomersData = $this->extensibleDataObjectConverter->toNestedArray(
            $priceListCustomers,
            [],
            \SuttonSilver\PriceLists\Api\Data\PriceListCustomersInterface::class
        );
        
        $priceListCustomersModel = $this->priceListCustomersFactory->create()->setData($priceListCustomersData);
        
        try {
            $this->resource->save($priceListCustomersModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the priceListCustomers: %1',
                $exception->getMessage()
            ));
        }
        return $priceListCustomersModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($priceListCustomersId)
    {
        $priceListCustomers = $this->priceListCustomersFactory->create();
        $this->resource->load($priceListCustomers, $priceListCustomersId);
        if (!$priceListCustomers->getId()) {
            throw new NoSuchEntityException(__('PriceListCustomers with id "%1" does not exist.', $priceListCustomersId));
        }
        return $priceListCustomers->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->priceListCustomersCollectionFactory->create();
        
        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \SuttonSilver\PriceLists\Api\Data\PriceListCustomersInterface::class
        );
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        \SuttonSilver\PriceLists\Api\Data\PriceListCustomersInterface $priceListCustomers
    ) {
        try {
            $priceListCustomersModel = $this->priceListCustomersFactory->create();
            $this->resource->load($priceListCustomersModel, $priceListCustomers->getPricelistcustomersId());
            $this->resource->delete($priceListCustomersModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PriceListCustomers: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($priceListCustomersId)
    {
        return $this->delete($this->get($priceListCustomersId));
    }
}
