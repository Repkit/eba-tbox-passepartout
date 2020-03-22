<?php

namespace TBoxPassepartout\Plugin;

use ZF\Hal\Extractor\LinkCollectionExtractorInterface;
use ZF\Hal\Link\LinkCollection;

class LinkCollectionExtractor implements LinkCollectionExtractorInterface
{
	/**
     * Extract a link collection into a structured set of links.
     *
     * @param LinkCollection $collection
     * @return array
     */
    public function extract(LinkCollection $collection)
    {
    	return [];
    }

    /**
     * @return LinkExtractorInterface
     */
    public function getLinkExtractor()
    {
    	return null;
    }
}