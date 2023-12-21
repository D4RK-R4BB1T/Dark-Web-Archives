<?php

namespace BitWasp\CommonTrait;

trait FunctionAliasArrayAccess
{
    /*
     * Expose ArrayAccess, using aliases to map functions on your object.
     * The parent class needs to implement \ArrayAccess itself/
     * The parent class will need to call init() (as it's private otherwise)
     */

    /**
     * @var array
     */
    private $map = [];

    /**
     * @param string $alias
     * @param string $function
     * @return $this
     */
    private function initFunctionAlias($alias, $function)
    {
        if (!is_callable([$this, $function])) {
            throw new \InvalidArgumentException('Function must be callable on parent class');
        }

        $this->map[$alias] = $function;

        return $this;
    }

    /**
     * @param string $offset
     */
    private function checkOffset($offset)
    {
        if (!isset($this->map[$offset])) {
            throw new \InvalidArgumentException('This offset does not exist');
        }
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->checkOffset($offset);
        $function = $this->map[$offset];
        return $this->$function();
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->map[$offset]);
    }

    /**
     *
     */
    private function errorNoWriting()
    {
        throw new \RuntimeException('Instace is immutable, no writes allowed');
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->errorNoWriting();
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->errorNoWriting();
    }
}
