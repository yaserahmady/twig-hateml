<?php

namespace localghost\Twig\Extra\Hateml\Helper;

use yii\base\InvalidArgumentException;

/**
 * Class Html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Html extends \yii\helpers\Html
{
    /**
     * @inheritdoc
     */
    public static function tag($name, $content = '', $options = [])
    {
        return parent::tag($name, $content, static::normalizeTagAttributes($options));
    }

     /**
     * Normalizes attributes.
     *
     * @param array $attributes
     * @return array
     * @since 3.3.0
     */
    public static function normalizeTagAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null) {
                $normalized[$name] = false;
                continue;
            }

            switch ($name) {
                case 'class':
                case 'removeClass':
                    $normalized[$name] = static::explodeClass($value);
                    break;
                case 'style':
                    $normalized[$name] = static::explodeStyle($value);
                    break;
                default:
                    // See if it's a data attribute
                    foreach (self::_sortedDataAttributes() as $dataAttribute) {
                        if (str_starts_with($name, $dataAttribute . '-')) {
                            $n = substr($name, strlen($dataAttribute) + 1);
                            $normalized[$dataAttribute][$n] = $value;
                            break 2;
                        }
                    }
                    $normalized[$name] = $value;
            }
        }

        if (isset($normalized['removeClass'])) {
            $removeClasses = ArrayHelper::remove($normalized, 'removeClass');
            $normalized['class'] = array_diff($normalized['class'] ?? [], $removeClasses);
        }

        return $normalized;
    }

    /**
     * Explodes a `style` attribute into an array of property/value pairs.
     *
     * @param mixed $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeStyle(mixed $value): array
    {
        if ($value === null || is_bool($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            // first match any css properties that contain 'url()'
            $markers = [];
            $value = preg_replace_callback('/\burl\(.*\)/i', function($match) use (&$markers) {
                $marker = sprintf('{marker:%s}', mt_rand());
                $markers[$marker] = $match[0];
                return $marker;
            }, $value);

            // now split the styles string on semicolons
            $styles = ArrayHelper::filterEmptyStringsFromArray(preg_split('/\s*;\s*/', $value));

            // and proceed with the array of styles
            $normalized = [];
            foreach ($styles as $style) {
                [$n, $v] = array_pad(preg_split('/\s*:\s*/', $style, 2), 2, '');
                $normalized[$n] = strtr($v, $markers);
            }
            return $normalized;
        }
        throw new InvalidArgumentException('Invalid style value');
    }


    /**
     * Explodes a `class` attribute into an array.
     *
     * @param mixed $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeClass(mixed $value): array
    {
        if ($value === null || is_bool($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return ArrayHelper::filterEmptyStringsFromArray(explode(' ', $value));
        }
        throw new InvalidArgumentException('Invalid class value');
    }

}
