<?php

declare(strict_types=1);

namespace Packetery\Checkout\Test;

use PHPUnit\Framework\MockObject\MockObject;

abstract class BaseTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @param $object
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeMethod($object, string $method, array $args = [])
    {
        $rc = new \ReflectionClass($object);
        $method = $rc->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * @param array $callback
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function invokeCallback(array $callback, array $args = [])
    {
        $object = array_shift($callback);
        $method = array_shift($callback);
        return $this->invokeMethod($object, $method, $args);
    }

    /**
     * @param $className
     * @param $mock
     * @param string $property
     * @param $value
     * @throws \ReflectionException
     */
    protected function mockPrivateProperty($className, $mock, string $property, $value): void
    {
        $rc = new \ReflectionClass($className);

        $property = $rc->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($mock, $value);
    }

    /**
     * @param string $className
     * @return array
     * @throws \ReflectionException
     */
    protected function createConstructorMocks(string $className):array
    {
        $rc = new \ReflectionClass($className);
        $constructor = $rc->getConstructor();
        $params = $constructor->getParameters();

        $args = [];
        foreach ($params as $param) {
            if ($param->isDefaultValueAvailable()) {
                $args[$param->getName()] = $param->getDefaultValue();
                continue;
            }

            $classType = $param->getClass();
            $args[$param->getName()] = $this->createMock($classType->getName());
        }

        return $args;
    }

    /**
     * @param string $exceptionClass
     * @param callable $callback
     * @throws \PHPUnit\Exception
     */
    protected function assertException(string $exceptionClass, callable $callback): void
    {
        $e = null;
        try {
            call_user_func_array($callback, []);
        } catch (\PHPUnit\Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
        }

        $actualExceptionClass = (is_object($e) ? get_class($e) : null);
        $this->assertEquals($exceptionClass, $actualExceptionClass, ($e instanceof \Throwable ? $e->getMessage() : ''));
    }

    /**
     * Returns a mock object for the specified class.
     *
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $originalClassName
     * @psalm-return MockObject&RealInstanceType
     */
    protected function createMockWithProps($originalClassName, array $props = []): MockObject
    {
        $mock = $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();

        foreach ($props as $prop => $propValue) {
            $this->mockPrivateProperty($originalClassName, $mock, $prop, $propValue);
        }

        return $mock;
    }

    /**
     * @psalm-template RealInstanceType of object
     * @psalm-param class-string<RealInstanceType> $originalClassName
     * @psalm-return MockObject&RealInstanceType
     */
    protected function createProxy($originalClassName, $properties = [], $existingMethods = [])
    {
        $service = $this->createPartialMock($originalClassName, array_keys($existingMethods));

        foreach ($existingMethods as $existingMethod => $value) {
            $service->method($existingMethod)->willReturn($value);
        }

        foreach ($properties as $argName => $customArg) {
            $this->mockPrivateProperty(
                $originalClassName, $service, $argName, $customArg
            );
        }

        return $service;
    }

    /**
     * @param $originalClassName
     * @param $args
     * @param $existingMethods
     * @param $addMethods
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    protected function createProxyWithMethods($originalClassName, $args, $existingMethods, $addMethods): \PHPUnit\Framework\MockObject\MockObject
    {
        $constructorArguments = $this->createConstructorMocks($originalClassName);

        foreach ($args as $arg => $val) {
            $constructorArguments[$arg] = $val;
        }

        $proxy = $this->getMockBuilder($originalClassName)
            ->setConstructorArgs($constructorArguments)
            ->enableProxyingToOriginalMethods();

        if (!empty($existingMethods)) {
            $proxy = $proxy->onlyMethods(array_keys($existingMethods));
        }

        if (!empty($addMethods)) {
            $proxy = $proxy->addMethods(array_keys($addMethods));
        }

        $proxy = $proxy->getMock();

        foreach ($existingMethods as $method => $methodValue) {
            $proxy->method($method)->willReturn($methodValue);
        }

        foreach ($addMethods as $method => $methodValue) {
            $proxy->method($method)->willReturn($methodValue);
        }

        return $proxy;
    }
}
