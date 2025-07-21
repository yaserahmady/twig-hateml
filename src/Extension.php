<?php

namespace localghost\Twig\Extra\Hateml;

use localghost\Twig\Extra\Hateml\TokenParser\TagTokenParser;
use Twig\Extension\AbstractExtension;

/**
 * Custom Twig extension for WordPress/Timber
 *
 * Provides the {% tag %} functionality similar to Craft CMS
 */
class HatemlExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getTokenParsers(): array
    {
        return [new TagTokenParser()];
    }
}
