<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Bundle\Model\Sales\Order\Pdf\Items;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Sales\Model\Order\Pdf\Items\Invoice\PreparePriceLines;
use Magento\Tax\Helper\Data;

/**
 * Order invoice pdf default items renderer
 */
class Invoice extends AbstractItems
{
    /**
     * @var StringUtils
     */
    protected $string;

    /**
     * @var PreparePriceLines
     */
    private $preparePriceLines;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $taxData
     * @param Filesystem $filesystem
     * @param FilterManager $filterManager
     * @param StringUtils $coreString
     * @param Json $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param PreparePriceLines|null $preparePricesLines
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $taxData,
        Filesystem $filesystem,
        FilterManager $filterManager,
        StringUtils $coreString,
        Json $serializer,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?PreparePriceLines $preparePricesLines = null
    ) {
        $this->string = $coreString;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $serializer,
            $resource,
            $resourceCollection,
            $data
        );
        $this->preparePriceLines = $preparePricesLines ?? ObjectManager::getInstance()->get(PreparePriceLines::class);
    }

    /**
     * Draw item line
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();

        $this->_setFontRegular();
        $items = $this->getChildren($item);

        $prevOptionId = '';
        $drawItems = [];

        $lines = [];
        foreach ($items as $childItem) {
            $i = array_key_last($lines) !== null ? array_key_last($lines) + 1 : 0;
            $attributes = $this->getSelectionAttributes($childItem);
            if (is_array($attributes)) {
                $optionId = $attributes['option_id'];
            } else {
                $optionId = 0;
            }

            if (!isset($drawItems[$optionId])) {
                $drawItems[$optionId] = ['lines' => [], 'height' => 15];
            }

            if ($childItem->getOrderItem()->getParentItem() && $prevOptionId != $attributes['option_id']) {
                $lines[$i][] = [
                    'font' => 'italic',
                    'text' => $this->string->split($attributes['option_label'], 45, true, true),
                    'feed' => 35,
                ];

                $drawItems[$optionId] = ['height' => 15];
                $i++;
                $prevOptionId = $attributes['option_id'];
            }

            /* in case Product name is longer than 80 chars - it is written in a few lines */
            if ($childItem->getOrderItem()->getParentItem()) {
                $feed = 40;
                $name = $this->getValueHtml($childItem);
            } else {
                $feed = 35;
                $name = $childItem->getName();
            }
            $lines[$i][] = ['text' => $this->string->split($name, 35, true, true), 'feed' => $feed];

            // draw SKUs
            if (!$childItem->getOrderItem()->getParentItem()) {
                $text = [];
                foreach ($this->string->split($item->getSku(), 17) as $part) {
                    $text[] = $part;
                }
                $lines[$i][] = ['text' => $text, 'feed' => 255];
                $i++;
            }

            // draw prices
            if ($this->canShowPriceInfo($childItem)) {
                $this->_item = $childItem;
                $lines[$i][] = ['text' => $childItem->getQty() * 1, 'feed' => 435, 'align' => 'right'];

                $tax = $order->formatPriceTxt($childItem->getTaxAmount());
                $lines[$i][] = ['text' => $tax, 'feed' => 495, 'font' => 'bold', 'align' => 'right'];

                $this->preparePriceLines->execute($this->getItemPricesForDisplay(), $lines);
            }

        }
        $drawItems[$optionId]['lines'] = $lines;

        // custom options
        $options = $item->getOrderItem()->getProductOptions();
        if ($options && isset($options['options'])) {
            foreach ($options['options'] as $option) {
                $lines = [];
                $lines[][] = [
                    'text' => $this->string->split(
                        $this->filterManager->stripTags($option['label']),
                        40,
                        true,
                        true
                    ),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                if ($option['value']) {
                    $text = [];
                    $printValue = $option['print_value'] ?? $this->filterManager->stripTags($option['value']);
                    $values = explode(', ', $printValue);
                    foreach ($values as $value) {
                        foreach ($this->string->split($value, 30, true, true) as $subValue) {
                            $text[] = $subValue;
                        }
                    }

                    $lines[][] = ['text' => $text, 'feed' => 40];
                }

                $drawItems[] = ['lines' => $lines, 'height' => 15];
            }
        }

        $page = $pdf->drawLineBlocks($page, $drawItems, ['table_header' => true]);

        $this->setPage($page);
    }
}
