<?php

namespace localghost\Twig\Extra\Hateml\Helpers;

use InvalidArgumentException;
use Yiisoft\Html\Html as YiisoftHtml;
use Yiisoft\Arrays\ArrayHelper;

class Html extends YiisoftHtml
{

    public static function modifyTagAttributes(string $tag, array $attributes): string
    {
        // Normalize the attributes & merge with the old attributes
        $attributes = static::normalizeTagAttributes($attributes);
        $oldAttributes = static::parseTagAttributes($tag, 0, $start, $end, true);
        $attributes = ArrayHelper::merge($oldAttributes, $attributes);

        // Ensure we don't have any duplicate classes
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = array_unique($attributes['class']);
        }

        return substr($tag, 0, $start) .
            static::renderTagAttributes($attributes) .
            substr($tag, $end);
    }

    /**
     * Parses an HTML tag to find its attributes.
     *
     * @param string $tag The HTML tag to parse
     * @param int $offset The offset to start looking for a tag
     * @param int|null $start The start position of the first attribute in the given tag
     * @param-out int $start
     * @param int|null $end The end position of the last attribute in the given tag
     * @param bool $decode Whether the attributes should be HTML decoded in the process
     * @return array The parsed HTML tag attributes
     * @throws InvalidHtmlTagException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTagAttributes(string $tag, int $offset = 0, ?int &$start = null, ?int &$end = null, bool $decode = false): array
    {
        [$type, $tagStart] = self::_findTag($tag, $offset);
        $start = $tagStart + strlen($type) + 1;
        $anchor = $start;
        $attributes = [];

        do {
            try {
                $attribute = static::parseTagAttribute($tag, $anchor, $attrStart, $attrEnd);
            } catch (InvalidArgumentException $e) {
                throw new InvalidHtmlTagException($e->getMessage(), $type, null, $tagStart);
            }

            // Did we just reach the end of the tag?
            if ($attribute === null) {
                $end = $anchor;
                break;
            }

            [$name, $value] = $attribute;
            $attributes[$name] = $value;
            $anchor = $attrEnd;
        } while (true);

        $attributes = static::normalizeTagAttributes($attributes);

        if ($decode) {
            foreach ($attributes as &$value) {
                if (is_string($value)) {
                    $value = static::decode($value);
                }
            }
        }

        return $attributes;
    }


    /**
     * Parses the next HTML tag attribute in a given string.
     *
     * @param string $html The HTML to parse
     * @param int $offset The offset to start looking for an attribute
     * @param int|null $start The start position of the attribute in the given HTML
     * @param int|null $end The end position of the attribute in the given HTML
     * @return array|null The name and value of the attribute, or `false` if no complete attribute was found
     * @throws InvalidArgumentException if `$html` doesn't begin with a valid HTML attribute
     * @since 3.7.0
     */
    public static function parseTagAttribute(string $html, int $offset = 0, ?int &$start = null, ?int &$end = null): ?array
    {
        if (!preg_match('/\s*([^=\/>\s]+)/A', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
            if (!preg_match('/(\s*)\/?>/A', $html, $m, 0, $offset)) {
                // No `>`
                throw new InvalidArgumentException("Malformed HTML tag attribute in string: $html");
            }

            // No more attributes here
            return null;
        }

        $value = true;

        // Does the tag have an explicit value?
        $offset += strlen($match[0][0]);

        if (preg_match('/\s*=\s*/A', $html, $m, 0, $offset)) {
            $offset += strlen($m[0]);

            // Wrapped in quotes?
            if (isset($html[$offset]) && in_array($html[$offset], ['\'', '"'])) {
                $q = preg_quote($html[$offset], '/');
                if (!preg_match("/$q(.*?)$q/sA", $html, $m, 0, $offset)) {
                    // No matching end quote
                    throw new InvalidArgumentException("Malformed HTML tag attribute in string: $html");
                }

                $offset += strlen($m[0]);
                if (isset($m[1]) && $m[1] !== '') {
                    $value = static::decode($m[1]);
                }
            } elseif (preg_match('/[^\s>]+/A', $html, $m, 0, $offset)) {
                $offset += strlen($m[0]);
                $value = static::decode($m[0]);
            }
        }

        $start = (int) $match[1][1];
        $end = $offset;

        return [$match[1][0], $value];
    }


    /**
     * Decodes special HTML entities back to the corresponding characters.
     * This is the opposite of [[encode()]].
     * @param string $content the content to be decoded
     * @return string the decoded content
     * @see encode()
     * @see https://www.php.net/manual/en/function.htmlspecialchars-decode.php
     */
    public static function decode($content)
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }

    /**
     * Finds the first tag defined in some HTML that isn't a comment or DTD.
     *
     * @param string $html
     * @param int $offset
     * @return array{non-empty-string, int} The tag type and starting position
     * @throws InvalidHtmlTagException
     */
    private static function _findTag(string $html, int $offset = 0): array
    {
        // Find the first HTML tag that isn't a DTD or a comment
        if (!preg_match('/<(\/?[\w\-]+)/', $html, $match, PREG_OFFSET_CAPTURE, $offset) || $match[1][0][0] === '/') {
            throw new InvalidHtmlTagException(
                "Could not find an HTML tag in string: $html",
                isset($match[1][0]) ? strtolower($match[1][0]) : null,
                null,
                $match[0][1] ?? null
            );
        }

        return [strtolower($match[1][0]), $match[0][1]];
    }

    public static function normalizeTagAttributes(array $attributes): array
    {
        // List of tag attributes that should be specially handled when their values are of array type.
        $data_attributes = ["aria", "data", "data-hx", "data-ng", "hx", "ng"];

        $normalized = [];

        foreach ($attributes as $name => $value) {
            if ($value === false || $value === null) {
                $normalized[$name] = false;
                continue;
            }

            switch ($name) {
                case "class":
                case "removeClass":
                    $normalized[$name] = static::explodeClass($value);
                    break;
                case "style":
                    $normalized[$name] = static::explodeStyle($value);
                    break;
                default:
                    // See if it's a data attribute
                    foreach ($data_attributes as $dataAttribute) {
                        if (str_starts_with($name, $dataAttribute . "-")) {
                            $n = substr($name, strlen($dataAttribute) + 1);
                            $normalized[$dataAttribute][$n] = $value;
                            break 2;
                        }
                    }
                    $normalized[$name] = $value;
            }
        }

        if (isset($normalized["removeClass"])) {
            $removeClasses = ArrayHelper::remove($normalized, "removeClass");
            $normalized["class"] = array_diff(
                $normalized["class"] ?? [],
                $removeClasses,
            );
        }

        return $normalized;
    }

    public static function explodeClass(mixed $value): array
    {
        if ($value === null || is_bool($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return static::filterEmptyStringsFromArray(explode(" ", $value));
        }
        throw new InvalidArgumentException("Invalid class value");
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
            $value = preg_replace_callback(
                "/\burl\(.*\)/i",
                function ($match) use (&$markers) {
                    $marker = sprintf("{marker:%s}", mt_rand());
                    $markers[$marker] = $match[0];
                    return $marker;
                },
                $value,
            );

            // now split the styles string on semicolons
            $styles = static::filterEmptyStringsFromArray(
                preg_split("/\s*;\s*/", $value),
            );

            // and proceed with the array of styles
            $normalized = [];
            foreach ($styles as $style) {
                [$n, $v] = array_pad(preg_split("/\s*:\s*/", $style, 2), 2, "");
                $normalized[$n] = strtr($v, $markers);
            }
            return $normalized;
        }
        throw new InvalidArgumentException("Invalid style value");
    }

    public static function filterEmptyStringsFromArray(array $array): array
    {
        return array_filter($array, fn($value): bool => $value !== "");
    }
}

/**
 * InvalidHtmlTagException represents an invalid HTML tag encountered via [[\craft\helpers\Html::parseTag()]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.27
 */
class InvalidHtmlTagException extends InvalidArgumentException
{
    /**
     * @var string|null The tag type
     */
    public ?string $type = null;

    /**
     * @var array|null The tag attributes
     */
    public ?array $attributes = null;

    /**
     * @var int|null The tag’s starting position
     */
    public ?int $start = null;

    /**
     * @var int|null The tag’s inner HTML starting position
     */
    public ?int $htmlStart = null;

    /**
     * Constructor.
     *
     * @param string $message The error message
     * @param string|null $type The tag type
     * @param array|null $attributes The tag attributes
     * @param int|null $start The tag’s starting position
     * @param int|null $htmlStart The tag’s inner HTML starting position
     */
    public function __construct(string $message, ?string $type = null, ?array $attributes = null, ?int $start = null, ?int $htmlStart = null)
    {
        $this->type = $type;
        $this->attributes = $attributes;
        $this->start = $start;
        $this->htmlStart = $htmlStart;

        parent::__construct($message);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid HTML tag';
    }
}
