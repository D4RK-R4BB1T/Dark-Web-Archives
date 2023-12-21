<?php

namespace Mdanter\Ecc\Serializer\PublicKey;

use Mdanter\Ecc\Crypto\Key\PublicKeyInterface;

/**
 *
 * @link https://tools.ietf.org/html/rfc5480#page-3
 */
class PemPublicKeySerializer implements PublicKeySerializerInterface
{

    private $derSerializer;

    /**
     * @param DerPublicKeySerializer $serializer
     */
    public function __construct(DerPublicKeySerializer $serializer)
    {
        $this->derSerializer = $serializer;
    }

    /**
     *
     * @param  PublicKeyInterface $key
     * {@inheritDoc}
     * @see \Mdanter\Ecc\Serializer\PublicKey\PublicKeySerializerInterface::serialize()
     */
    public function serialize(PublicKeyInterface $key)
    {
        $publicKeyInfo = $this->derSerializer->serialize($key);

        $content  = '-----BEGIN PUBLIC KEY-----'.PHP_EOL;
        $content .= trim(chunk_split(base64_encode($publicKeyInfo), 64, PHP_EOL)).PHP_EOL;
        $content .= '-----END PUBLIC KEY-----';

        return $content;
    }

    /**
     * {@inheritDoc}
     * @see \Mdanter\Ecc\Serializer\PublicKey\PublicKeySerializerInterface::parse()
     */
    public function parse($formattedKey)
    {
        $formattedKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $formattedKey);
        $formattedKey = str_replace('-----END PUBLIC KEY-----', '', $formattedKey);

        $data = base64_decode($formattedKey);

        return $this->derSerializer->parse($data);
    }
}
