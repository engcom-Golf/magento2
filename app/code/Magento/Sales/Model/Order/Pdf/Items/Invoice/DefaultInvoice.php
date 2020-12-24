<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Sales\Model\Order\Pdf\Items\Invoice;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Sales\Model\Order\Pdf\Items\AbstractItems;
use Magento\Sales\Model\RtlTextHandler;
use Magento\Tax\Helper\Data;

/**
 * Sales Order Invoice Pdf default items renderer
 */
class DefaultInvoice extends AbstractItems
{
    /**
     * Core string
     *
     * @var StringUtils
     */
    protected $string;

    /**
     * @var RtlTextHandler
     */
    private $rtlTextHandler;

    /**
     * @var PreparePriceLines|mixed
     */
    private $preparePriceLines;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Data $taxData
     * @param Filesystem $filesystem
     * @param FilterManager $filterManager
     * @param StringUtils $string
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param RtlTextHandler|null $rtlTextHandler
     * @param PreparePriceLines|null $preparePricesLines
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $taxData,
        Filesystem $filesystem,
        FilterManager $filterManager,
        StringUtils $string,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?RtlTextHandler $rtlTextHandler = null,
        ?PreparePriceLines $preparePricesLines = null
    ) {
        $this->string = $string;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $resource,
            $resourceCollection,
            $data
        );
        $this->rtlTextHandler = $rtlTextHandler ?? ObjectManager::getInstance()->get(RtlTextHandler::class);
        $this->preparePriceLines = $preparePricesLines ?? ObjectManager::getInstance()->get(PreparePriceLines::class);
    }

    /**
     * Draw item line
     *
     * @return void
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];
        $i = 0;

        // draw Product name
        $lines[$i][] = [
                'text' => $this->string->split($this->prepareText((string)$item->getName()), 35, true, true),
                'feed' => 35
        ];

        // draw SKU
        $lines[$i][] = [
            'text' => $this->string->split($this->prepareText((string)$this->getSku($item)), 17),
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[$i][] = ['text' => $item->getQty() * 1, 'feed' => 435, 'align' => 'right'];


        // draw Tax
        $lines[$i][] = [
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

                // Checking whether option value is not null
                if ($option['value'] !== null) {
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

        $lineBlock = ['lines' => $lines, 'height' => 20];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    /**
     * Returns prepared for PDF text, reversed in case of RTL text
     *
     * @param string $string
     * @return string
     */
    private function prepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->rtlTextHandler->reverseRtlText(html_entity_decode($string));
    }
}
