<?php

namespace Inneair\Search\Stemmer\Snowball;

use Inneair\Search\Stemmer\Snowball;

/**
 * The French Snowball stemmer.
 *
 * @var string $_vowels The French vowels.
 * @var array $_step1Suffixes Suffixes to be deleted in step 1 of the algorithm.
 * @var array $_step2aSuffixes Suffixes to be deleted in step 2a of the algorithm.
 * @var array $_step2bSuffixes Suffixes to be deleted in step 2b of the algorithm.
 * @var array $_step4Suffixes Suffixes to be deleted in step 4 of the algorithm.
 *
 * @note: A detailed description of the French stemming algorithm can be found
 * under http://snowball.tartarus.org/algorithms/french/stemmer.html.
 */
class French extends Snowball
{
    protected $_vowels = 'aeiouyâàëéêèïîôûù';

    protected $_step1Suffixes = array(
        'issements', 'issement', 'atrices', 'atrice', 'ateurs', 'ations', 'logies', 'usions',
        'utions', 'ements', 'amment', 'emment', 'ances', 'iqUes', 'ismes', 'ables', 'istes',
        'ateur', 'ation', 'logie', 'usion', 'ution', 'ences', 'ement', 'euses', 'ments', 'ance',
        'iqUe', 'isme', 'able', 'iste', 'ence', 'ités', 'ives', 'eaux', 'euse', 'ment',
        'eux', 'ité', 'ive', 'ifs', 'aux', 'if'
    );

    protected $_step2aSuffixes = array(
        'issaIent', 'issantes', 'iraIent', 'issante', 'issants', 'issions', 'irions', 'issais',
        'issait', 'issant', 'issent', 'issiez', 'issons', 'irais', 'irait', 'irent', 'iriez',
        'irons', 'iront', 'isses', 'issez', 'îmes', 'îtes', 'irai', 'iras', 'irez', 'isse',
        'ies', 'ira', 'ît', 'ie', 'ir', 'is', 'it', 'i'
    );

    protected $_step2bSuffixes = array(
        'eraIent', 'assions', 'erions', 'assent', 'assiez', 'èrent', 'erais', 'erait',
        'eriez', 'erons', 'eront', 'aIent', 'antes', 'asses', 'ions', 'erai', 'eras',
        'erez', 'âmes', 'âtes', 'ante', 'ants', 'asse', 'ées', 'era', 'iez', 'ais',
        'ait', 'ant', 'ée', 'és', 'er', 'ez', 'ât', 'ai', 'as', 'é', 'a'
    );

    protected $_step4Suffixes = array('ière', 'Ière', 'ion', 'ier', 'Ier', 'e', 'ë');

    /**
     * Stem a French word and return the stemmed form.
     *
     * @param string $word The word that is stemmed.
     * @return string The stemmed form.
     */
    public function stem($word)
    {
        $word = mb_strtolower($word);

        $step1Success = false;
        $step2aSuccess = false;
        $step2bSuccess = false;
        $rvEndingFound = false;

        # Every occurrence of 'u' after 'q' is put into upper case.
        for ($i = 1; $i < mb_strlen($word); ++$i) {
            if (mb_substr($word, $i - 1, 1) == 'q' && mb_substr($word, $i, 1) == 'u') {
                $word = mb_substr($word, 0, $i) . 'U' . mb_substr($word, $i + 1);
            }
        }
        # Every occurrence of 'u' and 'i' between vowels is put into upper case.
        # Every occurrence of 'y' preceded or followed by a vowel is also put into upper case.
        for ($i = 1; $i < mb_strlen($word) - 1; ++$i) {
            if ($this->_is_vowel(mb_substr($word, $i - 1, 1)) && $this->_is_vowel(mb_substr($word, $i + 1, 1))) {
                if (mb_substr($word, $i, 1) == 'u') {
                    $word = mb_substr($word, 0, $i) . 'U' . mb_substr($word, $i + 1);
                } elseif (mb_substr($word, $i, 1) == 'i') {
                    $word = mb_substr($word, 0, $i) . 'I' . mb_substr($word, $i + 1);
                }
            }

            if ($this->_is_vowel(mb_substr($word, $i - 1, 1)) || $this->_is_vowel(mb_substr($word, $i + 1, 1))) {
                if (mb_substr($word, $i, 1) == 'y') {
                    $word = mb_substr($word, 0, $i) . 'Y' . mb_substr($word, $i + 1);
                }
            }
        }

        list($r1, $r2) = $this->_r1r2($word);
        $rv = $this->_rv($word);

        # STEP 1: Standard suffix removal
        foreach ($this->_step1Suffixes as $suffix) {
            if (preg_match('`' . $suffix . '$`u', $word)) {
                $rs = mb_strrpos($word, $suffix);

                if ($suffix == 'eaux') {
                    # replace with 'eau'
                    $word = mb_substr($word, 0, -1);
                    $step1Success = true;
                } elseif (in_array($suffix, array('euse', 'euses'))) {
                    # delete if in R2,
                    if ($r2 <= $rs) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix));
                        $step1Success = true;
                    # else replace by 'eux' if in R1
                    } elseif ($r1 <= $rs) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'eux';
                        $step1Success = true;
                    }
                } elseif (in_array($suffix, array('ement', 'ements')) && $rv <= $rs) {
                    # delete if in RV
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;

                    # if preceded by 'iv', delete if in R2 (and if further preceded by 'at', delete if in R2),
                    if (mb_substr($word, -2) == 'iv' && $r2 <= mb_strrpos($word, 'iv')) {
                        $word = mb_substr($word, 0, -2);

                        if (mb_substr($word, -2) == 'at' && $r2 <= mb_strrpos($word, 'at')) {
                            $word = mb_substr($word, 0, -2);
                        }
                    # otherwise, if preceded by 'eus', delete if in R2, else replace by 'eux' if in R1,
                    } elseif (mb_substr($word, -3) == 'eus') {
                        if ($r2 <= mb_strrpos($word, 'eus')) {
                            $word = mb_substr($word, 0, -3);
                        } elseif ($r1 <= mb_strrpos($word, 'eus')) {
                            $word = mb_substr($word, 0, -1) . 'x';
                        }
                    # otherwise, if preceded by 'abl' or 'iqU', delete if in R2,
                    } elseif (in_array(mb_substr($word, -3), array('abl', 'iqU'))) {
                        if ($r2 <= mb_strrpos($word, 'abl') || $r2 <= mb_strrpos($word, 'iqU')) {
                            $word = mb_substr($word, 0, -3);
                        }
                    # otherwise, if preceded by 'ièr' or 'Ièr', replace by 'i' if in RV
                    } elseif (in_array(mb_substr($word, -3), array('ièr', 'Ièr'))) {
                        if ($rv <= mb_strrpos($word, 'ièr') || $rv <= mb_strrpos($word, 'Ièr')) {
                            $word = mb_substr($word, 0, -3) . 'i';
                        }
                    }
                } elseif ($suffix == 'amment' && $rv <= $rs) {
                    # replace with 'ant' if in RV
                    $word = mb_substr($word, 0, -6) . 'ant';
                    $step1Success = true;
                    $rvEndingFound = true;
                } elseif ($suffix == 'emment' && $rv <= $rs) {
                    # replace with 'ent' if in RV
                    $word = mb_substr($word, 0, -6) . 'ent';
                    $step1Success = true;
                    $rvEndingFound = true;
                } elseif (in_array($suffix, array('ment', 'ments'))
                    && $rv <= ($rs - 1)
                    && $this->_is_vowel(mb_substr($word, $rs - 1, 1))
                ) {
                    # delete if preceded by a vowel in RV
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;
                    $rvEndingFound = true;
                } elseif ($suffix == 'aux' && $r1 <= $rs) {
                    # replace with 'al' if in R1
                    $word = mb_substr($word, 0, -2) . 'l';
                    $step1Success = true;
                } elseif (in_array($suffix, array('issement', 'issements'))
                    && $r1 <= $rs
                    && !$this->_is_vowel(mb_substr($word, $rs - 1, 1))
                ) {
                    # delete if in R1 and preceded by a non-vowel
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;
                } elseif (in_array(
                        $suffix,
                        array(
                            'ance',
                            'iqUe',
                            'isme',
                            'able',
                            'iste',
                            'eux',
                            'ances',
                            'iqUes',
                            'ismes',
                            'ables',
                            'istes'
                        )
                    ) && ($r2 <= $rs)
                ) {
                    # delete if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;
                } elseif (in_array(
                        $suffix,
                        array('atrice', 'ateur', 'ation', 'atrices', 'ateurs', 'ations')
                    ) && $r2 <= $rs
                ) {
                    # delete if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;

                    # if preceded by 'ic', delete if in R2, else replace by 'iqU'
                    if (mb_substr($word, -2) == 'ic') {
                        if ($r2 <= mb_strrpos($word, 'ic')) {
                            $word = mb_substr($word, 0, -2);
                        } else {
                            $word = mb_substr($word, 0, -2) . 'iqU';
                        }
                    }
                } elseif (in_array($suffix, array('logie', 'logies')) && $r2 <= $rs) {
                    # replace with 'log' if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'log';
                    $step1Success = true;
                } elseif (in_array($suffix, array('usion', 'ution', 'usions', 'utions')) && $r2 <= $rs) {
                    # replace with 'u' if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'u';
                    $step1Success = true;
                } elseif (in_array($suffix, array('ence', 'ences')) && $r2 <= $rs) {
                    # replace with 'ent' if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'ent';
                    $step1Success = true;
                } elseif (in_array($suffix, array('ité', 'ités')) && $r2 <= $rs) {
                    # delete if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;

                    # if preceded by 'abil', delete if in R2, else replace by 'abl',
                    if (mb_substr($word, -4) == 'abil') {
                        if ($r2 <= mb_strrpos($word, 'abil')) {
                            $word = mb_substr($word, 0, -4);
                        } else {
                            $word = mb_substr($word, 0, -2) . 'l';
                        }
                    # otherwise, if preceded by 'ic', delete if in R2, else replace by 'iqU',
                    } elseif (mb_substr($word, -2) == 'ic') {
                        if ($r2 <= mb_strrpos($word, 'ic')) {
                            $word = mb_substr($word, 0, -2);
                        } else {
                            $word = mb_substr($word, 0, -1) . 'qU';
                        }
                    # otherwise, if preceded by 'iv', delete if in R2
                    } elseif (mb_substr($word, -2) == 'iv') {
                        if ($r2 <= mb_strrpos($word, 'iv')) {
                            $word = mb_substr($word, 0, -2);
                        }
                    }
                } elseif (in_array($suffix, array('if', 'ive', 'ifs', 'ives')) && $r2 <= $rs) {
                    # delete if in R2
                    $word = mb_substr($word, 0, -mb_strlen($suffix));
                    $step1Success = true;

                    # if preceded by 'at', delete if in R2 (and if further
                    # preceded by 'ic', delete if in R2, else replace by 'iqU')
                    if (mb_substr($word, -2) == 'at' && $r2 <= mb_strrpos($word, 'at')) {
                        $word = mb_substr($word, 0, -2);

                        if (mb_substr($word, -2) == 'ic') {
                            if ($r2 <= mb_strrpos($word, 'ic')) {
                                $word = mb_substr($word, 0, -2);
                            } else {
                                $word = mb_substr($word, 0, -1) . 'qU';
                            }
                        }
                    }
                }

                break;
            }
        }

        # In steps 2a and 2b all tests are confined to the RV region.
        /*
         * STEP 2a: Verb suffixes beginning 'i'
         * Do step 2a if either no ending was removed by step 1, or if one of
         * endings 'amment', 'emment', 'ment', 'ments' was found.
         */
        if (!$step1Success || $rvEndingFound) {
            foreach ($this->_step2aSuffixes as $suffix) {
                if (preg_match('`' . str_repeat('.', $rv) . $suffix . '$`u', $word)) {
                    $rs = mb_strrpos($word, $suffix);

                    # delete if preceded by a non-vowel
                    # (Note that the non-vowel itself must also be in RV.)
                    if ($rv <= ($rs - 1) && !$this->_is_vowel(mb_substr($word, $rs - 1, 1))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix));
                        $step2aSuccess = true;
                    }

                    break;
                }
            }

            /*
             * STEP 2b: Other verb suffixes
             * Do step 2b if step 2a was done, but failed to remove a suffix.
             */
            if (!$step2aSuccess) {
                foreach ($this->_step2bSuffixes as $suffix) {
                    if (preg_match('`' . str_repeat('.', $rv) . $suffix . '$`u', $word)) {
                        $rs = mb_strrpos($word, $suffix);

                        # delete if in R2
                        if ($suffix == 'ions' && $r2 <= $rs) {
                            $word = mb_substr($word, 0, -4);
                            $step2bSuccess = true;
                        # delete
                        } elseif (in_array(
                            $suffix,
                            array('eraIent', 'erions', 'èrent',
                            'erais', 'erait', 'eriez', 'erons', 'eront', 'erai', 'eras',
                            'erez', 'ées', 'era', 'iez', 'ée', 'és', 'er', 'ez', 'é')
                        )) {
                            $word = mb_substr($word, 0, -mb_strlen($suffix));
                            $step2bSuccess = true;
                        # delete, if preceded by 'e', delete
                        } elseif (in_array(
                            $suffix,
                            array('assions', 'assent', 'assiez',
                            'aIent', 'antes', 'asses', 'âmes', 'âtes', 'ante', 'ants',
                            'asse', 'ais', 'ait', 'ant', 'ât', 'ai', 'as', 'a')
                        )) {
                            $word = mb_substr($word, 0, -mb_strlen($suffix));
                            $step2bSuccess = true;

                            if (mb_substr($word, -1) == 'e' && $rv <= mb_strrpos($word, 'e')) {
                                $word = mb_substr($word, 0, -1);
                            }
                        }

                        break;
                    }
                }
            }
        }

        /*
         * STEP 3
         * If the last step to be obeyed — either step 1, 2a or 2b — altered
         * the word, do step 3
         */
        if ($step1Success || $step2aSuccess || $step2bSuccess) {
            # Replace final Y with i or final ç with c
            if (mb_substr($word, -1) == 'Y') {
                $word = mb_substr($word, 0, -1) . 'i';
            } elseif (mb_substr($word, -1) == 'ç') {
                $word = mb_substr($word, 0, -1) . 'c';
            }

        /*
         * STEP 4: Residual suffixes
         * Alternatively, if the last step to be obeyed did not alter the word, do step 4
         */
        } else {
            # If the word ends 's', not preceded by 'a', 'i', 'o', 'u', 'è' or 's', delete it.
            $word = preg_replace('`([^aiouès])s$`u', '$1', $word);
            /*if (mb_strlen($word) >= 2 && mb_substr($word, -1) == 's' && strpbrk('aiouès', mb_substr($word, -2, 1))) {
             $word = mb_substr($word, 0, -1);
            }*/

            # In the rest of step 4, all tests are confined to the RV region.
            # Search for the longest among the following suffixes, and perform the action indicated.
            foreach ($this->_step4Suffixes as $suffix) {
                if (preg_match('`' . str_repeat('.', $rv) . $suffix . '$`u', $word)) {
                    $rs = mb_strrpos($word, $suffix);

                    # delete if in R2 and preceded by 's' or 't'
                    # (So note that 'ion' is removed only when it is in R2 —
                    # as well as being in RV — and preceded by 's' or 't'
                    # which must be in RV.)
                    if ($suffix == 'ion' && $r2 <= $rs && strpbrk('st', mb_substr($word, -4, 1))) {
                        $word = mb_substr($word, 0, -3);
                    # replace with 'i'
                    } elseif (in_array($suffix, array('ier', 'ière', 'Ier', 'Ière'))) {
                        $word = mb_substr($word, 0, -mb_strlen($suffix)) . 'i';
                    # delete
                    } elseif ($suffix == 'e') {
                        $word = mb_substr($word, 0, -1);
                    # if preceded by 'gu', delete
                    // preceded by 'gu' in $rv or in $word ?
                    } elseif ($suffix == 'ë' && mb_substr($word, -3, -1) == 'gu') {
                        $word = mb_substr($word, 0, -1);
                    }

                    break;
                }
            }
        }

        /*
         * STEP 5: Undouble
         */
        # If the word ends 'enn', 'onn', 'ett', 'ell' or 'eill', delete the last letter.
        if (preg_match('`(enn|onn|ett|ell|eill)$`', $word)) {
            $word = mb_substr($word, 0, -1);
        }

        /*
         * STEP 6: Un-accent
         */
        # If the words ends 'é' or 'è' followed by at least one non-vowel, remove the accent from the 'e'.
        $word = preg_replace('`(?:é|è)([^' . $this->_vowels . ']+)$`u', 'e$1', $word);
        /*for ($i = 1; $i < mb_strlen($word); ++$i) {
            if (!$this->_is_vowel(mb_substr($word, -$i, 1))) {
                if ($i != 1 && in_array(mb_substr($word, -$i - 1, 1), array('é', 'è'))) {
                    $word = mb_substr($word, 0, -$i) .'e'. mb_substr($word, -$i + 1);
                }
                break;
            }
        }*/

        /*
         * Finally
         */
        # Turn any remaining I, U and Y letters in the word back into lower case.
        //strtr($word, 'IUY', 'iuy');
        return mb_strtolower($word);
    }

    /**
     * Return the region RV that is used by the French stemmer.
     *
     * If the word begins with two vowels, RV is the region after
     * the third letter. Otherwise, it is the region after the first
     * vowel not at the beginning of the word, or the end of the word
     * if these positions cannot be found. (Exceptionally, 'par',
     * 'col' or 'tap' at the beginning of a word is also taken to
     * define RV as the region to their right.)
     *
     * @param string $word The French word whose region RV is determined.
     * @return int $rv The region RV for the respective French word.
     */
    protected function _rv($word)
    {
        $rv = mb_strlen($word);

        if (mb_strlen($word) >= 2) {
            if (preg_match('`^(par|col|tap)`u', $word)
                || ($this->_is_vowel(mb_substr($word, 0, 1))
                    && $this->_is_vowel(mb_substr($word, 1, 1)))
            ) {
                $rv = 3;
            } else {
                $wordLength = mb_strlen($word);
                for ($i = 1; $i < $wordLength; ++$i) {
                    if ($this->_is_vowel(mb_substr($word, $i, 1))) {
                        $rv = $i + 1;
                        break;
                    }
                }
            }
        }

        return $rv;
    }
}
