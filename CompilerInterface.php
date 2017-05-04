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
interface CompilerInterface
{

    /**
     * @param string $name
     * @return ExpressionFunction The function registered using <code>$name</code>
     */
    public function getFunction($name);

    /**
     * Gets the current PHP code after compilation.
     *
     * @return string The PHP code
     */
    public function getSource();

    /**
     * @return $this The current compiler instance
     */
    public function reset();

    /**
     * Compiles a node.
     *
     * @param Node\Node $node The node to compile
     *
     * @return $this The current compiler instance
     */
    public function compile(Node\Node $node);

    /**
     * @param Node\Node $node
     *
     * @return string
     */
    public function subcompile(Node\Node $node);

    /**
     * Adds a raw string to the compiled code.
     *
     * @param string $string The string
     *
     * @return Compiler The current compiler instance
     */
    public function raw($string);

    /**
     * Adds a quoted string to the compiled code.
     *
     * @param string $value The string
     *
     * @return Compiler The current compiler instance
     */
    public function string($value);

    /**
     * Returns a PHP representation of a given value.
     *
     * @param mixed $value The value to convert
     *
     * @return Compiler The current compiler instance
     */
    public function repr($value);

}