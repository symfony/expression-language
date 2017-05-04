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

use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * Parsers a token stream.
 *
 * This parser implements a "Precedence climbing" algorithm.
 *
 * @see http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 * @see http://en.wikipedia.org/wiki/Operator-precedence_parser
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ParserInterface
{

    /**
     * Converts a token stream to a node tree.
     *
     * The valid names is an array where the values
     * are the names that the user can use in an expression.
     *
     * If the variable name in the compiled PHP code must be
     * different, define it as the key.
     *
     * For instance, ['this' => 'container'] means that the
     * variable 'container' can be used in the expression
     * but the compiled code will use 'this'.
     *
     * @param TokenStream $stream A token stream instance
     * @param array       $names  An array of valid names
     *
     * @return Node A node tree
     *
     * @throws SyntaxError
     */
    public function parse(TokenStream $stream, $names = array());

    public function parseExpression($precedence = 0);

    public function parsePrimaryExpression();

    public function parseArrayExpression();

    public function parseHashExpression();

    public function parsePostfixExpression($node);

    /**
     * Parses arguments.
     */
    public function parseArguments();

}
