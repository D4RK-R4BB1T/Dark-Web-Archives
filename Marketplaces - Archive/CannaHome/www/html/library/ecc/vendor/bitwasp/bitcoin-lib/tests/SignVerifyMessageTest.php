<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\Jsonrpcclient;

class SignVerifyMessageTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        // ensure we're set to bitcoin and not bitcoin-testnet
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function testSignMessage()
    {
        $k = "40830342147156906673307227534819286677883886097095155210766904187107130350230"; // fixed K value for testing

        $WIF = "KxuKf1nB3nZ5eYVFVuCgvH5EFM8iUSWqmqJ9bAQukekYgPbju4FL";
        $privKey = BitcoinLib::WIF_to_private_key($WIF);

        $sig = BitcoinLib::signMessage('12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm', $privKey, $k);

        $this->assertEquals("H2LFY1Qm5w7xSlnluYovpPYgiFT8kqot/SJOho5f7R8CWtpkLMGAFac/S4kDzah76y2tjfirGNpKhxWw6Ki5RpU=", $sig);
        $this->assertTrue(BitcoinLib::verifyMessage('12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm', $sig, '12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm'));

        $sig = BitcoinLib::signMessage('12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm', $privKey);

        $this->assertTrue(!!$sig);
        $this->assertTrue(BitcoinLib::verifyMessage('12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm', $sig, '12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm'));

        $this->assertTrue(BitcoinLib::verifyMessage("12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm", "IHDCaEP3MZQcOOn1hp/nAbYFf9KOSoLi+TCWNFDV2+j+SkVSFYZHHJjfwwYP02Xlf7aIOZdI5ZzJZetLpnDp9H8=", "12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm"));
    }

    public function testSignMessageFailure()
    {
        $e = null;
        try {
            BitcoinLib::verifyMessage("mkiPAxhzUMo8mAwW3q95q7aNuXt6HzbbUA", "IND22TSMS2uuWyIn2Be49ajaGwNmiQtiCXrozev00cPFXpACe8LQYU/t6xp8YXb5SIVAnqEn/DailZw+OM85TM0=", "mkiPAxhzUMo8mAwW3q95q7aNuXt6HzbbUA");
        } catch (\Exception $e) {
            $this->assertEquals("invalid Bitcoin address", $e->getMessage());
        }
        $this->assertTrue(!!$e, "verifyMessage should throw exception when address is invalid");

        $this->assertFalse(BitcoinLib::verifyMessage("12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm", "IHDCaEP3MZQcOOn1hp/nAbYFf9KOSoLi+TCWNFDV2+j+SkVSFYZHHJjfwwYP02Xlf7aIOZdI5ZzJZetLpnDp9H7=", "12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm"));

        $e = null;
        try {
            $this->assertFalse(BitcoinLib::verifyMessage("12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm", "I22CEEP3MZQcOOn1hp/nAbYFf9KOSoLi+TCWNFDV2+j+SkVSFYZHHJjfwwYP02Xlf7aIOZdI5ZzJZetLpnDp9H8=", "12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm"));
        } catch (\Exception $e) {
            $this->assertEquals("invalid signature type", $e->getMessage());
        }
        $this->assertTrue(!!$e, "verifyMessage should throw exception when signature is invalid");

        $e = null;
        try {
            $this->assertFalse(BitcoinLib::verifyMessage("12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm", "IH2CEEP3MZQcOOn1hp/nAbYFf9KOSoLi+TCWNFDV2+j+SkVSFYZHHJjfwwYP02Xlf7aIOZdI5ZzJZetLpnDp9H8=", "12XJYLMM9ZoDZjmBZ1SeFANhgCVjwNYgVm"));
        } catch (\Exception $e) {
            $this->assertEquals("unable to recover key", $e->getMessage());
        }
        $this->assertTrue(!!$e, "verifyMessage should throw exception when signature is invalid");
    }

    public function testSignMessageTestnet()
    {
        BitcoinLib::setMagicByteDefaults('bitcoin-testnet');
        $this->assertTrue(BitcoinLib::verifyMessage("mkiPAxhzUMo8mAwW3q95q7aNuXt6HzbbUA", "IND22TSMS2uuWyIn2Be49ajaGwNmiQtiCXrozev00cPFXpACe8LQYU/t6xp8YXb5SIVAnqEn/DailZw+OM85TM0=", "mkiPAxhzUMo8mAwW3q95q7aNuXt6HzbbUA"));
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function testVerifyMessageDataSet()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 10;

        $data = json_decode(file_get_contents(__DIR__ . "/data/signverify.json"), true);
        $data = array_map(function ($k) use ($data) {
            return $data[$k];
        }, array_rand($data, $cnt));

        foreach ($data as $row) {
            $this->assertTrue(BitcoinLib::verifyMessage($row['address'], $row['signature'], $row['address']));
        }
    }

    public function testSignMessageDataSet()
    {
        // special case, when undefined we do 1, otherwise we do ENV * 5 (50 on travis)
        $cnt = getenv('BITCOINLIB_EXTENSIVE_TESTING') ? (getenv('BITCOINLIB_EXTENSIVE_TESTING') * 5) : 1;

        $data = json_decode(file_get_contents(__DIR__ . "/data/signverify.json"), true);
        $data = array_map(function ($k) use ($data) {
            return $data[$k];
        }, (array)array_rand($data, $cnt));

        foreach ($data as $row) {
            $privKey = BitcoinLib::WIF_to_private_key($row['wif']);
            $signature = BitcoinLib::signMessage($row['address'], $privKey);
            $this->assertTrue(!!$signature);
            $this->assertTrue(BitcoinLib::verifyMessage($row['address'], $signature, $row['address']));
        }
    }

    public function testSignMessageDataSetAgainstRPC()
    {
        if (!getenv('BITCOINLIB_TEST_AGAINST_RPC')) {
            return $this->markTestSkipped("Not testing against RPC");
        }

        // special case, when undefined we do 1, otherwise we do ENV * 5 (50 on travis)
        $cnt = getenv('BITCOINLIB_EXTENSIVE_TESTING') ? (getenv('BITCOINLIB_EXTENSIVE_TESTING') * 5) : 1;

        $rpcHost = getenv('BITCOINLIB_RPC_HOST') ?: '127.0.0.1';
        $rpcUser = getenv('BITCOINLIB_RPC_USER') ?: 'bitcoinrpc';
        $rpcPassword = getenv('BITCOINLIB_RPC_PASSWORD') ?: '6Wk1SYL7JmPYoUeWjYRSdqij4xrM5rGBvC4kbJipLVJK';

        $rpc = new Jsonrpcclient(['url' => "http://{$rpcUser}:{$rpcPassword}@{$rpcHost}:8332"]);

        if ($rpc->getinfo() == null) {
            $this->fail("Can't connect to bitcoind");
        }

        $data = json_decode(file_get_contents(__DIR__ . "/data/signverify.json"), true);
        $data = array_map(function ($k) use ($data) {
            return $data[$k];
        }, (array)array_rand($data, $cnt));

        foreach ($data as $row) {
            $privKey = BitcoinLib::WIF_to_private_key($row['wif']);
            $signature = BitcoinLib::signMessage($row['address'], $privKey);
            $this->assertTrue(!!$signature);
            $this->assertTrue(BitcoinLib::verifyMessage($row['address'], $signature, $row['address']));

            $this->assertTrue($rpc->verifymessage($row['address'], $signature, $row['address']));
        }
    }
}
