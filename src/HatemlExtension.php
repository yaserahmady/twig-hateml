<?php

namespace localghost\Twig\Extra\Hateml;

use localghost\Twig\Extra\Hateml\TokenParser\SwitchTokenParser;
use localghost\Twig\Extra\Hateml\TokenParser\TagTokenParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Yiisoft\Html\Html;
use Yiisoft\Arrays\ArrayHelper;


class HatemlExtension extends AbstractExtension {
    /**
     * @inheritdoc
     */
    public function getTokenParsers(): array
    {
        return [
            new SwitchTokenParser(),
            new TagTokenParser(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {

        return [
            new TwigFunction('attr', [Html::class, 'renderTagAttributes'], ['is_safe' => ['html']]),
            new TwigFunction('tag', [$this, 'tagFunction'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Generates a complete HTML tag.
     * @see https://github.com/craftcms/cms/blob/dd09e52e60d57e2e0e4cfa3b86d0fc90df4e4aa1/src/web/twig/Extension.php#L1692-L1712
     * 
     * @param string $type the tag type ('p', 'div', etc.)
     * @param array $attributes the HTML tag attributes in terms of name-value pairs.
     * If `text` is supplied, the value will be HTML-encoded and included as the contents of the tag.
     * If 'html' is supplied, the value will be included as the contents of the tag, without getting encoded.
     * @return string
     * @since 3.3.0
     */
    public function tagFunction(string $type, array $attributes = []): string
    {
        $html = ArrayHelper::remove($attributes, 'html', '');
        $text = ArrayHelper::remove($attributes, 'text');

        if ($text !== null) {
            $html = Html::encode($text);
        }

        return Html::tag($type, $html, $attributes)->encode(false);
    }
}
