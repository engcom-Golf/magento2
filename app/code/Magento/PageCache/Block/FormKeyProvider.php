<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @api
 */
declare(strict_types=1);

namespace Magento\PageCache\Block;

use Magento\Framework\View\Element\Template;
use Magento\PageCache\Model\Config;

/**
 * Adds script to update form key from cookie after script rendering
 */
class FormKeyProvider extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Is full page cache enabled
     *
     * @return bool
     */
    public function isFullPageCacheEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
