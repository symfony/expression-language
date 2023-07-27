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
 * Represents an expression.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Expression
{
    protected string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    /**
     * Gets the expression.
     */
    public function __toString(): string
    {
        return $this->expression;
    }
}
