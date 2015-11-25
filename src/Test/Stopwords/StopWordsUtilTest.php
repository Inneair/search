<?php

namespace Inneair\Search\Test\Stopwords;

use Inneair\Search\Test\AbstractSearchTest;
use Inneair\Search\Stopwords\StopWordsUtil;

/**
 * Test suite for the StopWordsUtil stem resolution algorithm.
 */
class StopWordsUtilTest extends AbstractSearchTest
{
    public function testFindStopWordsWithNotSupportedCodeLanguage()
    {
        $stopWords = StopWordsUtil::findStopWords('ia');
        $this->assertEquals($stopWords, array());
    }

    public function testRemoveStopWords()
    {
        $frenchStopWords = StopWordsUtil::findStopWords('fr');
        $this->assertNotEmpty($frenchStopWords);

        $word = 'caramel';
        $filteredText = StopWordsUtil::removeStopWords($word . ' ' . implode(' ', $frenchStopWords), 'fr');
        $this->assertEquals($word, trim($filteredText));
    }
}