<?php
/**
 * @copyright 2013 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

use InvalidArgumentException;
use OutOfBoundsException;
use RangeException;

/**
 * Builds a merkle tree of a given width
 *
 * This is used to pre-build the total amount of nodes equal to the total
 * hashes that you would need to calculate a merkle tree without having the
 * data that is going to be hashed yet.
 *
 * For example: say you are downloading chunks of a file out of order. If you
 * know the length of the file, and decide on an appropriate chunk size, you
 * would know the merkle tree width ahead of time. You could construct the
 * tree and start adding data components one at a time. Any subtree that could
 * be hashed will be hashed as soon as possible and references to the
 * underlying nodes broken.
 */
class FixedSizeTree implements ITreeNode
{
    /**
     * @string[]
     */
    private $chunks;

    /**
     * @var int
     */
    private $width;

    /**
     * @var callable
     */
    private $hasher;

    /**
     * @var ITreeNode[]
     */
    private $htree;

    /**
     * @var ITreeNode
     */
    private $treeRoot;

    /**
     * @var callable|null
     */
    private $finished;

    /**
     * Returns a tree of ITreeNode objects of width $width
     *
     * @param int $width
     * @param callable $hasher
     * @todo This must be cleaned up, it is pretty terrible as it stands.
     * @return ITreeNode[]
     * The return is an array of ITreeNode nodes that represent all of the base
     * of the tree in order. Additionally, the 0 index on the array will be the
     * TOP of the tree generated. Having both the base nodes and the top node
     * is all you need for operating on a fixed tree.
     *
     * For example, if you were to build a tree of size 8, you this would
     * return an array of 5 ITreeNodes. The 0 index being the top of this tree
     * (that you should be calling ->hash() on) and the next 4 indicies being
     * instances of TwoChildrenNode objects (each which has the ability to
     * accept two strings as data).
     */
    private static function buildHtreeRow($width, callable $hasher)
    {
        $row = [];
        if ($width === 2) {
            $row[] = new TwoChildrenNode($hasher);
            return $row;
        }

        if ($width === 3) {
            $row[] = new TwoChildrenNode($hasher);
            $row[] = new TwoChildrenNode($hasher);
            $row[] = new OneChildDuplicateNode(new TwoChildrenNode($hasher));
            $row[0]->data($row[1], $row[2]);
            return $row;
        }

        $rowSize = (int) ceil($width / 2);
        $odd = $width % 2;

        for ($i = 0; $i < $rowSize; $i++) {
            $row[] = new TwoChildrenNode($hasher);
        }

        if ($odd) {
            $row[$rowSize - 1] = new OneChildDuplicateNode($row[$rowSize - 1]);
        }

        $parents = self::buildHtreeRow($rowSize, $hasher);
        $treeRoot = array_shift($parents);
        $pRowSize = count($parents);
        array_unshift($row, $treeRoot);

        if ($pRowSize === 0) {
            $row[0]->data($row[1], $row[2]);
            return $row;
        }

        $pOdd = $rowSize % 2;
        foreach ($parents as $i => $parent) {
            $index = ($i * 2) + 1;
            if ($i + 1 === $pRowSize && $pOdd) {
                $parent->data($row[$index]);
                continue;
            }
            $parent->data($row[$index], $row[$index + 1]);
        }

        return $row;
    }

    /**
     * @param int $width
     * @param callable $hasher
     * @param callable|null $finished
     */
    public function __construct($width, callable $hasher, callable $finished = null)
    {
        $this->width = $width;
        $this->chunks = array_fill(0, $width, null);
        $this->hasher = $hasher;
        $this->finished = $finished;
        $this->htree = self::buildHtreeRow($width, $hasher);
        $this->treeRoot = array_shift($this->htree);

        if (count($this->htree) === 0) {
            $this->htree[0] = $this->treeRoot;
        }
    }

    /**
     * @throws UnexpectedValueException
     * @return string|null
     */
    public function hash()
    {
        return $this->treeRoot->hash();
    }

    /**
     * @param int $i
     * @param string $v
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     * @throws RangeException
     * @return null
     */
    public function set($i, $v)
    {
        $this->validateSetInputs($i, $v);
        $this->addChunk($i, $v);
        $odd = $i % 2;
        if ($odd) {
            $i--;
        }

        if ($i + 1 === $this->width) {
            $data = call_user_func($this->hasher, $this->chunks[$i]);
            $idx = (int) ($i / 2);
            $this->htree[$idx]->data($data);
            $this->resolveHashes();
            unset($this->chunks[$i]);
            return;
        }

        if (isset($this->chunks[$i]) && isset($this->chunks[$i + 1])) {
            $first = call_user_func($this->hasher, $this->chunks[$i]);
            $second = call_user_func($this->hasher, $this->chunks[$i + 1]);
            $idx = (int) ($i / 2);
            $this->htree[$idx]->data($first, $second);
            $this->resolveHashes();
            unset($this->chunks[$i]);
            unset($this->chunks[$i + 1]);
        }
    }

    /**
     * @param int $i
     * @param string $v
     * @throws OutOfBoundsException
     * @return null
     */
    private function addChunk($i, $v)
    {
        if (!array_key_exists($i, $this->chunks) || !is_null($this->chunks[$i])) {
            throw new OutOfBoundsException('Cannot call ->set() for the same index more than once');
        }
        $this->chunks[$i] = $v;
    }

    /**
     * @param int $i
     * @param string $v
     */
    private function validateSetInputs($i, $v)
    {
        if (!is_int($i)) {
            throw new InvalidArgumentException('index must be an integer');
        }

        if (!is_string($v)) {
            throw new InvalidArgumentException('value must be a string');
        }

        if ($i < 0 || $i >= $this->width) {
            throw new RangeException("$i must be between 0 and the tree width (minus one)");
        }
    }

    /**
     * @return null
     */
    private function resolveHashes()
    {
        $res = $this->treeRoot->hash();
        if (is_null($res)) {
            return;
        }

        if ($this->finished) {
            call_user_func($this->finished, $res);
        }
    }
}
