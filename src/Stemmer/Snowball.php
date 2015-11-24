<?php

namespace Inneair\Search\Stemmer;

use Exception;

/**
 * A word stemmer based on the Snowball stemming algorithms.
 *
 * At the moment, this port is able to stem words from fourteen
 * languages: Danish, Dutch, English, Finnish, French, German,
 * Hungarian, Italian, Norwegian, Portuguese, Romanian, Russian,
 * Spanish and Swedish.
 *
 * Furthermore, there is also the original English Porter algorithm:
 *
 *    Porter, M. \"An algorithm for suffix stripping.\"
 *    Program 14.3 (1980): 130-137.
 *
 * The algorithms have been developed by
 * Dr Martin Porter <http://tartarus.org/~martin/>.
 * These stemmers are called Snowball, because he invented
 * a programming language with this name for creating
 * new stemming algorithms. There is more information available
 * on the Snowball Website <http://snowball.tartarus.org/>.
 *
 * This subclass encapsulates two methods for defining the standard versions
 * of the string regions R1, R2, and RV.
 */
class Snowball
{
    /**
     * The vowels of the respective language
     *
     * @var string
     */
    protected $_vowels;

    /**
     * The type of page to use when it was not set
     *
     * @var string
     */
    protected static $_defaultStemmerLanguage;

    /**
     * Factory for Zend_Navigation_Page classes
     *
     * A specific type to construct can be specified by specifying the key
     * 'type' in $options. If type is 'uri' or 'mvc', the type will be resolved
     * to Zend_Navigation_Page_Uri or Zend_Navigation_Page_Mvc. Any other value
     * for 'type' will be considered the full name of the class to construct.
     * A valid custom page class must extend Zend_Navigation_Page.
     *
     * If 'type' is not given, the type of page to construct will be determined
     * by the following rules:
     * - If $options contains either of the keys 'action', 'controller',
     *   'module', or 'route', a Zend_Navigation_Page_Mvc page will be created.
     * - If $options contains the key 'uri', a Zend_Navigation_Page_Uri page
     *   will be created.
     *
     * @param  array $options options used for creating page
     * @return Snowball a stemmer instance
     * @throws Exception if $options is not array
     * @throws Exception if 'language' is specified and Autoload is unable to load the class
     * @throws Exception if something goes wrong during instantiation of the stemmer
     * @throws Exception if 'language' is given, and the specified language does not extend this class
     * @throws Exception if unable to determine which class to instantiate
     */
    public static function factory($options)
    {
        if (!is_array($options)) {
            throw new Exception('Invalid argument: $options must be an array');
        }

        if (isset($options['language'])) {
            $language = $options['language'];
        } elseif (static::getDefaultStemmerLanguage() != null) {
            $language = static::getDefaultStemmerLanguage();
        }

        if (isset($language) && is_string($language) && !empty($language)) {
            $language = ucfirst(strtolower($language));
            $class = 'Inneair\Search\Stemmer\Snowball\\' . $language;

            $page = new $class($options);
            if (!$page instanceof Snowball) {
                throw new Exception(sprintf(
                    'Invalid argument: Detected type "%s", which is not an instance of '
                    . 'Inneair\Search\Stemmer\Snowball',
                    $language
                ));
            }
            return $page;
        }

        throw new Exception('Invalid argument: Unable to determine class to instantiate');
    }

    public function __construct()
    {
        if (!function_exists('mb_internal_encoding')) {
            // mbstring extension is disabled
            throw new Exception('Utf8 compatible lower case filter needs mbstring extension to be enabled.');
        }

        if (PHP_VERSION_ID < 50600) {
            mb_internal_encoding('UTF-8');
        } else {
            ini_set('default_charset', 'UTF-8');
        }
    }

    /**
     * Return the standard interpretations of the string regions R1 and R2.
     *
     * R1 is the region after the first non-vowel following a vowel,
     * or is the null region at the end of the word if there is no
     * such non-vowel.
     *
     * R2 is the region after the first non-vowel following a vowel
     * in R1, or is the null region at the end of the word if there
     * is no such non-vowel.
     *
     * @param string $word The word whose regions R1 and R2 are determined.
     * @return array($r1, $r2) The regions R1 and R2 for the respective word.
     *
     * @note A detailed description of how to define R1 and R2 can be found
     * under http://snowball.tartarus.org/texts/r1r2.html.
     */
    protected function _r1r2($word)
    {
        $r1 = mb_strlen($word);
        $r2 = mb_strlen($word);

        $wordLength = mb_strlen($word);
        for ($i = 1; $i < $wordLength; ++$i) {
            if (!$this->_is_vowel(mb_substr($word, $i, 1)) &&
                $this->_is_vowel(mb_substr($word, $i - 1, 1))
            ) {
                $r1 = $i + 1;
                break;
            }
        }

        for ($i = $r1; $i < $wordLength; ++$i) {
            if (!$this->_is_vowel(mb_substr($word, $i, 1)) &&
                $this->_is_vowel(mb_substr($word, $i - 1, 1))
            ) {
                $r2 = $i + 1;
                break;
            }
        }

        return array($r1, $r2);
    }

    /**
     * Return the standard interpretation of the string region RV.
     *
     * If the second letter is a consonant, RV is the region after the
     * next following vowel. If the first two letters are vowels, RV is
     * the region after the next following consonant. Otherwise, RV is
     * the region after the third letter.
     *
     * @param string $word The word whose region RV is determined.
     * @return int $rv the region RV for the respective word.
     */
    protected function _rv($word)
    {
        $rv = mb_strlen($word);

        if (2 <= $rv) {
            if (!$this->_is_vowel(mb_substr($word, 1, 1))) {
                $wordLength = mb_strlen($word);
                for ($i = 2; $i < $wordLength; ++$i) {
                    if ($this->_is_vowel(mb_substr($word, $i, 1))) {
                        $rv = $i + 1;
                        break;
                    }
                }
            } elseif ($this->_is_vowel(mb_substr($word, 0, 1))) {
                $wordLength = mb_strlen($word);
                for ($i = 1; $i < $wordLength; ++$i) {
                    if (!$this->_is_vowel(mb_substr($word, $i, 1))) {
                        $rv = $i + 1;
                        break;
                    }
                }
            } else {
                $rv = 3;
            }
        }

        return $rv;
    }

    /**
     * Check if the given character is a vowel
     *
     * @param string $char The character
     * @return bool
     */
    protected function _is_vowel($char)
    {
        return (bool)preg_match('`^[' . $this->_vowels . ']+$`u', $char);
    }

    public static function setDefaultStemmerLanguage($language = null)
    {
        if ($language !== null && !is_string($language)) {
            throw new Exception('Cannot set default page type: type is no string but should be');
        }

        static::$_defaultStemmerLanguage = $language;
    }

    public static function getDefaultStemmerLanguage()
    {
        return static::$_defaultStemmerLanguage;
    }
}
