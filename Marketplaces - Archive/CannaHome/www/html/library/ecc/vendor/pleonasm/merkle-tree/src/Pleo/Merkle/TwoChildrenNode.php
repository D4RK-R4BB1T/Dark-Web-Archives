<?php
/**
 * @copyright 2013 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

class TwoChildrenNode implements ITreeNode
{
    const ERR_SETTYPE = '->data() can only be passed two strings or two instances of TwoChildrenNode';

    /**
     * @var callable|null
     */
    private $hasher;

    /**
     * @var string|ITreeNode|null|boolean
     */
    private $first;

    /**
     * @var string|ITreeNode|null|boolean
     */
    private $second;

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @param callable $hasher
     */
    public function __construct(callable $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * @return string|null
     */
    public function hash()
    {
        if ($this->hash) {
            return $this->hash;
        }

        if (is_null($this->first) && is_null($this->second)) {
            return null;
        }

        $first = $this->first;
        $second = $this->second;
        if ($this->first instanceof ITreeNode) {
            $first = $this->first->hash();
            $second = $this->second->hash();
            if ($first === null || $second === null) {
                return null;
            }
        }

        $hash = call_user_func($this->hasher, $first . $second);

        if (!is_string($hash)) {
            throw new UnexpectedValueException('Hash callback must return a string.');
        }

        $this->hash = $hash;
        $this->first = false;
        $this->second = false;
        return $this->hash;
    }

    /**
     * @param string|ITreeNode $first
     * @param string|ITreeNode $second
     * @throws LogicException
     * @throws InvalidArgumentException
     * @return null
     */
    public function data($first, $second)
    {
        if ($this->first && $this->second || $this->first === false && $this->second === false) {
            throw new LogicException('You cannot set data twice');
        }

        if (is_string($first) && is_string($second) || $first instanceof ITreeNode && $second instanceof ITreeNode) {
            $this->first = $first;
            $this->second = $second;
            return;
        }

        throw new InvalidArgumentException(self::ERR_SETTYPE);
    }
}
