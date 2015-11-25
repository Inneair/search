<?php

namespace Inneair\Search\Stopwords;

/**
 * This class provide text management utilities.
 */
class StopWordsUtil
{
    public static $stopWords = null;

    /**
     *
     * @param string $lg Code language
     * @return array
     */
    public static function findStopWords($lg)
    {
        if (isset(static::$stopWords[$lg])) {
            return static::$stopWords[$lg];
        }

        $swPath = __DIR__ . '/Resources/' . $lg . '.txt';
        if (file_exists($swPath)) {
            return static::$stopWords[$lg] = array_map('trim', file($swPath));
        }

        return static::$stopWords[$lg] = array();
    }

    /**
     * Remove stop words from given content.
     *
     * @param string $content Content to filter
     * @param string $lg Code language
     * @return string
     */
    public static function removeStopWords($content, $lg)
    {
        $contents = static::removeStopWordsMultiple(array($content), $lg);

        return array_shift($contents);
    }

    /**
     * Remove stop words from given contents.
     *
     * @param array $contents Array of contents to filter
     * @param string $lg Code language
     * @return array
     */
    public static function removeStopWordsMultiple(array $contents, $lg)
    {
        $stopWords = static::findStopWords($lg);

        if (!empty($stopWords)) {
            foreach ($contents as & $content) {
                $key = trim(preg_replace('`((^|\s)(' . implode('|', $stopWords) . '))+(\s|$)`uis', ' ', $content));
                if (!empty($key)) {
                    $content = $key;
                }
            }
        }

        return $contents;
    }
}