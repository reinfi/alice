<?php

/*
 * This file is part of the Alice package.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Alice\Generator\Resolver\Value\Chainable;

use Faker\Generator as FakerGenerator;
use Nelmio\Alice\Definition\Value\FunctionCallValue;
use Nelmio\Alice\Definition\ValueInterface;
use Nelmio\Alice\Exception\Generator\Resolver\ResolverNotFoundException;
use Nelmio\Alice\FixtureInterface;
use Nelmio\Alice\Generator\ResolvedFixtureSet;
use Nelmio\Alice\Generator\ResolvedValueWithFixtureSet;
use Nelmio\Alice\Generator\Resolver\Value\ChainableValueResolverInterface;
use Nelmio\Alice\Generator\ValueResolverAwareInterface;
use Nelmio\Alice\Generator\ValueResolverInterface;
use Nelmio\Alice\NotClonableTrait;

final class FakerFunctionCallValueResolver implements ChainableValueResolverInterface, ValueResolverAwareInterface
{
    use NotClonableTrait;

    /**
     * @var FakerGenerator
     */
    private $fakerGenerator;

    /**
     * @var ValueResolverInterface
     */
    private $resolver;

    public function __construct(FakerGenerator $fakerGenerator, ValueResolverInterface $resolver = null)
    {
        $this->fakerGenerator = $fakerGenerator;
        $this->resolver = $resolver;
    }

    /**
     * @inheritdoc
     */
    public function withResolver(ValueResolverInterface $resolver): self
    {
        return new self($this->fakerGenerator, $resolver);
    }

    /**
     * @inheritdoc
     */
    public function canResolve(ValueInterface $value): bool
    {
        return $value instanceof FunctionCallValue;
    }

    /**
     * {@inheritdoc}
     *
     * @param FunctionCallValue $value
     */
    public function resolve(
        ValueInterface $value,
        FixtureInterface $fixture,
        ResolvedFixtureSet $fixtureSet,
        array $scope = [],
        int $tryCounter = 0
    ): ResolvedValueWithFixtureSet
    {
        if (null === $this->resolver) {
            throw ResolverNotFoundException::createUnexpectedCall(__METHOD__);
        }

        $arguments = $value->getArguments();
        foreach ($arguments as $index => $argument) {
            if ($argument instanceof ValueInterface) {
                $resolvedSet = $this->resolver->resolve($argument, $fixture, $fixtureSet, $scope);

                $arguments[$index] = $resolvedSet->getValue();
                $fixtureSet = $resolvedSet->getSet();
            }
        }

        return new ResolvedValueWithFixtureSet(
            $this->fakerGenerator->format($value->getName(), $arguments),
            $fixtureSet
        );
    }
}
