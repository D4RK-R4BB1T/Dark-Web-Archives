<?php

namespace BitWasp\Bitcoin\Transaction\Mutator;

use BitWasp\Bitcoin\Collection\MutableCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class InputCollectionMutator extends MutableCollection
{

    /**
     * @param TransactionInputInterface[] $inputs
     */
    public function __construct(array $inputs)
    {
        /** @var InputMutator[] $set */
        $set = [];
        foreach ($inputs as $i => $input) {
            $set[$i] = new InputMutator($input);
        }

        $this->set = \SplFixedArray::fromArray($set, false);
    }

    /**
     * @return InputMutator
     */
    public function current()
    {
        return $this->set->current();
    }

    /**
     * @param int $offset
     * @return InputMutator
     */
    public function offsetGet($offset)
    {
        if (!$this->set->offsetExists($offset)) {
            throw new \OutOfRangeException('Input does not exist');
        }

        return $this->set->offsetGet($offset);
    }

    /**
     * @return TransactionInputCollection
     */
    public function done()
    {
        $set = [];
        foreach ($this->set as $mutator) {
            $set[] = $mutator->done();
        }

        return new TransactionInputCollection($set);
    }

    /**
     * @param int|string $start
     * @param int|string $length
     * @return $this
     */
    public function slice($start, $length)
    {
        $end = $this->set->getSize();
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->set = \SplFixedArray::fromArray(array_slice($this->set->toArray(), $start, $length), false);
        return $this;
    }

    /**
     * @return $this
     */
    public function null()
    {
        $this->slice(0, 0);
        return $this;
    }

    /**
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function add(TransactionInputInterface $input)
    {
        $size = $this->set->getSize();
        $this->set->setSize($size + 1);

        $this->set[$size] = new InputMutator($input);
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function set($i, TransactionInputInterface $input)
    {
        $this->set[$i] = new InputMutator($input);
        return $this;
    }
}
