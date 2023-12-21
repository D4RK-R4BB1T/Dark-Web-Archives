<?php
/**
 * @copyright 2013 Matthew Nagi
 * @license http://opensource.org/licenses/BSD-2-Clause BSD 2-Clause License
 */

namespace Pleo\Merkle;

/**
 * A node in a Merkle Tree
 *
 * In general, a node will have references to its children. It will genreally
 * not have references to its parents.
 */
interface ITreeNode
{
    /**
     * Generates the hash for this Merkle Tree node
     *
     * Generally if this node has references to nodes below it, it will call
     * the hash function on those nodes as well. The idea is that calling
     * ->hash() from the top level node of a tree will hash the entire tree.
     *
     * @throws UnexpectedValueException Most ITreeNodes actually use a
     *    user-supplied callback function to do the hashing. If anything goes
     *    wrong on that front, an UnexpectedValueException may be thrown.
     * @return string|null Returns the hash of this node in the Merkle Tree or
     *    null if the hash can’t be generated yet (perhaps data hasn’t been set
     *    further down the tree).
     */
    public function hash();
}
