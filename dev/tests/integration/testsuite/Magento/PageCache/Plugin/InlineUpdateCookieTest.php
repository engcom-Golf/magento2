<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PageCache\Plugin;

use Magento\Framework\View\Element\FormKey;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Inline update form key cached value plugin test class
 */
class InlineUpdateCookieTest extends TestCase
{
    /**
     * @var FormKey
     */
    private $element;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->element = $objectManager->get(FormKey::class);
    }

    /**
     * @magentoCache full_page enabled
     */
    public function testInlineUpdateFormKeyWithCache()
    {
        $this->assertMatchesRegularExpression(
            '/.+document\.getElementById\(\".+\"\)\.previousSibling\.value = formKey.+/',
            $this->element->toHtml()
        );
    }

    /**
     * @magentoCache full_page disabled
     */
    public function testInlineUpdateFormKeyWithoutCache()
    {
        $this->assertDoesNotMatchRegularExpression(
            '/.+document\.getElementById\(\".+\"\)\.previousSibling\.value = formKey.+/',
            $this->element->toHtml()
        );
    }
}
