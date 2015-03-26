<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ExpressionLanguage;

/**
 * Compiles a node to PHP code.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Compiler implements CompilerInterface
{
    private $source;
    private $functions;

    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }

    public function getFunction($name)
    {
        return $this->functions[$name];
    }

    /**
     * Gets the current PHP code after compilation.
     *
     * @return string The PHP code
     */
    public function getSource()
    {
        return $this->source;
    }

    public function reset()
    {
        $this->source = '';

        return $this;
    }

    /**
     * Compiles a node.
     *
     * @param Node\Node $node The node to compile
     *
     * @return Compiler The current compiler instance
     */
    public function compile(Node\Node $node)
    {
        $node->compile($this);

        return $this;
    }

    public function subcompile(Node\Node $node)
    {
        $current = $this->source;
        $this->source = '';

        $node->compile($this);

        $source = $this->source;
        $this->source = $current;

        return $source;
    }

    /**
     * Adds a raw string to the compiled code.
     *
     * @param string $string The string
     *
     * @return Compiler The current compiler instance
     */
    public function raw($string)
    {
        $this->source .= $string;

        return $this;
    }

    /**
     * Adds a quoted string to the compiled code.
     *
     * @param string $value The string
     *
     * @return Compiler The current compiler instance
     */
    public function string($value)
    {
        $this->source .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));

        return $this;
    }

    /**
     * Returns a PHP representation of a given value.
     *
     * @param mixed $value The value to convert
     *
     * @return Compiler The current compiler instance
     */
    public function repr($value)
    {
        if (is_int($value) || is_float($value)) {
            if (false !== $locale = setlocale(LC_NUMERIC, 0)) {
                setlocale(LC_NUMERIC, 'C');
            }

            $this->raw($value);

            if (false !== $locale) {
                setlocale(LC_NUMERIC, $locale);
            }
        } elseif (null === $value) {
            $this->raw('null');
        } elseif (is_bool($value)) {
            $this->raw($value ? 'true' : 'false');
        } elseif (is_array($value)) {
            $this->raw('array(');
            $first = true;
            foreach ($value as $key => $keyValue) {
                if (!$first) {
                    $this->raw(', ');
                }
                $first = false;
                $this->repr($key);
                $this->raw(' => ');
                $this->repr($keyValue);
            }
            $this->raw(')');
        } else {
            $this->string($value);
        }

        return $this;
    }
}
