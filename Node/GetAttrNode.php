<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ExpressionLanguage\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
class GetAttrNode extends Node
{
    public const PROPERTY_CALL = 1;
    public const METHOD_CALL = 2;
    public const ARRAY_CALL = 3;

    private bool $isShortCircuited = false;
    public bool $isNullCoalesce = false;

    public function __construct(Node $node, Node $attribute, ArrayNode $arguments, int $type)
    {
        parent::__construct(
            ['node' => $node, 'attribute' => $attribute, 'arguments' => $arguments],
            ['type' => $type]
        );
    }

    public function compile(Compiler $compiler)
    {
        $nullSafe = $this->nodes['attribute'] instanceof ConstantNode && $this->nodes['attribute']->isNullSafe;
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw($nullSafe ? '?->' : '->')
                    ->raw($this->nodes['attribute']->attributes['value'])
                ;
                break;

            case self::METHOD_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw($nullSafe ? '?->' : '->')
                    ->raw($this->nodes['attribute']->attributes['value'])
                    ->raw('(')
                    ->compile($this->nodes['arguments'])
                    ->raw(')')
                ;
                break;

            case self::ARRAY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('[')
                    ->compile($this->nodes['attribute'])->raw(']')
                ;
                break;
        }
    }

    public function evaluate(array $functions, array $values)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (null === $obj && ($this->nodes['attribute']->isNullSafe || $this->isNullCoalesce)) {
                    $this->isShortCircuited = true;

                    return null;
                }
                if (null === $obj && $this->isShortCircuited()) {
                    return null;
                }

                if (!\is_object($obj)) {
                    throw new \RuntimeException(sprintf('Unable to get property "%s" of non-object "%s".', $this->nodes['attribute']->dump(), $this->nodes['node']->dump()));
                }

                $property = $this->nodes['attribute']->attributes['value'];

                if ($this->isNullCoalesce) {
                    return $obj->$property ?? null;
                }

                return $obj->$property;

            case self::METHOD_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);

                if (null === $obj && $this->nodes['attribute']->isNullSafe) {
                    $this->isShortCircuited = true;

                    return null;
                }
                if (null === $obj && $this->isShortCircuited()) {
                    return null;
                }

                if (!\is_object($obj)) {
                    throw new \RuntimeException(sprintf('Unable to call method "%s" of non-object "%s".', $this->nodes['attribute']->dump(), $this->nodes['node']->dump()));
                }
                if (!\is_callable($toCall = [$obj, $this->nodes['attribute']->attributes['value']])) {
                    throw new \RuntimeException(sprintf('Unable to call method "%s" of object "%s".', $this->nodes['attribute']->attributes['value'], get_debug_type($obj)));
                }

                return $toCall(...array_values($this->nodes['arguments']->evaluate($functions, $values)));

            case self::ARRAY_CALL:
                $array = $this->nodes['node']->evaluate($functions, $values);

                if (null === $array && $this->isShortCircuited()) {
                    return null;
                }

                if (!\is_array($array) && !$array instanceof \ArrayAccess && !(null === $array && $this->isNullCoalesce)) {
                    throw new \RuntimeException(sprintf('Unable to get an item of non-array "%s".', $this->nodes['node']->dump()));
                }

                if ($this->isNullCoalesce) {
                    return $array[$this->nodes['attribute']->evaluate($functions, $values)] ?? null;
                }

                return $array[$this->nodes['attribute']->evaluate($functions, $values)];
        }
    }

    private function isShortCircuited(): bool
    {
        return $this->isShortCircuited
            || ($this->nodes['node'] instanceof self && $this->nodes['node']->isShortCircuited())
        ;
    }

    public function toArray()
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                return [$this->nodes['node'], '.', $this->nodes['attribute']];

            case self::METHOD_CALL:
                return [$this->nodes['node'], '.', $this->nodes['attribute'], '(', $this->nodes['arguments'], ')'];

            case self::ARRAY_CALL:
                return [$this->nodes['node'], '[', $this->nodes['attribute'], ']'];
        }
    }
}
