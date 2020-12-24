<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Downloadable\Model\Sales\Order\Pdf\Items;

use Magento\Downloadable\Model\Link\PurchasedFactory;
use Magento\Downloadable\Model\ResourceModel\Link\Purchased\Item\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Sales\Model\Order\Pdf\Items\Invoice\PreparePriceLines;
use Magento\Tax\Helper\Data;

/**
 * Order Invoice Downloadable Pdf Items renderer
 *
 * @api
 * @since 100.0.2
 */
class Invoice extends AbstractItems
{
    /**
     * @var StringUtils
     */
    protected $string;
    /**
     * @var PreparePriceLines|null
     */
    private $preparePriceLines;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Data $taxData
     * @param Filesystem $filesystem
     * @param FilterManager $filterManager
     * @param ScopeConfigInterface $scopeConfig
     * @param PurchasedFactory $purchasedFactory
     * @param CollectionFactory $itemsFactory
     * @param StringUtils $string
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param PreparePriceLines|null $preparePriceLines
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $taxData,
        Filesystem $filesystem,
        FilterManager $filterManager,
        ScopeConfigInterface $scopeConfig,
        PurchasedFactory $purchasedFactory,
        CollectionFactory $itemsFactory,
        StringUtils $string,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        PreparePriceLines $preparePriceLines = null
    ) {
        $this->string = $string;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $scopeConfig,
            $purchasedFactory,
            $itemsFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->preparePriceLines = $preparePriceLines ?? ObjectManager::getInstance()->get(PreparePriceLines::class);
    }

    /**
     * Draw item line
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0] = [['text' => $this->string->split($item->getName(), 35, true, true), 'feed' => 35]];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->getSku($item), 17),
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 435, 'align' => 'right'];

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => 495,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw item Prices
        $this->preparePriceLines->execute($this->getItemPricesForDisplay(), $lines);


        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = [
                    'text' => $this->string->split($this->filterManager->stripTags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $this->filterManager->stripTags($option['value']);
                    }
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        $lines[][] = ['text' => $this->string->split($value, 30, true, true), 'feed' => 40];
                    }
                }
            }
        }

        // downloadable Items
        $purchasedItems = $this->getLinks()->getPurchasedItems();

        // draw Links title
        $lines[][] = [
            'text' => $this->string->split($this->getLinksTitle(), 70, true, true),
            'font' => 'italic',
            'feed' => 35,
        ];

        // draw Links
        foreach ($purchasedItems as $link) {
            $lines[][] = ['text' => $this->string->split($link->getLinkTitle(), 50, true, true), 'feed' => 40];
        }

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }
}
