<?php

namespace Inneair\Search\Stemmer\Snowball;

use Inneair\Search\Stemmer\Snowball;

/**
 * The English Snowball stemmer.
 *
 * @var string _vowels The English vowels.
 * @var array _doubleConsonants The English double consonants.
 * @var string _liEnding Letters that may directly appear before a word final 'li'.
 * @var array _step0Suffixes Suffixes to be deleted in step 0 of the algorithm.
 * @var array _step1aSuffixes Suffixes to be deleted in step 1a of the algorithm.
 * @var array _step1bSuffixes Suffixes to be deleted in step 1b of the algorithm.
 * @var array _step2Suffixes Suffixes to be deleted in step 2 of the algorithm.
 * @var array _step3Suffixes Suffixes to be deleted in step 3 of the algorithm.
 * @var array _step4Suffixes Suffixes to be deleted in step 4 of the algorithm.
 * @var array _step5Suffixes Suffixes to be deleted in step 5 of the algorithm.
 * @var array _specialWords A dictionary containing words which have to be stemmed specially.
 *
 * @note: A detailed description of the English stemming algorithm can be found under
 *  http://snowball.tartarus.org/algorithms/english/stemmer.html.
 */
class English extends Snowball
{
    protected $_vowels = 'aeiouy';

    protected $_doubleConsonants = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');

    protected $_liEnding = 'cdeghkmnrt';

    protected $_step0Suffixes = array("'s'", "'s", "'");

    protected $_step1aSuffixes = array('sses', 'ied', 'ies', 'us', 'ss', 's');

    protected $_step1bSuffixes = array('eedly', 'ingly', 'edly', 'eed', 'ing', 'ed');

    protected $_step2Suffixes = array(
        'ization', 'ational', 'fulness', 'ousness', 'iveness', 'tional', 'biliti', 'lessli',
        'entli', 'ation', 'alism', 'aliti', 'ousli', 'iviti', 'fulli',
        'enci', 'anci', 'abli', 'izer', 'ator', 'alli', 'bli', 'ogi', 'li'
    );

    protected $_step3Suffixes = array('ational', 'tional', 'alize', 'icate', 'iciti', 'ative', 'ical', 'ness', 'ful');

    protected $_step4Suffixes = array(
        'ement', 'ance', 'ence', 'able', 'ible', 'ment', 'ant', 'ent', 'ism', 'ate', 'iti', 'ous', 'ive', 'ize',
        'ion', 'al', 'er', 'ic'
    );

    protected $_step5Suffixes = array('e', 'l');

    protected $_specialWords = array(
        'skis' => 'ski',
        'skies' => 'sky',
        'dying' => 'die',
        'lying' => 'lie',
        'tying' => 'tie',
        'idly' => 'idl',
        'gently' => 'gentl',
        'ugly' => 'ugli',
        'early' => 'earli',
        'only' => 'onli',
        'singly' => 'singl',
        'sky' => 'sky',
        'news' => 'news',
        'howe' => 'howe',
        'atlas' => 'atlas',
        'cosmos' => 'cosmos',
        'bias' => 'bias',
        'andes' => 'andes',
        'inning' => 'inning',
        'innings' => 'inning',
        'outing' => 'outing',
        'outings' => 'outing',
        'canning' => 'canning',
        'cannings' => 'canning',
        'herring' => 'herring',
        'herrings' => 'herring',
        'earring' => 'earring',
        'earrings' => 'earring',
        'proceed' => 'proceed',
        'proceeds' => 'proceed',
        'proceeded' => 'proceed',
        'proceeding' => 'proceed',
        'exceed' => 'exceed',
        'exceeds' => 'exceed',
        'exceeded' => 'exceed',
        'exceeding' => 'exceed',
        'succeed' => 'succeed',
        'succeeds' => 'succeed',
        'succeeded' => 'succeed',
        'succeeding' => 'succeed'
    );

    /**
     * Stem an English word and return the stemmed form.
     *
     * @param string $word The word that is stemmed.
     * @return string The stemmed form.
     */
    public function stem($word)
    {
        $word = mb_strtolower($word);

        if (mb_strlen($word) <= 2) {
            return $word;
        }

        if (array_key_exists($word, $this->_specialWords)) {
            return $this->_specialWords[$word];
        }

        # Map the different apostrophe characters to a single consistent one
        $word = str_replace(array("\u2019", "\u2018", "\u201B"), "\x27", $word);

        if ("\x27" == mb_substr($word, 0, 1)) {
            $word = mb_substr($word, 1);
        }

        if ('y' == mb_substr($word, 0, 1)) {
            $word = 'Y' . mb_substr($word, 1);
        }

        $word = preg_replace('`([' . $this->_vowels . '])y`u', '$1Y', $word);

        //$step1aVowelFound = false;
        //$step1bVowelFound = false;

        //$r1 = mb_strlen($word);
        $r2 = mb_strlen($word);

        if (preg_match('`^(gener|commun|arsen)`u', $word)) {
            if (preg_match('`^(gener|arsen)`u', $word)) {
                $r1 = 5;
            } else {
                $r1 = 6;
            }

            $wordLength = mb_strlen($word);
            for ($i = $r1; $i < $wordLength; ++$i) {
                if (!$this->_is_vowel(mb_substr($word, $i, 1)) && $this->_is_vowel(mb_substr($word, $i - 1, 1))) {
                    $r2 = $i + 1;
                    break;
                }
            }
        } else {
            list($r1, $r2) = $this->_r1r2($word);
        }

        # STEP 0
        foreach ($this->_step0Suffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                $word = mb_substr($word, 0, -mb_strlen($suffix));
                break;
            }
        }

        # STEP 1a
        foreach ($this->_step1aSuffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                if ($suffix == 'sses') {
                    $word = mb_substr($word, 0, -2);
                } elseif (in_array($suffix, array('ied', 'ies'))) {
                    if (mb_strlen(mb_substr($word, 0, -mb_strlen($suffix))) > 1) {
                        $word = mb_substr($word, 0, -2);
                    } else {
                        $word = mb_substr($word, 0, -1);
                    }
                } elseif ($suffix == 's') {
                    if (preg_match('`[' . $this->_vowels . ']`u', mb_substr($word, 0, -2))) {
                        $word = mb_substr($word, 0, -1);
                    }
                }
                # elseif (in_array($suffix, array('us', 'ss'))) { do nothing }

                break;
            }
        }

        # STEP 1b
        foreach ($this->_step1bSuffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                if (in_array($suffix, array('eed', 'eedly'))) {
                    $rs = mb_strrpos($word, $suffix);

                    if ($r1 <= $rs) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ee';
                    }
                } else {
                    if (preg_match('`[' . $this->_vowels . ']`u', mb_substr($word, 0, -mb_strlen($suffix)))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix));

                        if (in_array(mb_substr($word, -2), array('at', 'bl', 'iz'))) {
                            $word .= 'e';
                        } elseif (in_array(mb_substr($word, -2), $this->_doubleConsonants)) {
                            $word = mb_substr($word, 0, -1);
                        } elseif (mb_strlen($word) <= $r1 && (
                                preg_match(
                                    '`[^' . $this->_vowels . '][' . $this->_vowels . '][^' . $this->_vowels . 'wxY]$`',
                                    $word
                                ) ||
                                preg_match('`^[' . $this->_vowels . '][^' . $this->_vowels . ']`', $word))
                        ) {
                            $word .= 'e';
                        }
                    }
                }

                break;
            }
        }

        # STEP 1c
        $word = preg_replace('`(.+[^' . $this->_vowels . '])y$`ui', '$1i', $word);

        # STEP 2
        foreach ($this->_step2Suffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                $rs = mb_strrpos($word, $suffix);

                if ($r1 <= $rs) {
                    if ($suffix == 'tional') {
                        $word = mb_substr($word, 0, -2);
                    } elseif (in_array($suffix, array('enci', 'anci', 'abli'))) {
                        $word = mb_substr($word, 0, -1) . 'e';
                    } elseif ($suffix == 'entli') {
                        $word = mb_substr($word, 0, -2);
                    } elseif (in_array($suffix, array('izer', 'ization'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ize';
                    } elseif (in_array($suffix, array('ational', 'ation', 'ator'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ate';
                    } elseif (in_array($suffix, array('alism', 'aliti', 'alli'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'al';
                    } elseif ($suffix == 'fulness') {
                        $word = mb_substr($word, 0, -4);
                    } elseif (in_array($suffix, array('ousli', 'ousness'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ous';
                    } elseif (in_array($suffix, array('iveness', 'iviti'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ive';
                    } elseif (in_array($suffix, array('biliti', 'bli'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ble';
                    } elseif ($suffix == 'ogi' && mb_substr($word, -4, 1) == 'l') {
                        $word = mb_substr($word, 0, -1);
                    } elseif (in_array($suffix, array('fulli', 'lessli'))) {
                        $word = mb_substr($word, 0, -2);
                    } elseif ($suffix == 'li' && strpbrk($this->_liEnding, mb_substr($word, -3, 1))) {
                        $word = mb_substr($word, 0, -2);
                    }
                }

                break;
            }
        }

        # STEP 3
        foreach ($this->_step3Suffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                $rs = mb_strrpos($word, $suffix);

                if ($r1 <= $rs) {
                    if ($suffix == 'tional') {
                        $word = mb_substr($word, 0, -2);
                    } elseif ($suffix == 'ational') {
                        $word = mb_substr($word, 0, -7) . 'ate';
                    } elseif ($suffix == 'alize') {
                        $word = mb_substr($word, 0, -3);
                    } elseif (in_array($suffix, array('icate', 'iciti', 'ical'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ic';
                    } elseif (in_array($suffix, array('ful', 'ness'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix));
                    } elseif ($suffix == 'ative' && $r2 <= $rs) {
                        $word = mb_substr($word, 0, -5);
                    }
                }

                break;
            }
        }

        # STEP 4
        foreach ($this->_step4Suffixes as $suffix) {
            if ($suffix == mb_substr($word, -mb_strlen($suffix))) {
                $rs = mb_strrpos($word, $suffix);

                if ($r2 <= $rs) {
                    if ($suffix == 'ion') {
                        if (strpbrk('st', mb_substr($word, -4, 1))) {
                            $word = mb_substr($word, 0, -3);
                        }
                    } else {
                        $word = mb_substr($word, 0, -mb_strlen($suffix));
                    }
                }

                break;
            }
        }

        # STEP 5
        $rs = mb_strlen($word) - 1;
        if (mb_substr($word, -1) == 'e') {
            if ($r2 <= $rs) {
                $word = mb_substr($word, 0, -1);
            } elseif ($r1 <= $rs && mb_strlen($word) >= 4 && !preg_match(
                    '`[^' . $this->_vowels . '][' . $this->_vowels . '][^' . $this->_vowels . 'wxY].$`',
                    $word
                )
            ) {
                $word = mb_substr($word, 0, -1);
            }
        } elseif (mb_substr($word, -1) == 'l') {
            if ($r2 <= $rs && mb_substr($word, -2, 1) == 'l') {
                $word = mb_substr($word, 0, -1);
            }
        }

        //str_replace('Y', 'y', $word);
        return mb_strtolower($word);
    }
}
