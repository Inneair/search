<?php

namespace Inneair\Search\Test;

/**
 * Base class for all PHPUnit tests in the Search library, and providing methods to access the data directory.
 */
abstract class AbstractSearchTest extends AbstractTest
{
    /**
     * Absolute path of the data directory.
     * @var string
     */
    protected $dataPath;
    /**
     * Absolute path prefix of the data directory, ending with a directory separator.
     * @var string
     */
    protected $dataPathPrefix;
    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->dataPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . static::getRelativePathToData();
        $this->dataPathPrefix = $this->dataPath . DIRECTORY_SEPARATOR;

        // Go on with the setup
        parent::setUp();
    }
    /**
     * Gets the relative path to the directory containing test data.
     *
     * @return string Path.
     */
    protected static function getRelativePathToData()
    {
        return '../data/test';
    }
    /**
     * Gets the absolute path to the data sub-directory containing test resources, under the library's root.
     *
     * @return string Path.
     */
    protected function getDataPath()
    {
        return $this->dataPath;
    }
    /**
     * Gets the absolute path to the data sub-directory containing test resources, under the library's root, for use as
     * a path prefix. The prefix ends with a directory separator, so as it can be used immediately.
     *
     * @return string Path ending with a directory separator, useful to prefix a file name.
     */
    protected function getDataPathPrefix()
    {
        return $this->dataPathPrefix;
    }
}
