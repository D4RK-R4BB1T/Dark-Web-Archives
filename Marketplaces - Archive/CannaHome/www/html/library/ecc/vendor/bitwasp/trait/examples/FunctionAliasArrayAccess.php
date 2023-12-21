<?php

require "../vendor/autoload.php";

// Demonstrates use of FunctionAliasArrayAccess
// Class needs to implement \ArrayAccess itself
// And initialize mapping

class Info implements \ArrayAccess {

    use \BitWasp\CommonTrait\FunctionAliasArrayAccess;

    public function __construct()
    {
        $this
            ->initFunctionAlias('name', 'getName')
            ->initFunctionAlias('coffee', 'getCoffee')
            ->initFunctionAlias('age', 'getAge');
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Harold';
    }

    /**
     * @return int
     */
    public function getAge()
    {
        return 99;
    }

    /**
     * @return string
     */
    public function getCoffee()
    {
        return 'black';
    }
}

$info = new Info();

echo $info['coffee'] . PHP_EOL;

