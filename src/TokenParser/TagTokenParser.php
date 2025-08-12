<?php

namespace localghost\Twig\Extra\Hateml\TokenParser;

use localghost\Twig\Extra\Hateml\Node\TagNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class TagTokenParser
 * @see https://github.com/craftcms/cms/blob/0c0e410243138536687297a2ea876c420bc66d49/src/web/twig/tokenparsers/TagTokenParser.php
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class TagTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'tag';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): TagNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'name' => $this->parser->parseExpression(),
        ];

        if ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['attributes'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['content'] = $this->parser->subparse(fn(Token $token) => $token->test('endtag'), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new TagNode($nodes, [], $lineno);
    }
}