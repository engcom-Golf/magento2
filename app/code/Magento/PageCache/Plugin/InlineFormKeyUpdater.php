<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PageCache\Plugin;

use Magento\Framework\View\Element\FormKey;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\PageCache\Model\Config;

/**
 * Adds script to update form key from cookie after script rendering
 */
class InlineFormKeyUpdater
{
    /**
     * @var SecureHtmlRenderer
     */
    private $secureHtmlRenderer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param SecureHtmlRenderer $secureHtmlRenderer
     * @param Config $config
     */
    public function __construct(
        SecureHtmlRenderer $secureHtmlRenderer,
        Config $config
    ) {
        $this->secureHtmlRenderer = $secureHtmlRenderer;
        $this->config = $config;
    }

    /**
     * Add inline Javascript to update the form_key
     *
     * @param FormKey $formKey
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToHtml(FormKey $formKey, string $result)
    {
        if ($this->config->isEnabled()) {
            $result .= $this->getInlineJavaScript();
        }

        return $result;
    }

    /**
     * Generate inline JavaScript to update for form_key
     *
     * @return string
     */
    private function getInlineJavaScript()
    {
        $uniqueId = uniqid();
        $scriptString = 'document.getElementById("' . $uniqueId . '").previousSibling.value = window.formKey;';

        return $this->secureHtmlRenderer->renderTag('script', ['id' => $uniqueId], $scriptString, false);
    }
}
