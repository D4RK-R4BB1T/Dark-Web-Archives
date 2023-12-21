<?php

namespace BitWasp\Secp256k1Tests;

class Secp256k1ContextTest extends TestCase
{
    public function testContext()
    {
        $ctx = \secp256k1_context_create(SECP256K1_CONTEXT_VERIFY);
        $clone = \secp256k1_context_clone($ctx);

        // We should have two resources of type secp256k1_context_t
        $this->assertInternalType('resource', $ctx);
        $this->assertInternalType('resource', $ctx);
        $this->assertEquals(SECP256K1_TYPE_CONTEXT, get_resource_type($ctx));
        $this->assertEquals(SECP256K1_TYPE_CONTEXT, get_resource_type($clone));

        // We should be able to destroy it (without affecting the other), and see it's type is now unknown.
        $this->assertTrue(\secp256k1_context_destroy($ctx));
        $this->assertEquals('Unknown', get_resource_type($ctx));
        $this->assertEquals(SECP256K1_TYPE_CONTEXT, get_resource_type($clone));

    }
}
