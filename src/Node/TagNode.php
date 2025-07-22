<?php

namespace localghost\Twig\Extra\Hateml\Node;

use Yiisoft\Html\Html;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * Class TagNode
 * @see https://github.com/craftcms/cms/blob/dd09e52e60d57e2e0e4cfa3b86d0fc90df4e4aa1/src/web/twig/nodes/TagNode.php
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class TagNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write('echo ' . Html::class . '::tag(')
            ->subcompile($this->getNode('name'))
            ->raw(', ob_get_clean()');


        if ($this->hasNode('attributes')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('attributes'));
        }

        // Hmm, could disabling encoding be dangerous?
        $compiler->raw(")->encode(false);\n");
    }
}