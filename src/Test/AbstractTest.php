<?php

namespace Inneair\Search\Test;

use PHPUnit_Framework_TestCase;

/**
 * Base class for PHPUnit tests.
 */
abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * A message that shall be used in assertions, when an exception did not occur.
     * @var string
     */
    const ASSERT_EXPECTED_EXCEPTION_MESSAGE = 'Expected exception did not occur.';

    /**
     * Initializes environment for this test execution.
     */
    public function setUp()
    {
        // Performs additional setup.
        $this->afterSetUp();

        // Clean this test environment
        $this->clean();
    }

    /**
     * Performs additional setup.
     *
     * This method may be overridden by concrete tests, to perform additional setup tasks before test execution.
     * This method is called after the root setup, and before cleaning the environment.
     */
    protected function afterSetUp()
    {
    }

    /**
     * Releases environment resources allocated by this test.
     */
    public function tearDown()
    {
        // Clean this test environment
        $this->clean();
    }

    /**
     * Cleans the environment before/after a unitary test execution.
     *
     * This method may be overridden by concrete tests, to perform additional cleaning tasks before/after the execution.
     * This method is called after the setup is done, and before the teardown is done, in the base class. Sub-classes
     * shall always call this parent method before any statements.
     */
    protected function clean()
    {
    }

    /**
     * Asserts an exception occurred, by testing a flag, and, if not, fails with an explicit message.
     *
     * @param bool $hasException If an exception occurred.
     */
    protected function assertException($hasException)
    {
        $this->assertTrue($hasException, static::ASSERT_EXPECTED_EXCEPTION_MESSAGE);
    }
}
