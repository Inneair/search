<?php

namespace Inneair\Search\Test\Stemmer;

use Inneair\Search\Test\AbstractSearchTest;
use Inneair\Search\Stemmer\Snowball;

/**
 * Test suite for the Snowball stem resolution algorithm.
 */
class SnowballTest extends AbstractSearchTest
{
    /**
     * Dictionary of stemmer words in french.
     * @var string
     */
    const STEMMER_DICTIONNARY_FILENAME_FR = 'diffs-fr.txt';
    /**
     * Dictionary of stemmer words in english.
     * @var string
     */
    const STEMMER_DICTIONNARY_FILENAME_EN = 'diffs-en.txt';

    /**
     * {@inheritdoc}
     */
    public function clean()
    {
        parent::clean();
    }

    /**
     * Checks the french stem resolution.
     */
    public function testFrStemmer()
    {
        $fs = Snowball::factory(array('language' => 'french'));

        $diffs = file('../data/test/' . static::STEMMER_DICTIONNARY_FILENAME_FR);

        $words = [];
        $stems = [];
        foreach ($diffs as $diff) {
            list($word, $stems[]) = mb_split('\s+', $diff);
            $words[] = $fs->stem($word);
        }
        $this->assertSame($stems, $words);
    }

    /**
     * Checks the english stem resolution.
     */
    public function testEnStemmer()
    {
        $fs = Snowball::factory(array('language' => 'english'));

        $diffs = file('../data/test/' . static::STEMMER_DICTIONNARY_FILENAME_EN);

        $words = [];
        $stems = [];
        foreach ($diffs as $diff) {
            list($word, $stems[]) = mb_split('\s+', $diff);
            $words[] = $fs->stem($word);
        }
        $this->assertSame($stems, $words);
    }
}
