<?php

use BitWasp\BitcoinLib\BitcoinLib;
use BitWasp\BitcoinLib\RawTransaction;
use Mdanter\Ecc\EccFactory;

class RawTransactionTest extends PHPUnit_Framework_TestCase
{

    public function setup()
    {
        // ensure we're set to bitcoin and not bitcoin-testnet
        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function tearDown()
    {
        
    }

    public function testDecodeRedeemScript()
    {
        // Random samples from txs in the blockchain
        $samples = [
            "5221032c6aa78662cc43a3bb0f8f850d0c45e18d0a49c61ec69db87e072c88d7a9b6e9210353581fd2fc745d17264af8cb8cd507d82c9658962567218965e750590e41c41e21024fe45dd4749347d281fd5348f56e883ee3a00903af899301ac47ba90f904854f53ae",
            "5141048ff228400b3056084121fa83658c43858e3826d59ddc6cfd033df80565f8cc96e09e26e6a2320958a999cb82030d781698176a591424cf4f66b5644e7c8e690a41040f63d3a4b5d797b8ceb443ae54a45b7bb0465a792844e104e31eda18f8a0b0b8f42adaac0002bac7aae6a5cd4106be45172463e9caf44aa567da486455063d1152ae",
            "524104664547a29ffc51db8d46377c1c6611914e11a9c36a83992e642b6c3aa1f0017eee1d77d6d0e4f3b82be92067b39d7f62fe2da7b680e3e306402bf53f8dd5ef04410496cb89ca7f8f244808b4a50ee80e9acd12f801d33646aa9a50eb4a6b550f6520b4204a79f0192a30fc8c7b0eef18d49b3b720373f6b2b928023ae4b463c2c4eb52ae",
            "522102c33586c016c66158f921c54e30628d682627839d0297f800050b981ab298b58a2102bd4d0115f28b4ac3122e89ebab628f1de2db0a3947721f2fe9b17f9b96e2204052ae",
            "524104192944fe1ecf2bb78174a282a3b7d3a457bb40993a6785853cfcedf5bb6f67fd453db59b4e2cfd864480c86d816b383b0be51f82444f7bca0cdaa12e002b25da4104738bcb941b0ca59dad113393c212be91b4bb648efce44567bd372ffac64c6f373d2d928a34c6d1d98fced6a6b03852fc592eef7534d23b801a714e11f1a061ac410491ccd85240d31e84e40586d0bf10a1f1e9d309bffac8e179534be2a28310e92c486d9c7e47ee76e23f6b6f390075e16cf08b6ce9c3d2383c5a3a20b6598d224e53ae",
            "52410453e1bb5aa30c3711ac64ca03a38d7f248db3b31a2559efa8a4cb6fa63b5356e9d2cb8c26b722d7f984cc51ba6bfceb2e5306249f6ba626e73de1d412f2e8d4594104be2c03fe4f9aa55dea54812822a563138b4c12651ce3d1161b246c47379838ae1f7a0df55e6b475c2cddd278bcd130898a21e319e1bc02da86876efb9fcb621c4104cf5189fa8b6457a5e06f652ef8666499574632bc5c75f51fb049dcc93638309f493594e4bd6b1d6ac92f6d77cc52db255b92f20749b237927fb2a60d7c887a1053ae",
            "5241048c1bbabfd38a9d462443e9bd99e133f512efa9548743ffb3f544dcc83b9b0356780796678519987ba04a054445e0cea2a60f1cafc1dbcc97c412338771b094374104bf5d3c29bad79db8f541b0d1095ef1f8ba4e8eda75936dcec81ac72599121b561f2b4ce9a2947cf0a5498888ee8e2a918c7e595da8fa1ae5b28df607e5fc29bb4104d79d37c0f322442bea954bb3fda3a97b24b113152a638316a1fbd44673e28967cefca7c3ed366ed41bbc0978dc308efab89f6e9010912d1e6c3fed0d4dc2b63d53ae",
            "5121031b9abc9a11a4a078509969f12de3129bf81060b001742e05cd8041a7e7402bcb51ae",
            "5241043a0f00454bdcd92698bebd22de0785c8dd190144115b3eced47f26796e24c3050faa3cb3b46a5a868a8f77f7f08ddda7c90952ca07bc18fc9765b8f0bc28cdd941044d7e454064678f032c6a845a986a41e20ae224a7843eb397637e7d1df2c80762b0a14d348cb2a4261cf83fba77d1e025a9eaaf251e1a23f9caabb4c9c5fba5314104ba5b7a9ce88ee36d8689e6a70e847b88d2ddcd12a008195f5b841f88485e14d6a3abc4120e721a50655ce3ba63606ba7ac927254f89ec3d0dea3bfe1d7b2797653ae",
            "5241046bcd91262b52419bdb0a88cc628e6eeed8ca7c60857a04be80c9cef320a775881d01b439cefc6e814f713c753be596fb65b5575f9e99583cd50776474e80adc14104b730f446201772dbc67d39f72fe9ef2e966066718070a4248874fb237a325dfa43073a893f95ae713d40194bac5d6cb23d25616e0966286c9c7416afe7214ac84104b31a45c0362e0c56e868bb7563334b3b35eb0f4af810648e45e365c46eaf983a155b6ec834db5143a7df47780fbf7e9f6ac5c3d45837b6c76b0c9c4c864feee453ae",
            "524104f10d42e5a5dc24dabe524dc305f9deab2c5b58c42aa83833d1d2ad38c9b796b4756a99e60bfee19e92424f3aa919fb63377b6d8d35d091d6a5de62b58b8c8d7721025563ee4b549f5a7d3dfb046a088815c69ebd44b389a78342ccb01e77d89465a752ae",
            "522102c48bf0c25bf78fbe98988ef8f27ee05284f79c550e987a88e1237872c030ab8021026f6020210361586f02c93cfb926fed3d240b7f11b1a5694b36c826561abd3932210352057bbf5c024edd3630606689956c52104150d1c494b1d92ca6c7243d5eeb7453ae",
            "522102d7297e56bc410c2d1671cd2694686400f59061bef1bbb6f7e3269dacbbdce53e2102421a4efafabd2534116deb6c00a01831de71c62c53a5e718b852ccd81a7ff98c52ae",
            "522102d7297e56bc410c2d1671cd2694686400f59061bef1bbb6f7e3269dacbbdce53e2102421a4efafabd2534116deb6c00a01831de71c62c53a5e718b852ccd81a7ff98c52ae",
            "52210374763369da3dca422b64e13ae1cf81a5f68dd09a1efcd463b5511fe7a436207a21021d7e25870057383faad795f651b3f5844c4d3ebb1e3a31e444e6a21d9af371642102c5463df6e77f7f659d123ca19e079ef9ec08e1ddfa4a1bcb4814902b2b1ad41c53ae",
            "5241046dae34916bec595e73b3fabfd1aad2158994fee1a5bc19216654dc49b47f1976a26d7a6d3c7b713f8650121812253a206c4cd7f1e7b453e8e700c720e99c60564104fcb3a8fd045eb2c77a45fdfed73317c22b4ab40de5950c944ff8b1cf45eb83aaafc8d987fe1706d10febc6277223569e0df10881b8cea2435b4fa3bdabf41ad021036c06d0a475646980d4ede31b6b4864946fe7fb54fa15102b03ac16a56c4300f053ae",
            "524104d403a9ec23cb289734645337a079c636ecc47b6015ebd88e972741948ddd65a708f8238f1da4b8f70945ae57288a4bc406e3811a0b83b4e610565cc83a5faff741046c82c6671ada6c430d8ea81b27ecae04fa9f0b4ab14bcceb89c589ea0d7b1919565221ef6c2004a6204f1d6d616eb770c13dbda3cdf271f2ae6fe5a654b5a42e2102e3929380fccb603649a8dd775ec2b1e81748b2749503e039530330f1a5b448bf53ae",
            "5221039bf1c1ec550815dee677d1b391a03f5f501abe6fd9817b2486bc03aa7ae038d8410437d0fd54cbbe8d4e6bd12d19b1c863d3cfbce71c4b31541546685c090d9572a2d2f5f30c58d45c7141cf29b7fe83002f29abccc9c290b7dba6eed9e9d773d1bc2102521e92a6b26db346cb8e2a27ceb2ec83905f3f8d3f8d52634c82c3ad8d0f188a53ae"
        ];

        foreach ($samples as $sample) {
            $raw = RawTransaction::decode_redeem_script($sample);
            $this->assertTrue(is_array($raw));
        }
    }

    public function testSignP2SH()
    {
        BitcoinLib::setMagicByteDefaults('bitcoin-testnet');

        $redeem_script = "522103c0b1fd07752ebdd43c75c0a60d67958eeac8d4f5245884477eae094c4361418d2102ab1fae8dacd465460ad8e0c08cb9c25871782aa539a58b65f9bf1264c355d0982102dc43b58ee5313d1969b939718d2c8104a3365d45f12f91753bfc950d16d3e82e53ae";

        $inputs = array(
            array(
                "txid" => "83c5c88e94d9c518f314e30ca0529ab3f8e5e4f14a8936db4a32070005e3b61f",
                "vout" => 0,
                "scriptPubKey" => "a9145fe34588f475c5251ff994eafb691a5ce197d18b87",

                // only needed for RawTransaction::sign
                "redeemScript" => $redeem_script,

                // only for debugging
                "value" => 0.00010000
            )
        );
        $outputs = array(
            "n3P94USXs7LzfF4BKJVyGv2uCfBQRbvMZJ" => BitcoinLib::toSatoshi(0.00010000)
        );
        $raw_transaction = RawTransaction::create($inputs, $outputs);

        /*
         * sign with first key
         */
        $wallet = array();
        RawTransaction::private_keys_to_wallet($wallet, array("cV2BRcdtWoZMSovYCpoY9gyvjiVK5xufpAwdAFk1jdonhGZq1cCm"));
        RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
        $sign = RawTransaction::sign($wallet, $raw_transaction, json_encode($inputs));
        $this->assertEquals(2, $sign['req_sigs']);
        $this->assertEquals(1, $sign['sign_count']);
        $this->assertEquals('false', $sign['complete']);

        /*
         * sign with second key
         */
        $wallet = array();
        RawTransaction::private_keys_to_wallet($wallet, array("cMps8Dg4Z1ThcwvPiPpshR6cbosYoTrgUwgLcFasBSxsdLHwzoUK"));
        RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
        $sign = RawTransaction::sign($wallet, $sign['hex'], json_encode($inputs));
        $this->assertEquals(2, $sign['req_sigs']);
        $this->assertEquals(2, $sign['sign_count']);
        $this->assertEquals('true', $sign['complete']);

        /*
         * sign with third key
         */
        $wallet = array();
        RawTransaction::private_keys_to_wallet($wallet, array("cNn72iUvQhuzZCWg3TC31fvyNDYttL8emHgMcFJzhF4xnFo8LYCk"));
        RawTransaction::redeem_scripts_to_wallet($wallet, array($redeem_script));
        $sign = RawTransaction::sign($wallet, $sign['hex'], json_encode($inputs));
        $this->assertEquals(2, $sign['req_sigs']);
        $this->assertEquals(3, $sign['sign_count']);
        $this->assertEquals('true', $sign['complete']);

        BitcoinLib::setMagicByteDefaults('bitcoin');
    }

    public function testCreateRaw()
    {
        /*$arr = [
            "010000000869c2997e9dd8ce50f9481a33971f0308e8d525ef8bf079f455fc7936d13f375a1f0000008a47304402202de0d834112506ed10549f751ce142094243390f3e035444f105b4764056314302205dfddc421b377c8b089182ddb3928ce02e73c86e5dfb9e66ca6a98810d7a2ac5014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffab9aae4a00230f30795cd928225e9f69f7c0e6146c535b641541cb81aa09b5297b0000008a47304402205604456b1ed6dcae5e5f370568dd71c8bfb44823d583e3fa781ae8117ed831a30220154ee1c49bfca8bd1970953255498cb6bc347de1df267ed1b0f70385bca29184014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffc036184c5aa93f74530359251e6ff63ab9e17ce7eb6f5d467c42675e318696e5010000008a47304402206c756a38757443794196d16e887c95b9b769b11c608b425e7580e4fcd8456642022040613fbff5e5412c8aa0c3a91b651a6fe6fcf793596a5e29bbc7bc5d73fc683a014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff727107bfb37c254f12b4dfdda9ded7544ea6e44298a657ecb1863e00f88e0700110000008c493046022100c24b9c50820d19457fc5842a124500bf2371397144fc6166bdaa4a94275e9dda022100fc6c59286cfdc2fca4cca62d0a1b4e7dd8125a18aee59e630877f85101aa441a014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5fffffffffb862000126a61dba547524f7302bc68ea177cf00f7a49c5b834c0fd397c4dfe390000008b483045022100c837ec9105ddaa75250a38e9942a624754ecacb025feb024adfa8a77914e6eff0220538a1407d7ed8b7417421113ee29c3d9699f87780907b4121068740f1eb31d68014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffdb35b57c65cf0fcdea54bfdebda43942d728001a06a9df0404a07801c87ccd8a090000008b483045022100b00faaa1b6a7c1cbe4c47791e302bbcbf1d4fee54cb3d1195d82ef05df0f8f0702207382b68bb44e8fadb0ae7b44f22a18df93e8e8e000d28c88f4fdff78d92a8316014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff539ff8e14dab98db92dbbaff03dda50136470bb30bf9fb5b844eb356bc31362d1f0000008b48304502205e41d2112c190396173562d7957b16e0bba64a40adbe466cfe98778d30d91fa20221009f62ac2e345dbd16639b4269c86f156c050e5747013d2a452cdd8c25b8c9aeb9014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffbc0dabbf27d0a58f0e2f5a36c11e7f270737ddc92ef0d8d1baacdeff24f39ed7110000008a473044021f3cfe420bd8a5baca342a3df2501f03165e8b8b76fea6fbc82dd05047c99fcd022100bfa829bce78d4f463abefbba943ea54dbbd931b5d7712e263eaf76d2779cf053014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff023011e100000000001976a91431b07b8df3c19573388bb688b4fd89f6233f5d7988acf4f31400000000001976a914d17e062579b71bfe199a80991a253d929f8bd35b88ac00000000",
            "01000000074829be5251cac2c2f6bc5bb71b37a7cd57504408d42010b4a924dc4dd60dabba000000006b483045022100921b883c6a42e14a3718fa2b0b9cc72225c761121710fd380e8ffc25766b3f8302200c436640eeeb6dded3950d9782bb101ca6ebd6f3a7371a4f94f4d769d0e09292012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff7841ac6b34686946bff2996ee7a8c347efc4906f565ca15dec507faa085ed8a9000000006a47304402207499620cb0e7db680df261b10beca91988746389b51664f0906f30a8e96a7db60220410ca07f12a3edfb1521bd5c9b18c83f6ccc2ced62ff1fb910b4d2a4ad73ef16012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff7806bbe4b6e2b43c677f86845cee3e43d43e4e6da2f387cd15b3fcbafde436fb000000006a47304402201200e9c0a2a452f59ce94fff83128b1795cb2d241a79cd49ea90e56972e70d320220403640d7d103366a2b2f1e0782c3bbb5166c7947b500303b448e8fde27c02987012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff129ef9ae1bf2382bd123005102723eaeab4cd9a2287286c1286db74a836c68d6000000006a47304402206392c220d6cd142e9423d559791cc318f3be87d91049d6ee76fdeddec69ceb0d02201240b24be64041ef6a3b0ae94935b23d357a8dbcb1e379ee7d2a58e33b66e72a012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff2d918344bedaabd0a8406368518e2ba0ac0f48edd5733ef17909dfab6146789d000000006b483045022100bbdd6943c2233340de453f44f17641846df5d0e319782af48965eee9a2f40de80220304f7b200086b519b4f9b583c598e949f63df96539706391b813f0262a94beb4012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0efffffffffa83a3af885f23269b4fe583d38510a9902924399816ede03a95160c3cb87b3f000000006a4730440220397bfa4185f41910f80d18bc9ef29cb9583299b668175cf1fd01c0f62176ffc302206d11ba8807a7de0033553a44ef5535f100e62158f04e2cff7140506afef761de012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff3a0645bd4adcd0094d1e3c06104dd5037f53f14b7f533dacf5c3c02ffdd41a90000000006b4830450221009accebcf34cda23a9ded8dacaa6f80c87eaa734c2bc80b8aeaab4fe9b7ef9a1502202ce314930e89cee1a527daed448a52fe11bb77867aa5a8e33b07e24a2246ae96012103417cff182cd9886e693868f474d26af984e9af82b3d83116eb5bb591bbb87e0effffffff02676df004000000001976a9141713a60c250ad7e1b909baf09c3291257eef381188acbbde2500000000001976a914e3cf40e4218cdf11d9f3f339035a4b0937ba0a5888ac00000000",
            "01000000049c90b31e3ea1b3f157b64c6c12832292b3da372c23458fab097f660728f8d72b010000006a473044022008cc581b5a99c35683beb551770ffbebad6605f84a35118c25cc1d7685b5aeb402207b158c110b72cd635213eed7305ca762922ec464583c2d5dfb0f4e43562250b10121022235905741eb82e063b9736a3ced9e3686c0ca4fb14c9bb6e0c8cbdf308cf41dffffffffdb16fbc52a0b77fa466f4501b7dc8af2d9218e32d852f0a6a77e3d0063acb8f9000000006a47304402206c441ed1020c8a36ad6edd1f0c7296ee3d2f390cfca8893973c0714073a9c9cc022023ac27df5e8017c1933847dcbcb471e1349941a81bc83e98b358eea54f01d20a012103829a75e07a0c490f413455798cc7f4a6330f9a403491e063b44862f542b622caffffffff9e65c29ac0ae80db55c72915ecef387f2d2c4ad066694e6b6e3ea5868cb788b6010000006b483045022100a7544728bf36ad8e80927caae7fe6d8d74129f03ba37a841edf8984cc467fd2602203e2603ecf5fdd56e4ed5cde8034ee731535e980e4b84e2759471f7df76cda164012103d8959922eba9a927dec4d742f940e9421c705ae661c15b1a47e3e0baafbc0547ffffffffacffa835a861226cfc63f9b491fc1d5f782b0a9d82f9ee62c99a65eac8ff90a5000000006b483045022100b3aadc533e6de0fe5d9b14861526feddc988cf9e7d7c80eee9ccbd8b2883276502206bf589498a08b44e82b6e5f398d95820ad1e766844189dea202b8b390954176b0121037d14b11915f8f58ba90e6f219abbdb4bc48fdf412094dda6d5790f0edf084cf2ffffffff0aab3ccc10000000001976a9149a305f166390604123d628af06c8897324ae1b6688ac20965904000000001976a9140c0850e3da96e15fc722545f28bced12b46ff5bb88ac66409005000000001976a9149191be4fd37c3391e918a641626cfaf39594923a88ac107c5a00000000001976a914b46a76ad1f35e26f41251a79f68e65fb873d361688acc1b90f00000000001976a9143d0119812e60b1d71e42867dbc6ec09507d618d188ace0e76d01000000001976a914bdd386cd9deeace2557eabc489dcf2a699b82cb388ac7f420f00000000001976a9147c554abe9bf802a53b0cfd70c95c3860fdfefd3588ac4b765509000000001976a914f33c0d7be35d6d146d4618f665d4118b622d200d88acc4d55d0a000000001976a914396da404b5d95fa2261928de51157cc1052ccce188acec314100000000001976a914f09c529f3ebd2097ab81fc50145ba104058c938f88ac00000000",
            "0100000004bf3ff395d579649914060b71487e466e6fd09604eb62f57ffb63ce702b0c60ab000000008c493046022100896c3dcdaf7c01cf86d9a8105bc54b9065bc26a8a9ea478a491d244b466bf64c0221009377176af0277c45b9c81433d9983d34bb1cdbc37a7479d4a8bbdde4a5d192a0014104a5ed97469860bbe8f05b9964dbc83bb17e5d14383a54fc4395e1e698d01011f6827632c50871df697d46faab766a267cd3b12a17244db5af311133953b440e31ffffffff7010eb5de98223f6048c4e048b4c47b9078b5e6ea679f7d5a4e78d88def33002000000008b48304502206773a142ab11bd4bcac34f55f64ffd488dbdc8dc2a9197d75b1f3bf1da5c00ff022100c4c65d5ea66b00162a3744a9ff3ae60aeb19507e9c30a5ea9ea27fc26306007b014104a5ed97469860bbe8f05b9964dbc83bb17e5d14383a54fc4395e1e698d01011f6827632c50871df697d46faab766a267cd3b12a17244db5af311133953b440e31ffffffff705d8a483f532355a9a3cc4c2f8e9604ec5da99df9689319f0eb75378fd2b5a5000000008b48304502202e5299ab19138468421b4c48c5a78d7ed9e4783a266dd827b915dc27051755a002210093b4377bf0a0bf1ff0a40a996255463bc706ca49a7b23f45999125774c0b7d75014104a5ed97469860bbe8f05b9964dbc83bb17e5d14383a54fc4395e1e698d01011f6827632c50871df697d46faab766a267cd3b12a17244db5af311133953b440e31ffffffff8a0dfdaa0b30c5af535a1791ce3f0f158e197f311d6696b20b6ff56f89f27602000000008b4830450220472537228e29b49a06a9255b86323c2cf5a230fce789ef3b0a308aa58f9dafd4022100a14cfbafbbc682759d5491a2c4dfa1f8287649fafa88c0166fb7b9ef9e77d999014104a5ed97469860bbe8f05b9964dbc83bb17e5d14383a54fc4395e1e698d01011f6827632c50871df697d46faab766a267cd3b12a17244db5af311133953b440e31ffffffff0234b13c00000000001976a914794e3a3f5f542ebb3f2e640ea9417e3c7d41e40b88acfc4d2a00000000001976a914b526df90f2bb0c5830b469b8b8f96d25e127de5d88ac00000000",
            "01000000011aaebeeb686e02c3471f0cf9f12eb6d69e45c3dd89cc0fa135e26ca72ec8d2c1020000006b483045022100f119173e52f8950dc0f369c64546746b900d621633f7a1d9b386232b5c21d6430220197f389584d087f34d250ffc8e5908a6ec9651fd02f64f200d3a60abe0df4287012102a1c5150347aa359ad363b7ccaf8602e7538de37935ebb2bb1656e20bfc6cd111ffffffff02b421e428000000001976a91443d6d50fb700dc1d98494942f8c378b8cbbb0def88ac80969800000000001976a9148e10169967933a59a77260277bb6f4d2869ed53088ac00000000"
        ];*/

        $data = [
            1 => [
                'inputs' => [
                    [
                        "txid" => "5a373fd13679fc55f479f08bef25d5e808031f97331a48f950ced89d7e99c269",
                        "vout" => 31,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "29b509aa81cb4115645b536c14e6c0f7699f5e2228d95c79300f23004aae9aab",
                        "vout" => 123,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "e59686315e67427c465d6febe77ce1b93af66f1e25590353743fa95a4c1836c0",
                        "vout" => 1,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "00078ef8003e86b1ec57a69842e4a64e54d7dea9dddfb4124f257cb3bf077172",
                        "vout" => 17,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "fe4d7c39fdc034b8c5497a0ff07c17ea68bc02734f5247a5db616a12002086fb",
                        "vout" => 57,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "8acd7cc80178a00404dfa9061a0028d74239a4bddebf54eacd0fcf657cb535db",
                        "vout" => 9,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "2d3631bc56b34e845bfbf90bb30b473601a5dd03ffbadb92db98ab4de1f89f53",
                        "vout" => 31,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "d79ef324ffdeacbad1d8f02ec9dd3707277f1ec1365a2f0e8fa5d027bfab0dbc",
                        "vout" => 17,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ]
                ],
                'outputs' => [
                    "15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz" => BitcoinLib::toSatoshi(0.14750000),
                    "1L6hCPsCq7C5rNzq7wSyu4eaQCq8LeipmG" => BitcoinLib::toSatoshi(0.01373172)
                ]
            ]
        ];

        foreach ($data as $test) {
            $create = RawTransaction::create($test['inputs'], $test['outputs'], '00');
            $this->assertTrue(is_string($create));
        }
    }

    public function testSign()
    {
        // 1 loop takes ~0.22s
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 5;

        $inputs = array(
            array(
                'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
                'vout' => 0
            )
        );

        $outputs = array('1PGa6cMAzzrBpTtfvQTzX5PmUxsDiFzKyW' => BitcoinLib::toSatoshi(0.00015));

        $json_inputs = json_encode(
            array(
                array(
                    'txid' => '6737e1355be0566c583eecd48bf8a5e1fcdf2d9f51cc7be82d4393ac9555611c',
                    'vout' => 0,
                    // OP_DUP OP_HASH160 push14bytes PkHash OP_EQUALVERIFY OP_CHECKSIG
                    'scriptPubKey' => '76a914' . '7e3f939e8ded8c0d93695310d6d481ae5da39616' . '88ac'
                )
            )
        );

        $wallet = array();
        RawTransaction::private_keys_to_wallet($wallet, array('L2V4QgXVUyWVoMGejTj7PrRUUCEi9D9Y1AhUM8E6f5yJm7gemgN6'), '00');

        $raw_transaction = RawTransaction::create($inputs, $outputs);

        for ($i = 0; $i < $cnt; $i++) {
            $sign = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);
            $this->assertTrue(RawTransaction::validate_signed_transaction($sign['hex'], $json_inputs));
        }
    }

    public function testPayToScriptHashSignedTransaction()
    {
        $data = [
            0 => [
                'tx' => '01000000012228bfe4d2ddd0818d39a81c9e0d41472790eab9c7409749c3b4c65061e1956300000000fd1e0100483045022100900883802edd7db97447182f69c49bc04c7eacc977bed53bc15e9ba648799fde02205146c913b5cdd8ebe03b9babde8631184ac8b6d7735ba8c2ac8da96d891632f601483045022100d57786b2b5f5cdf9211d355f75b87e3049ed9b4bd8ecd95cbb12c76f06bd5fc9022059dbbe08fc8d203fe54acef7163e53d59e8ffd1b4e202d7a53612114d9521897014c89522103386ad259758077336d6301323a8c1ee61ebf819e272e52353a0af76f2d495aee21022d1f8dde5155dee03cf8586f57d06df9100455c6942d7e1fec8892a2499e5a474104fc97d46d5c117eb3c631af52dade567d6ecce84935f3f0ce5df869fe120760f2ee4ecdec9f9efccbe05fb1debca6da055198cfe8adab8795e271916edae9ebc253aeffffffff02d8270000000000001976a9148dde681a2482740d5022bfd61a00c5c9ecc6b7fe88ac584d0000000000001976a9146c2bab1726f4582fbdfb4cd31549b05679cd97c688ac00000000',
                'inputs' => [
                    [
                        "txid" => "6395e16150c6b4c3499740c7b9ea902747410d9e1ca8398d81d0ddd2e4bf2822",
                        "vout" => 0,
                        "scriptPubKey" => "a914a1e64962519b43be719392eab45eed5cf1198f4087",
                        "redeemScript" => "522103386ad259758077336d6301323a8c1ee61ebf819e272e52353a0af76f2d495aee21022d1f8dde5155dee03cf8586f57d06df9100455c6942d7e1fec8892a2499e5a474104fc97d46d5c117eb3c631af52dade567d6ecce84935f3f0ce5df869fe120760f2ee4ecdec9f9efccbe05fb1debca6da055198cfe8adab8795e271916edae9ebc253ae"
                    ]
                ],
                'outputs' => [
                    "3GT4b2TDowpMnvn5xSpPhxCGvFfgNg3gfo" => 0.00040000,
                    "1PtY9BWxhGJRM3G4J1bQUXABNUhUsecdpE" => 0.05040048
                ]
            ]];


        foreach ($data as $test) {

            $this->assertTrue(RawTransaction::validate_signed_transaction($test['tx'], json_encode($test['inputs']), '00'));
        }
    }

    public function testPayToPubKeyHashSignedTransaction()
    {
        $data = [
            1 => [
                // Tx : c58ebf5e342191dc9a1797b308ea5c707aa8ea762543184931246b48689a2761
                'tx' => '010000000869c2997e9dd8ce50f9481a33971f0308e8d525ef8bf079f455fc7936d13f375a1f0000008a47304402202de0d834112506ed10549f751ce142094243390f3e035444f105b4764056314302205dfddc421b377c8b089182ddb3928ce02e73c86e5dfb9e66ca6a98810d7a2ac5014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffab9aae4a00230f30795cd928225e9f69f7c0e6146c535b641541cb81aa09b5297b0000008a47304402205604456b1ed6dcae5e5f370568dd71c8bfb44823d583e3fa781ae8117ed831a30220154ee1c49bfca8bd1970953255498cb6bc347de1df267ed1b0f70385bca29184014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffc036184c5aa93f74530359251e6ff63ab9e17ce7eb6f5d467c42675e318696e5010000008a47304402206c756a38757443794196d16e887c95b9b769b11c608b425e7580e4fcd8456642022040613fbff5e5412c8aa0c3a91b651a6fe6fcf793596a5e29bbc7bc5d73fc683a014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff727107bfb37c254f12b4dfdda9ded7544ea6e44298a657ecb1863e00f88e0700110000008c493046022100c24b9c50820d19457fc5842a124500bf2371397144fc6166bdaa4a94275e9dda022100fc6c59286cfdc2fca4cca62d0a1b4e7dd8125a18aee59e630877f85101aa441a014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5fffffffffb862000126a61dba547524f7302bc68ea177cf00f7a49c5b834c0fd397c4dfe390000008b483045022100c837ec9105ddaa75250a38e9942a624754ecacb025feb024adfa8a77914e6eff0220538a1407d7ed8b7417421113ee29c3d9699f87780907b4121068740f1eb31d68014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffdb35b57c65cf0fcdea54bfdebda43942d728001a06a9df0404a07801c87ccd8a090000008b483045022100b00faaa1b6a7c1cbe4c47791e302bbcbf1d4fee54cb3d1195d82ef05df0f8f0702207382b68bb44e8fadb0ae7b44f22a18df93e8e8e000d28c88f4fdff78d92a8316014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff539ff8e14dab98db92dbbaff03dda50136470bb30bf9fb5b844eb356bc31362d1f0000008b48304502205e41d2112c190396173562d7957b16e0bba64a40adbe466cfe98778d30d91fa20221009f62ac2e345dbd16639b4269c86f156c050e5747013d2a452cdd8c25b8c9aeb9014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffffbc0dabbf27d0a58f0e2f5a36c11e7f270737ddc92ef0d8d1baacdeff24f39ed7110000008a473044021f3cfe420bd8a5baca342a3df2501f03165e8b8b76fea6fbc82dd05047c99fcd022100bfa829bce78d4f463abefbba943ea54dbbd931b5d7712e263eaf76d2779cf053014104b3d8c8c5896b0ed9537ccbdcfdf3f05cd4299988c72d078b841e0491bab198702c4befaa3da367c117c7c6217cd478c54b572e13de6ddd22c948f4b66c1562b5ffffffff023011e100000000001976a91431b07b8df3c19573388bb688b4fd89f6233f5d7988acf4f31400000000001976a914d17e062579b71bfe199a80991a253d929f8bd35b88ac00000000',
                'inputs' => [
                    [
                        "txid" => "5a373fd13679fc55f479f08bef25d5e808031f97331a48f950ced89d7e99c269",
                        "vout" => 31,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "29b509aa81cb4115645b536c14e6c0f7699f5e2228d95c79300f23004aae9aab",
                        "vout" => 123,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "e59686315e67427c465d6febe77ce1b93af66f1e25590353743fa95a4c1836c0",
                        "vout" => 1,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "00078ef8003e86b1ec57a69842e4a64e54d7dea9dddfb4124f257cb3bf077172",
                        "vout" => 17,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "fe4d7c39fdc034b8c5497a0ff07c17ea68bc02734f5247a5db616a12002086fb",
                        "vout" => 57,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "8acd7cc80178a00404dfa9061a0028d74239a4bddebf54eacd0fcf657cb535db",
                        "vout" => 9,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "2d3631bc56b34e845bfbf90bb30b473601a5dd03ffbadb92db98ab4de1f89f53",
                        "vout" => 31,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ],
                    [
                        "txid" => "d79ef324ffdeacbad1d8f02ec9dd3707277f1ec1365a2f0e8fa5d027bfab0dbc",
                        "vout" => 17,
                        "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
                    ]
                ],
                'outputs' => [
                    "1L6hCPsCq7C5rNzq7wSyu4eaQCq8LeipmG" => 0.01373172
                ]
            ]
        ];

        foreach ($data as $test) {
            $this->assertTrue(RawTransaction::validate_signed_transaction($test['tx'], json_encode($test['inputs']), '00'));
        }
    }

    public function testTxId()
    {
        // https://www.blocktrail.com/BTC/tx/6395e16150c6b4c3499740c7b9ea902747410d9e1ca8398d81d0ddd2e4bf2822
        $txid = "6395e16150c6b4c3499740c7b9ea902747410d9e1ca8398d81d0ddd2e4bf2822";
        $raw = "010000000178c07b9b8383f28f70d6a386ebec67ca38b2320a0f797449c42faab463776d07010000006b4830450221009d35a724c2924643e8d53e7b9703748aac09eb636a30b61909f47e1b86bd5b3d0220364cdf7fc0c37ed4f78c10b96baa49b7b9f3b5a65a12c19b0f50ab77f1ca4ae10121034e68233e53095310b4f3041f7865bc928827afee62cc5aa2143465b3bb64cb2dffffffff02409c00000000000017a914a1e64962519b43be719392eab45eed5cf1198f4087b0e74c00000000001976a914fb11f9fe83b646d982a3d4df8c5a5da44143ac1888ac00000000";

        $tx = RawTransaction::decode($raw);

        $this->assertEquals($txid, $tx['txid']);
        $this->assertEquals($txid, RawTransaction::txid_from_raw($raw));
    }

    public function testP2SHMultisig()
    {
        $n = 3;
        $m = 2;

        $k = [];
        $pk_list = [];

        for ($i = 0; $i < $n; $i++) {
            $k[$i] = BitcoinLib::get_new_key_set();
            $pk_list[] = $k[$i]['pubKey'];
        }

        $multisig = RawTransaction::create_multisig($m, RawTransaction::sort_multisig_keys($pk_list));

        $this->assertTrue(!!$multisig['address']);
        $this->assertTrue(BitcoinLib::validate_address($multisig['address']));
    }

    public function testValidInput()
    {
        $inputs = [
            [
                "txid" => "5a373fd13679fc55f479f08bef25d5e808031f97331a48f950ced89d7e99c269",
                "vout" => 31,
                "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
            ]
        ];

        RawTransaction::create($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => 10000]);
        RawTransaction::create($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => BitcoinLib::toSatoshi(10000.0)]);
        RawTransaction::create($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => BitcoinLib::toSatoshi(10000000000.0)]);
    }

    public function testInvalidInput()
    {
        $inputs = [
            [
                "txid" => "5a373fd13679fc55f479f08bef25d5e808031f97331a48f950ced89d7e99c269",
                "vout" => 31,
                "scriptPubKey" => "76a914d17e062579b71bfe199a80991a253d929f8bd35b88ac"
            ]
        ];

        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVx' => 10000], "invalid address");
        $this->createRawExpectException($inputs, ['45XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => 10000], "invalid address");

        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => 0.1], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => 1.1], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => 1.0], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "0.1"], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "1.1"], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "1.0"], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "0,1"], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "1,1"], "float output value");
        $this->createRawExpectException($inputs, ['15XjXdS1qTBy3i8vCCriWSAbm1qx5JgJVz' => "1,0"], "float output value");
    }

    private function createRawExpectException($inputs, $outputs, $message = "")
    {
        $e = null;
        try {
            RawTransaction::create($inputs, $outputs);
        } catch (\Exception $e) {
        }
        $this->assertTrue(!!$e, "should have thrown exception [{$message}]");
    }

    public function testP2SHMultisig2()
    {
        $n = 3;
        $m = 2;

        $privKeys = [
            "a56a29f79648d95c5666989c9b2b8d40bfe29c4f65b6fbc3e28ed15f8bc46691",
            "df3fa8db488c6ab6eb31f6b8979dcffd9a7c334196db88b1705bf8bfada41bb2",
            "2c44e5a2b83abded4e02aae1c3c02a95bf68a4ca56b5473c7f55b8940a5dcfa6",
        ];
        $pubKeys = array_map(function ($privKey) {
            return BitcoinLib::private_key_to_public_key($privKey, true);
        }, $privKeys);

        $pubKeys = RawTransaction::sort_multisig_keys($pubKeys);

        $multisig = RawTransaction::create_multisig($m, $pubKeys);

        $this->assertEquals("3BMH67dedFZTbbtMQ3e7nnKEzHfkwB6VpU", $multisig['address']);
    }

    public function testSigCanonical()
    {
        $f = file_get_contents(__DIR__ . '/data/sig_canonical.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $this->assertTrue(RawTransaction::is_canonical_signature($test));
        }
    }

    public function testSigNonCanonical()
    {
        $f = file_get_contents(__DIR__ . '/data/sig_noncanonical.json');
        $json = json_decode($f);
        foreach ($json->test as $c => $test) {
            $result = RawTransaction::is_canonical_signature($test[1]);
            if ($result == false) {
                $this->assertTrue(true);
                continue;
            }

            $this->fail('Failed testing for case: ' . $test[0]);

        }
    }

    public function testSignAndIsCanonical()
    {
        $cnt = (getenv('BITCOINLIB_EXTENSIVE_TESTING') ?: 1) * 10;

        $math = EccFactory::getAdapter();
        $G = EccFactory::getSecgCurves()->generator256k1();
        $private = $G->createPrivateKey();

        for ($i = 0; $i < $cnt; $i++) {
            $randomMsgHash = $math->hexDec((string)hash('sha256', 'random' . $i));
            $randomK = $math->hexDec((string)bin2hex(mcrypt_create_iv(32, \MCRYPT_DEV_URANDOM)));

            $signer = new \Mdanter\Ecc\Crypto\Signature\Signer($math);
            $sign = $signer->sign($private, $randomMsgHash, $randomK);
            $this->assertInstanceOf('Mdanter\Ecc\Crypto\Signature\Signature', $sign);

            $sig = RawTransaction::encode_signature($sign);
            $this->assertTrue(RawTransaction::is_canonical_signature($sig));
        }
    }

    public function testDecodeTx() {
        $hex = "010000000178c07b9b8383f28f70d6a386ebec67ca38b2320a0f797449c42faab463776d07010000006b4830450221009d35a724c2924643e8d53e7b9703748aac09eb636a30b61909f47e1b86bd5b3d0220364cdf7fc0c37ed4f78c10b96baa49b7b9f3b5a65a12c19b0f50ab77f1ca4ae10121034e68233e53095310b4f3041f7865bc928827afee62cc5aa2143465b3bb64cb2dffffffff02409c00000000000017a914a1e64962519b43be719392eab45eed5cf1198f4087b0e74c00000000001976a914fb11f9fe83b646d982a3d4df8c5a5da44143ac1888ac00000000";
        $tx = RawTransaction::decode($hex);
        $this->assertEquals("6395e16150c6b4c3499740c7b9ea902747410d9e1ca8398d81d0ddd2e4bf2822", $tx['txid']);

        $hex = "0100000001552eed137888e6a6c2c69ded505d9e573c3d78ab0f478ecbdaf74b99b40f350d010000006b483045022100d958e320b5bbc700e7862b7832fc86d18f50be7a272399c38b35a6aecd471d68022014a02f0387a0971c4e06cac086d662615a3e07a0323e1f138d96c54c7f6aaead012102af6034f808ee5989a7ea0304cc7d464edb22a86d362739aeb4e52e759436b7f5ffffffff0240480801000000001976a91415df9c5643a3ef61ee05a92a7703f47a4ffbbcdb88ac8b0361695e0000001976a91490967f997eda3a1c0bd4358b3cd19824e46538b688ac00000000";

        $tx = RawTransaction::decode($hex);

        $this->assertEquals("eb5fed4f05285868e9b56d8a765271e34df491fed3127b5739d608ae46ba07fd", $tx['txid']);
        $this->assertEquals("1", $tx['version']);
        $this->assertEquals(1, count($tx['vin']));
        $this->assertEquals(2, count($tx['vout']));
        $this->assertEquals("0d350fb4994bf7dacb8e470fab783d3c579e5d50ed9dc6c2a6e6887813ed2e55", $tx['vin'][0]['txid']);
        $this->assertEquals(1, $tx['vin'][0]['vout']);
        $this->assertEquals("17320000", $tx['vout'][0]['value']);
        $this->assertEquals("76a91415df9c5643a3ef61ee05a92a7703f47a4ffbbcdb88ac", $tx['vout'][0]['scriptPubKey']['hex']);
        $this->assertEquals("405494891403", $tx['vout'][1]['value']);
        $this->assertEquals("76a91490967f997eda3a1c0bd4358b3cd19824e46538b688ac", $tx['vout'][1]['scriptPubKey']['hex']);
    }

    /**
     * took a valid TX hex and removed 1 byte from a output script
     */
    public function testDecodeBadTx() {
        $e = null;
        try {
            $hex = "0100000001552eed137888e6a6c2c69ded505d9e573c3d78ab0f478ecbdaf74b99b40f350d010000006b483045022100d958e320b5bbc700e7862b7832fc86d18f50be7a272399c38b35a6aecd471d68022014a02f0387a0971c4e06cac086d662615a3e07a0323e1f138d96c54c7f6aaead012102af6034f808ee5989a7ea0304cc7d464edb22a86d362739aeb4e52e759436b7f5ffffffff0240480801000000001976a91415df9c5643a3ef61ee05a92a7703f47a4ffbbcdb88ac8b0361695e0000001976a91490967f997eda3a1c0bd4358b3cd19824e46538b6ac00000000";
            $tx = RawTransaction::decode($hex);
        } catch (\Exception $e) {}
        $this->assertTrue(!!$e);
    }

    public function testTxDecodeEncodeOpReturn() {
        $txId = "9b831ef60919c42be1ede10fbe4c773a622144669f7dbaa7bb4452574a9263a2";
        $raw = "0100000001aadf4be6d94e6028986a70432e97051174dc7ee0b7d0fd4241871a6f5a4c8978000000008a4730440220730f9b2fdd4e94cf0ead16767151e08fc178ae05cea0528583a993988b4360f3022010cbecd765dad96ba86f47df7f770914e6e07d12756b1c7ccc2d3262b68ecaf501410441b5e5365075fc3a3df8313abefdceb0a7f67f5253f96f7ea2cb5d952ac6537adad5e25ca9eef68a486c3c0fe4c87e9fe566b1849c9da03b02c686dfecee99c9ffffffff03f8380900000000001976a91463241d31675c1d761c734649cb3681e92bdf86ee88ac00000000000000000c6a0a6f6d000000468000002a22020000000000001976a91463241d2ef3fcfe30e496135d66c16e66f87b6a4788ac00000000";

        $tx = RawTransaction::decode($raw);

        $this->assertEquals("6a0a6f6d000000468000002a", $tx['vout'][1]['scriptPubKey']['hex']);
        $this->assertEquals("OP_RETURN 6f6d000000468000002a", $tx['vout'][1]['scriptPubKey']['asm']);

        $this->assertEquals($raw, RawTransaction::encode($tx));
        $this->assertEquals($txId, RawTransaction::txid_from_raw(RawTransaction::encode($tx)));
    }

    public function testTxDecodeEncodeOpReturn2() {
        $txId = "66385bf1c129d5659a93e1a00aeb576a5410306a29adc22f38fbeaaf9d8c9dbd";
        $raw = "0100000001b6d1f899f12a191cadd933ab8a841ac95b31e0cb406efae6602daed7306d8356010000006b483045022100eef4793a2077b8477682041148f185c993a96805f4c97060bccf2a8cd3e4e32602201e37f214a598182349b7c257e092a2b53f62e2096bd855ba5c0015e1b112628201210230cc7167299b535269e12049954dd768d681c771cfde12e17cd6dc697da0d632ffffffff0310270000000000001976a9145fcfddacf59e1bb22187b8515f53c1432f348d5488ac0000000000000000fd06016a0430343443463044313341443542394137454143393630314130453144423638354339433537344537314338304630303238373733363346393646373633304632323136393736413645334639444545334435423243443944353145343743364234344631363435353537454344314238384443344445374339333741393334364246434432343539463443323339394335383442443838363639323133383638434343374137314145323135464532444243434632463143324238344132364235323130414135373133383641343743354332313431444434324443423039464145384437343239343038444236463143413944303737343045334333433039453244379fab1500000000001976a9144862914bdd96dec5c9534a97ff23d6b7bc39ca2f88ac00000000";

        $tx = RawTransaction::decode($raw);

        $this->assertEquals("6a043034344346304431334144354239413745414339363031413045314442363835433943353734453731433830463030323837373336334639364637363330463232313639373641364533463944454533443542324344394435314534374336423434463136343535353745434431423838444334444537433933374139333436424643443234353946344332333939433538344244383836363932313338363843434337413731414532313546453244424343463246314332423834413236423532313041413537313338364134374335433231343144443432444342303946414538443734323934303844423646314341394430373734304533433343303945324437", $tx['vout'][1]['scriptPubKey']['hex']);
        $this->assertEquals(null, $tx['vout'][1]['scriptPubKey']['asm']);

        $this->assertEquals($raw, RawTransaction::encode($tx));
        $this->assertEquals($txId, RawTransaction::txid_from_raw(RawTransaction::encode($tx)));
    }

    public function testTxDecodeEncode() {
        $txId = "fcbe95cd172371d9c35569fbb441774d0fa1adcc2426eff500a4c00a4eb2b6c4";
        $raw = "01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff2703681e05062f503253482f048dcc9854087400023054c704000d4254434368696e6120506f6f6c0000000001ff702896000000001976a9142c30a6aaac6d96687291475d7d52f4b469f665a688ac00000000";

        $tx = RawTransaction::decode($raw);

        $this->assertEquals($raw, RawTransaction::encode($tx));
        $this->assertEquals($txId, RawTransaction::txid_from_raw(RawTransaction::encode($tx)));
    }

    public function testTxDecodeNonStandard() {
        $txId = "e0c354620324422f59c9d62464a62240879cfb63856ebc60601da051522865ed";
        $raw = "0000000001e58a2b3e662984ec5a26c9d3bfbeab17c8bcb144d7b841ca2779b43f57424e23010000006b48304502210097166be10ed2660070929b68ea975e60ba4701bd5dbc2eb53290ce9ab6e5f9c502200222033604f63a4a7f97a29cd2039e97b4f9407544ced5ea6ec6371a223f2e98012103619344fbff8e9e203909cf9912c699f9d7ede4f3aa18b7545bfae16dd4c6df2effffffff020000000000000000216a1f4343484e01017b226d223a22746c222c2264223a7b2263223a223f63227d7d4847f50200000000226d784b4e7a3667583675627253457872326b66387556614539396d7a43536d78756900000000";

        $tx = RawTransaction::decode($raw);

        $this->assertEquals($raw, RawTransaction::encode($tx));
        $this->assertEquals($txId, RawTransaction::txid_from_raw(RawTransaction::encode($tx)));
    }

    public function testDecodeScriptPubKey() {
        $vectors = [
            "51210240852c3c09ee317bd1373a5731a6af9ef8eea52c8c6870072c1c32aa6a36ded52103eb6972fe40be87fdeff93dc56764b3058c0d0fa2d282f680e818f26f558b1a3541049958b1816ebd74ee1a94ab80dd6557c439388ffccbc532bc852568fa0efcf096f0169639ac8103eff80ecea4acce7f7aae528c4ccee393c05000d17edc9e5d3c53ae" =>
                "OP_1 0240852c3c09ee317bd1373a5731a6af9ef8eea52c8c6870072c1c32aa6a36ded5 03eb6972fe40be87fdeff93dc56764b3058c0d0fa2d282f680e818f26f558b1a35 049958b1816ebd74ee1a94ab80dd6557c439388ffccbc532bc852568fa0efcf096f0169639ac8103eff80ecea4acce7f7aae528c4ccee393c05000d17edc9e5d3c OP_3 OP_CHECKMULTISIG",
        ];

        foreach ($vectors as $hex => $asm) {
            $this->assertEquals($asm, RawTransaction::_decode_scriptPubKey($hex));
        }
    }

    public function testDecodeScriptPubKeyMatchBitcoinCore() {
        $vectors = [
            "51210240852c3c09ee317bd1373a5731a6af9ef8eea52c8c6870072c1c32aa6a36ded52103eb6972fe40be87fdeff93dc56764b3058c0d0fa2d282f680e818f26f558b1a3541049958b1816ebd74ee1a94ab80dd6557c439388ffccbc532bc852568fa0efcf096f0169639ac8103eff80ecea4acce7f7aae528c4ccee393c05000d17edc9e5d3c53ae" =>
                "1 0240852c3c09ee317bd1373a5731a6af9ef8eea52c8c6870072c1c32aa6a36ded5 03eb6972fe40be87fdeff93dc56764b3058c0d0fa2d282f680e818f26f558b1a35 049958b1816ebd74ee1a94ab80dd6557c439388ffccbc532bc852568fa0efcf096f0169639ac8103eff80ecea4acce7f7aae528c4ccee393c05000d17edc9e5d3c 3 OP_CHECKMULTISIG",
        ];

        foreach ($vectors as $hex => $asm) {
            $this->assertEquals($asm, RawTransaction::_decode_scriptPubKey($hex, true));
        }
    }
}
