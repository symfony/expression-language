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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheAdapter;
use Symfony\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * Allows to compile and evaluate expressions written in your own DSL.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionLanguage
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;
    protected $lexer;
    protected $parser;
    protected $compiler;

    protected $functions = array();

    /**
     * @param CacheItemPoolInterface                $cache
     * @param ExpressionFunctionProviderInterface[] $providers
     */
    public function __construct($cache = null, array $providers = array())
    {
        if (null !== $cache) {
            if ($cache instanceof ParserCacheInterface) {
                @trigger_error(sprintf('Passing an instance of %s as constructor argument for %s is deprecated as of 3.2 and will be removed in 4.0. Pass an instance of %s instead.', ParserCacheInterface::class, self::class, CacheItemPoolInterface::class), E_USER_DEPRECATED);
                $cache = new ParserCacheAdapter($cache);
            } elseif (!$cache instanceof CacheItemPoolInterface) {
                throw new \InvalidArgumentException(sprintf('Cache argument has to implement %s.', CacheItemPoolInterface::class));
            }
        }

        $this->cache = $cache ?: new ArrayAdapter();
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

        asort($names);
        $cacheKeyItems = array();

        foreach ($names as $nameKey => $name) {
            $cacheKeyItems[] = is_int($nameKey) ? $name : $nameKey.':'.$name;
        }

        $cacheItem = $this->cache->getItem(rawurlencode($expression.'//'.implode('|', $cacheKeyItems)));

        if (null === $parsedExpression = $cacheItem->get()) {
            $nodes = $this->getParser()->parse($this->getLexer()->tokenize((string) $expression), $names);
            $parsedExpression = new ParsedExpression((string) $expression, $nodes);

            $cacheItem->set($parsedExpression);
            $this->cache->save($cacheItem);
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
     * @throws \LogicException when registering a function after calling evaluate(), compile() or parse()
     *
     * @see ExpressionFunction
     */
    public function register($name, callable $compiler, callable $evaluator)
    {
        if (null !== $this->parser) {
            throw new \LogicException('Registering functions after calling evaluate(), compile() or parse() is not supported.');
        }

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
        $this->addFunction(ExpressionFunction::fromPhp('constant'));
    }

    protected function getLexer()
    {
        if (null === $this->lexer) {
            $this->lexer = new Lexer();
        }

        return $this->lexer;
    }

    protected function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser($this->functions);
        }

        return $this->parser;
    }

    protected function getCompiler()
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler($this->functions);
        }

        return $this->compiler->reset();
    }
}
