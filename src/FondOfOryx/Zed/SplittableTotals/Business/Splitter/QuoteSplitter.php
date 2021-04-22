<?php

namespace FondOfOryx\Zed\SplittableTotals\Business\Splitter;

use ArrayObject;
use FondOfOryx\Zed\SplittableTotals\SplittableTotalsConfig;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class QuoteSplitter implements QuoteSplitterInterface
{
    /**
     * @var \FondOfOryx\Zed\SplittableTotals\SplittableTotalsConfig
     */
    protected $config;

    /**
     * @param \FondOfOryx\Zed\SplittableTotals\SplittableTotalsConfig $config
     */
    public function __construct(SplittableTotalsConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array<string, \Generated\Shared\Transfer\QuoteTransfer>
     */
    public function split(QuoteTransfer $quoteTransfer): array
    {
        $getterMethod = $this->getGetterMethod();

        if ($getterMethod === null) {
            return ['*' => $quoteTransfer];
        }

        $quoteTransfers = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $splitItemAttributeValue = $itemTransfer->$getterMethod() ?? '*';

            if (!isset($quoteTransfers[$splitItemAttributeValue])) {
                $quoteTransfers[$splitItemAttributeValue] = $this->cloneQuote($quoteTransfer);
            }

            $quoteTransfers[$splitItemAttributeValue]->addItem($itemTransfer);
        }

        return $quoteTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function cloneQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return (new QuoteTransfer())->fromArray($quoteTransfer->toArray(), true)
            ->setIdQuote(null)
            ->setUuid(null)
            ->setItems(new ArrayObject())
            ->setIsDefault(false);
    }

    /**
     * @return string|null
     */
    protected function getGetterMethod(): ?string
    {
        $splitItemAttribute = $this->config->getSplitItemAttribute();

        if ($splitItemAttribute === null) {
            return null;
        }

        $getterMethod = sprintf(
            'get%s',
            str_replace(' ', '', ucwords(str_replace('_', ' ', $splitItemAttribute)))
        );

        if (!method_exists(ItemTransfer::class, $getterMethod)) {
            return null;
        }

        return $getterMethod;
    }
}