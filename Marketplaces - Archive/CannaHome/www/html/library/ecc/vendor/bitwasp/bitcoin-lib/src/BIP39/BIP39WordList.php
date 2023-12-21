<?php

namespace BitWasp\BitcoinLib\BIP39;

abstract class BIP39WordList
{

    /**
     * get a list of all the words
     *
     * @return array
     */
    abstract public function getWords();

    /**
     * get a word by it's index
     *
     * should throw an exception if the index does not exist
     *
     * @param int       $idx
     * @return string
     */
    abstract public function getWord($idx);

    /**
     * get the index for a word
     *
     * should throw an exception if the word does not exist
     *
     * @param string    $word
     * @return int
     */
    abstract public function getIndex($word);
}
