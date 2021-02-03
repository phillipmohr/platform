<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Store\Services;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Store\Exception\StoreApiException;
use Shopware\Core\Framework\Store\Struct\ExtensionCollection;
use Shopware\Core\Framework\Store\Struct\ExtensionStruct;

/**
 * @internal
 */
class ExtensionListingLoader
{
    /**
     * @var StoreClient
     */
    private $client;

    public function __construct(StoreClient $client)
    {
        $this->client = $client;
    }

    public function load(ExtensionCollection $localCollection, Context $context): ExtensionCollection
    {
        $this->addStoreInformation($localCollection, $context);

        return $this->sortCollection($localCollection);
    }

    private function addStoreInformation(ExtensionCollection $localCollection, Context $context): void
    {
        try {
            $storeExtensions = $this->client->listMyExtensions($localCollection, $context);
        } catch (StoreApiException $e) {
            return;
        }

        foreach ($storeExtensions->getElements() as $storeExtension) {
            if ($localCollection->has($storeExtension->getName())) {
                /** @var ExtensionStruct $localExtension */
                $localExtension = $localCollection->get($storeExtension->getName());
                $localExtension->setId($storeExtension->getId());
                $localExtension->setIsTheme($storeExtension->isTheme());
                $localExtension->setStoreExtension($storeExtension);

                $localExtension->setStoreLicense($storeExtension->getStoreLicense());
                $localExtension->setNotices($storeExtension->getNotices());

                if ($storeExtension->getDescription()) {
                    $localExtension->setDescription($storeExtension->getDescription());
                }

                if ($storeExtension->getShortDescription()) {
                    $localExtension->setShortDescription($storeExtension->getShortDescription());
                }

                $localExtension->setIcon($storeExtension->getIcon());
                $localExtension->setLabel($storeExtension->getLabel());

                if ($storeExtension->getLatestVersion()) {
                    $localExtension->setLatestVersion($storeExtension->getLatestVersion());
                    $localExtension->setUpdateSource($storeExtension->getUpdateSource());
                }

                continue;
            }

            $localCollection->set($storeExtension->getName(), $storeExtension);
        }
    }

    private function sortCollection(ExtensionCollection $collection): ExtensionCollection
    {
        $collection->sort(function (ExtensionStruct $a, ExtensionStruct $b) {
            return strcmp($a->getLabel(), $b->getLabel());
        });

        $sortedCollection = new ExtensionCollection();

        // Sorted order: active, installed, all others
        foreach ($collection->getElements() as $extension) {
            if ($extension->getActive()) {
                $sortedCollection->set($extension->getName(), $extension);
                $collection->remove($extension->getName());
            }
        }

        foreach ($collection->getElements() as $extension) {
            if ($extension->getInstalledAt()) {
                $sortedCollection->set($extension->getName(), $extension);
                $collection->remove($extension->getName());
            }
        }

        foreach ($collection->getElements() as $extension) {
            $sortedCollection->set($extension->getName(), $extension);
        }

        return $sortedCollection;
    }
}