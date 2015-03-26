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

use Symfony\Component\ExpressionLanguage\ParserCache\ArrayParserCache;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * Allows to compile and evaluate expressions written in your own DSL.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionLanguage
{
    /**
     * @var ParserCacheInterface
     */
    private $cache;
	/**
	 * @var LexerInterface
	 */
    private $lexer;
	/**
	 * @var ParserInterface
	 */
    private $parser;
	/**
	 * @var CompilerInterface
	 */
    private $compiler;

	/**
	 * @var ExpressionFunction[]
	 */
    protected $functions = array();

    /**
     * @param ParserCacheInterface                  $cache
     * @param ExpressionFunctionProviderInterface[] $providers
     */
    public function __construct(ParserCacheInterface $cache = null, array $providers = array())
    {
        $this->cache = $cache ?: new ArrayParserCache();
        $this->registerFunctions();
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }
    }

    /**
     * Compiles an expression source code.
     *
     * @param Expression|string $expression The expression to compile
     * @param array             $names      An array of valid names
     *
     * @return string The compiled PHP source code
     */
    public function compile($expression, $names = array())
    {
        return $this->getCompiler()->compile($this->parse($expression, $names)->getNodes())->getSource();
    }

    /**
     * Evaluate an expression.
     *
     * @param Expression|string $expression The expression to compile
     * @param array             $values     An array of values
     *
     * @return string The result of the evaluation of the expression
     */
    public function evaluate($expression, $values = array())
    {
        return $this->parse($expression, array_keys($values))->getNodes()->evaluate($this->functions, $values);
    }

    /**
     * Parses an expression.
     *
     * @param Expression|string $expression The expression to parse
     * @param array             $names      An array of valid names
     *
     * @return ParsedExpression A ParsedExpression instance
     */
    public function parse($expression, $names)
    {
        if ($expression instanceof ParsedExpression) {
            return $expression;
        }

        $key = $expression.'//'.implode('-', $names);

        if (null === $parsedExpression = $this->cache->fetch($key)) {
            $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string) $expression), $names);
            $parsedExpression = new ParsedExpression((string) $expression, $nodes);

            $this->cache->save($key, $parsedExpression);
        }

        return $parsedExpression;
    }

    /**
     * Registers a function.
     *
     * @param string   $name      The function name
     * @param callable $compiler  A callable able to compile the function
     * @param callable $evaluator A callable able to evaluate the function
     *
     * @see ExpressionFunction
     */
    public function register($name, $compiler, $evaluator)
    {
        $this->functions[$name] = array('compiler' => $compiler, 'evaluator' => $evaluator);
    }

    public function addFunction(ExpressionFunction $function)
    {
        $this->register($function->getName(), $function->getCompiler(), $function->getEvaluator());
    }

    public function registerProvider(ExpressionFunctionProviderInterface $provider)
    {
        foreach ($provider->getFunctions() as $function) {
            $this->addFunction($function);
        }
    }

    protected function registerFunctions()
    {
        $this->register('constant', function ($constant) {
            return sprintf('constant(%s)', $constant);
        }, function (array $values, $constant) {
            return constant($constant);
        });
    }

	/**
	 * @return LexerInterface
	 */
    protected function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

	/**
	 * @param LexerInterface $lexer
	 * @return $this
	 */
	protected function setLexer(LexerInterface $lexer)
	{
		$this->lexer = $lexer;
		return $this;
	}

	/**
	 * @return ParserInterface
	 */
    protected function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }

	/**
	 * @param ParserInterface $parser
	 * @return $this
	 */
	protected function setParser($parser)
	{
		$this->parser = $parser;
		return $this;
	}

	/**
	 * @return CompilerInterface
	 */
    protected function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this->functions);
        }

        return $this->compiler->reset();
    }

	/**
	 * @param CompilerInterface $compiler
	 * @return $this
	 */
	protected function setCompiler(CompilerInterface $compiler)
	{
		$this->compiler = $compiler;
		return $this;
	}

}
