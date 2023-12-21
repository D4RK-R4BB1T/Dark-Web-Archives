<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */







class Sequence extends Construct implements Parsable
{
    public function getType()
    {
        return Identifier::SEQUENCE;
    }
}
