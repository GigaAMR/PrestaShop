<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Compiler;

use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ArgumentInterface;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerInterface;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Exception\RuntimeException;
use _PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference;
/**
 * Emulates the invalid behavior if the reference is not found within the
 * container.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ResolveInvalidReferencesPass implements \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{
    private $container;
    private $signalingException;
    /**
     * Process the ContainerBuilder to resolve invalid references.
     */
    public function process(\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $this->container = $container;
        $this->signalingException = new \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Exception\RuntimeException('Invalid reference.');
        try {
            $this->processValue($container->getDefinitions(), 1);
        } finally {
            $this->container = $this->signalingException = null;
        }
    }
    /**
     * Processes arguments to determine invalid references.
     *
     * @throws RuntimeException When an invalid reference is found
     */
    private function processValue($value, $rootLevel = 0, $level = 0)
    {
        if ($value instanceof \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument) {
            $value->setValues($this->processValue($value->getValues(), 1, 1));
        } elseif ($value instanceof \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Argument\ArgumentInterface) {
            $value->setValues($this->processValue($value->getValues(), $rootLevel, 1 + $level));
        } elseif ($value instanceof \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Definition) {
            if ($value->isSynthetic() || $value->isAbstract()) {
                return $value;
            }
            $value->setArguments($this->processValue($value->getArguments(), 0));
            $value->setProperties($this->processValue($value->getProperties(), 1));
            $value->setMethodCalls($this->processValue($value->getMethodCalls(), 2));
        } elseif (\is_array($value)) {
            $i = 0;
            foreach ($value as $k => $v) {
                try {
                    if (\false !== $i && $k !== $i++) {
                        $i = \false;
                    }
                    if ($v !== ($processedValue = $this->processValue($v, $rootLevel, 1 + $level))) {
                        $value[$k] = $processedValue;
                    }
                } catch (\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Exception\RuntimeException $e) {
                    if ($rootLevel < $level || $rootLevel && !$level) {
                        unset($value[$k]);
                    } elseif ($rootLevel) {
                        throw $e;
                    } else {
                        $value[$k] = null;
                    }
                }
            }
            // Ensure numerically indexed arguments have sequential numeric keys.
            if (\false !== $i) {
                $value = \array_values($value);
            }
        } elseif ($value instanceof \_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\Reference) {
            if ($this->container->has($value)) {
                return $value;
            }
            $invalidBehavior = $value->getInvalidBehavior();
            // resolve invalid behavior
            if (\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE === $invalidBehavior) {
                $value = null;
            } elseif (\_PhpScoper5eddef0da618a\Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE === $invalidBehavior) {
                if (0 < $level || $rootLevel) {
                    throw $this->signalingException;
                }
                $value = null;
            }
        }
        return $value;
    }
}
