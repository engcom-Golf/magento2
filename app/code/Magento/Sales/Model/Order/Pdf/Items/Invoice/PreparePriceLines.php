<?php

namespace Magento\Sales\Model\Order\Pdf\Items\Invoice;

/**
 * Prepare price lines
 */
class PreparePriceLines
{
    /**
     * @param array $prices
     * @param array $linesArray
     */
    public function execute(array $prices, array &$linesArray): void
    {
        $index = array_key_last($linesArray);
        $feedPrice = 360;
        $feedSubtotal = $feedPrice + 205;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                // draw Price label
                $linesArray[$index][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                // draw Subtotal label
                $linesArray[$index][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $index++;
            }
            // draw Price
            $linesArray[$index][] = [
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
            ];
            // draw Subtotal
            $linesArray[$index][] = [
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $index++;
        }
    }
}
