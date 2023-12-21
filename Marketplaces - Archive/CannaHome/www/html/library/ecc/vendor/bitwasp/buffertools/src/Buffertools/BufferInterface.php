<?php

namespace BitWasp\Buffertools;

interface BufferInterface
{
    /**
     * @param integer $start
     * @param integer|null $end
     * @return BufferInterface
     * @throws \Exception
     */
    public function slice($start, $end = null);

    /**
     * Get the size of the buffer to be returned
     *
     * @return int
     */
    public function getSize();

    /**
     * Get the size of the value stored in the buffer
     *
     * @return int
     */
    public function getInternalSize();

    /**
     * @return string
     */
    public function getBinary();

    /**
     * @return string
     */
    public function getHex();

    /**
     * @return int|string
     */
    public function getInt();

    /**
     * @return Buffer
     */
    public function flip();

    /**
     * @return bool
     */
    public function equals(self $other);
}
