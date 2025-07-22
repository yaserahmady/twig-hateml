<?php

namespace localghost\Twig\Extra\Hateml;

use localghost\Twig\Extra\Hateml\TokenParser\TagTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Yiisoft\Html\Html;


class HatemlExtension extends AbstractExtension {
    /**
     * @inheritdoc
     */
    public function getTokenParsers(): array
    {
        return [new TagTokenParser()];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {

        return [
            new TwigFunction('attr', [Html::class, 'renderTagAttributes'], ['is_safe' => ['html']]),
        ];
    }
}
