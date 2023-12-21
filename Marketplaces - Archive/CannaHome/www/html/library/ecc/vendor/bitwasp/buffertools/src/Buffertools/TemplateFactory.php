<?php

namespace BitWasp\Buffertools;

use BitWasp\Buffertools\Types\ByteString;
use BitWasp\Buffertools\Types\Int128;
use BitWasp\Buffertools\Types\Int16;
use BitWasp\Buffertools\Types\Int256;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\Int64;
use BitWasp\Buffertools\Types\Int8;
use BitWasp\Buffertools\Types\Uint8;
use BitWasp\Buffertools\Types\Uint16;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\Uint64;
use BitWasp\Buffertools\Types\Uint128;
use BitWasp\Buffertools\Types\Uint256;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\VarString;
use BitWasp\Buffertools\Types\Vector;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\MathAdapterInterface;

class TemplateFactory
{
    /**
     * @var MathAdapterInterface
     */
    private $math;

    /**
     * @var \BitWasp\Buffertools\Template
     */
    private $template;

    /**
     * @param Template $template
     * @param MathAdapterInterface $math
     */
    public function __construct(Template $template = null, MathAdapterInterface $math = null)
    {
        $this->math = $math ?: EccFactory::getAdapter();
        $this->template = $template ?: new Template();
    }

    /**
     * Return the Template as it stands.
     *
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Add a Uint8 serializer to the template
     *
     * @return $this
     */
    public function uint8()
    {
        $this->template->addItem(new Uint8($this->math, ByteOrder::BE));

        return $this;
    }

    /**
     * Add a little-endian Uint8 serializer to the template
     *
     * @return $this
     */
    public function uint8le()
    {
        $this->template->addItem(new Uint8($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a Uint16 serializer to the template
     *
     * @return $this
     */
    public function uint16()
    {
        $this->template->addItem(new Uint16($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Uint16 serializer to the template
     *
     * @return $this
     */
    public function uint16le()
    {
        $this->template->addItem(new Uint16($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a Uint32 serializer to the template
     *
     * @return $this
     */
    public function uint32()
    {
        $this->template->addItem(new Uint32($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Uint32 serializer to the template
     *
     * @return $this
     */
    public function uint32le()
    {
        $this->template->addItem(new Uint32($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a Uint64 serializer to the template
     *
     * @return $this
     */
    public function uint64()
    {
        $this->template->addItem(new Uint64($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Uint64 serializer to the template
     *
     * @return $this
     */
    public function uint64le()
    {
        $this->template->addItem(new Uint64($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a Uint128 serializer to the template
     *
     * @return $this
     */
    public function uint128()
    {
        $this->template->addItem(new Uint128($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Uint128 serializer to the template
     *
     * @return $this
     */
    public function uint128le()
    {
        $this->template->addItem(new Uint128($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a Uint256 serializer to the template
     *
     * @return $this
     */
    public function uint256()
    {
        $this->template->addItem(new Uint256($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Uint256 serializer to the template
     *
     * @return $this
     */
    public function uint256le()
    {
        $this->template->addItem(new Uint256($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int8 serializer to the template
     *
     * @return $this
     */
    public function int8()
    {
        $this->template->addItem(new Int8($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int8 serializer to the template
     *
     * @return $this
     */
    public function int8le()
    {
        $this->template->addItem(new Int8($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int16 serializer to the template
     *
     * @return $this
     */
    public function int16()
    {
        $this->template->addItem(new Int16($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int16 serializer to the template
     *
     * @return $this
     */
    public function int16le()
    {
        $this->template->addItem(new Int16($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int32 serializer to the template
     *
     * @return $this
     */
    public function int32()
    {
        $this->template->addItem(new Int32($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int serializer to the template
     *
     * @return $this
     */
    public function int32le()
    {
        $this->template->addItem(new Int32($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int64 serializer to the template
     *
     * @return $this
     */
    public function int64()
    {
        $this->template->addItem(new Int64($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int64 serializer to the template
     *
     * @return $this
     */
    public function int64le()
    {
        $this->template->addItem(new Int64($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int128 serializer to the template
     *
     * @return $this
     */
    public function int128()
    {
        $this->template->addItem(new Int128($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int128 serializer to the template
     *
     * @return $this
     */
    public function int128le()
    {
        $this->template->addItem(new Int128($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a int256 serializer to the template
     *
     * @return $this
     */
    public function int256()
    {
        $this->template->addItem(new Int256($this->math, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian Int256 serializer to the template
     *
     * @return $this
     */
    public function int256le()
    {
        $this->template->addItem(new Int256($this->math, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a VarInt serializer to the template
     *
     * @return $this
     */
    public function varint()
    {
        $this->template->addItem(new VarInt($this->math));
        return $this;
    }

    /**
     * Add a VarString serializer to the template
     *
     * @return $this
     */
    public function varstring()
    {
        $this->template->addItem(new VarString(new VarInt($this->math), ByteOrder::BE));
        return $this;
    }

    /**
     * Add a byte string serializer to the template. This serializer requires a length to
     * pad/truncate to.
     *
     * @param  $length
     * @return $this
     */
    public function bytestring($length)
    {
        $this->template->addItem(new ByteString($this->math, $length, ByteOrder::BE));
        return $this;
    }

    /**
     * Add a little-endian byte string serializer to the template. This serializer requires
     * a length to pad/truncate to.
     *
     * @param  $length
     * @return $this
     */
    public function bytestringle($length)
    {
        $this->template->addItem(new ByteString($this->math, $length, ByteOrder::LE));
        return $this;
    }

    /**
     * Add a vector serializer to the template. A $readHandler must be provided if the
     * template will be used to deserialize a vector, since it's contents are not known.
     *
     * The $readHandler should operate on the parser reference, reading the bytes for each
     * item in the collection.
     *
     * @param  callable $readHandler
     * @return $this
     */
    public function vector(callable $readHandler)
    {
        $this->template->addItem(new Vector(new VarInt($this->math), $readHandler));
        return $this;
    }
}
