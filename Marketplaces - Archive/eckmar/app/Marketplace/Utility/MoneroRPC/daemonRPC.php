<?php
/**
 *
 * monerophp/daemonRPC
 *
 * A class for making calls to a Monero daemon's RPC API using PHP
 * https://github.com/monero-integrations/monerophp
 *
 * Using work from
 *   CryptoChangements [Monero_RPC] <bW9uZXJv@gmail.com> (https://github.com/cryptochangements34)
 *   Serhack [Monero Integrations] <nico@serhack.me> (https://serhack.me)
 *   TheKoziTwo [xmr-integration] <thekozitwo@gmail.com>
 *   Andrew LeCody [EasyBitcoin-PHP]
 *   Kacper Rowinski [jsonRPCClient] <krowinski@implix.com>
 *
 * @author     Monero Integrations Team <support@monerointegrations.com> (https://github.com/monero-integrations)
 * @copyright  2018
 * @license    MIT
 *
 * ============================================================================
 *
 * // See example.php for more examples
 *
 * // Initialize class
 * $daemonRPC = new daemonRPC();
 *
 * // Examples:
 * $height = $daemonRPC->getblockcount();
 * $block = $daemonRPC->getblock_by_height(1);
 *
 */


namespace App\Marketplace\Utility\MoneroRPC;

use App\Marketplace\Utility\MoneroRPC\jsonRPCClient;


class daemonRPC
{
  private $client;

  private $protocol;
  private $host;
  private $port;
  private $url;
  private $user;
  private $password;

  /**
   *
   * Start a connection with the the Monero daemon (monerod)
   *
   * @param  string  $host      Monero daemon IP hostname            (optional)
   * @param  int     $port      Monero daemon port                   (optional)
   * @param  string  $protocol  Monero daemon protocol (eg. 'http')  (optional)
   * @param  string  $user      Monero daemon RPC username           (optional)
   * @param  string  $password  Monero daemon RPC passphrase         (optional)
   *
   */
  function __construct($host = '127.0.0.1', $port = 18081, $protocol = 'http', $user = null, $password = null)
  {
    if (is_array($host)) { // Parameters passed in as object/dictionary
      $params = $host;

      if (array_key_exists('host', $params)) {
        $host = $params['host'];
      } else {
        $host = '127.0.0.1';
      }
      if (array_key_exists('port', $params)) {
        $port = $params['port'];
      }
      if (array_key_exists('protocol', $params)) {
        $protocol = $params['protocol'];
      }
      if (array_key_exists('user', $params)) {
        $user = $params['user'];
      }
      if (array_key_exists('password', $params)) {
        $password = $params['password'];
      }
    }
    
    $this->host = $host;
    $this->port = $port;
    $this->protocol = $protocol;
    $this->user = $user;
    $this->password = $password;

    $this->url = $protocol.'://'.$host.':'.$port.'/json_rpc';
    $this->client = new jsonRPCClient($this->url, $this->user, $this->password);
  }

  /**
   *
   * Execute command via jsonRPCClient
   *
   * @param  string  $method  RPC method to call
   * @param  string  $params  Parameters to pass  (optional)
   *
   * @return string  Call result
   *
   */
  protected function _run($method, $params = null)
  {
    return $this->client->_run($method, $params);
  }

  /**
   *
   * Look up how many blocks are in the longest chain known to the node
   *
   * @param  none
   *
   * @return object  Example: {
   *   "count": 993163,
   *   "status": "OK"
   * }
   *
   */
  public function getblockcount()
  {
    return $this->_run('getblockcount');
  }

  /**
   *
   * Look up a block's hash by its height
   *
   * @param  number  $height   Height of block to look up
   *
   * @return string  Example: 'e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6'
   *
   */
  public function on_getblockhash($height)
  {
    $params = array($height);

    return $this->_run('on_getblockhash', $params);
  }

  /**
   *
   * Construct a block template that can be mined upon
   *
   * @param  string  $wallet_address  Address of wallet to receive coinbase transactions if block is successfully mined
   * @param  int     $reserve_size    Reserve size
   *
   * @return object  Example: {
   *   "blocktemplate_blob": "01029af88cb70568b84a11dc9406ace9e635918ca03b008f7728b9726b327c1b482a98d81ed83000000000018bd03c01ffcfcf3c0493d7cec7020278dfc296544f139394e5e045fcda1ba2cca5b69b39c9ddc90b7e0de859fdebdc80e8eda1ba01029c5d518ce3cc4de26364059eadc8220a3f52edabdaf025a9bff4eec8b6b50e3d8080dd9da417021e642d07a8c33fbe497054cfea9c760ab4068d31532ff0fbb543a7856a9b78ee80c0f9decfae01023ef3a7182cb0c260732e7828606052a0645d3686d7a03ce3da091dbb2b75e5955f01ad2af83bce0d823bf3dbbed01ab219250eb36098c62cbb6aa2976936848bae53023c00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001f12d7c87346d6b84e17680082d9b4a1d84e36dd01bd2c7f3b3893478a8d88fb3",
   *   "difficulty": 982540729,
   *   "height": 993231,
   *   "prev_hash": "68b84a11dc9406ace9e635918ca03b008f7728b9726b327c1b482a98d81ed830",
   *   "reserved_offset": 246,
   *   "status": "OK"
   * }
   *
   */
  public function getblocktemplate($wallet_address, $reserve_size)
  {
    $params = array('wallet_address' => $wallet_address, 'reserve_size' => $reserve_size);

    return $this->client->_run('getblocktemplate', $params);
  }

  /**
   *
   * Submit a mined block to the network
   *
   * @param  string  $block  Block blob
   *
   * @return // TODO: example
   *
   */
  public function submitblock($block)
  {
    return $this->_run('submitblock', $block);
  }

  /**
   *
   * Look up the block header of the latest block in the longest chain known to the node
   *
   * @param  none
   *
   * @return object  Example: {
   *   "block_header": {
   *     "depth": 0,
   *     "difficulty": 746963928,
   *     "hash": "ac0f1e226268d45c99a16202fdcb730d8f7b36ea5e5b4a565b1ba1a8fc252eb0",
   *     "height": 990793,
   *     "major_version": 1,
   *     "minor_version": 1,
   *     "nonce": 1550,
   *     "orphan_status": false,
   *     "prev_hash": "386575e3b0b004ed8d458dbd31bff0fe37b280339937f971e06df33f8589b75c",
   *     "reward": 6856609225169,
   *     "timestamp": 1457589942
   *   },
   *   "status": "OK"
   * }
   *
   */
  public function getlastblockheader()
  {
    return $this->_run('getlastblockheader');
  }

  /**
   *
   * Look up a block header from a block hash
   *
   * @param  string  $hash  The block's SHA256 hash
   *
   * @return object  Example: {
   *   "block_header": {
   *     "depth": 78376,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "status": "OK"
   * }
   *
   */
  public function getblockheaderbyhash($hash)
  {
    $params = array('hash' => $hash);

    return $this->_run('getblockheaderbyhash', $params);
  }

  /**
   *
   * Look up a block header by height
   *
   * @param  int     $height  Height of block
   *
   * @return object  Example: {
   *   "block_header": {
   *     "depth": 78376,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "status": "OK"
   * }
   *
   */
  public function getblockheaderbyheight($height)
  {
    return $this->_run('getblockheaderbyheight', $height);
  }

  /**
   *
   * Look up block information by SHA256 hash
   *
   * @param  string  $hash  SHA256 hash of block
   *
   * @return object  Example: {
   *   "blob": "...",
   *   "block_header": {
   *     "depth": 12,
   *     "difficulty": 964985344,
   *     "hash": "510ee3c4e14330a7b96e883c323a60ebd1b5556ac1262d0bc03c24a3b785516f",
   *     "height": 993056,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 2036,
   *     "orphan_status": false,
   *     "prev_hash": "0ea4af6547c05c965afc8df6d31509ff3105dc7ae6b10172521d77e09711fd6d",
   *     "reward": 6932043647005,
   *     "timestamp": 1457720227
   *   },
   *   "json": "...",
   *   "status": "OK"
   * }
   *
   */
  public function getblock_by_hash($hash)
  {
    $params = array('hash' => $hash);

    return $this->_run('getblock', $params);
  }

  /**
   *
   * Look up block information by height
   *
   * @param  int     $height  Height of block
   *
   * @return object  Example: {
   *   "blob": "...",
   *   "block_header": {
   *     "depth": 80694,
   *     "difficulty": 815625611,
   *     "hash": "e22cf75f39ae720e8b71b3d120a5ac03f0db50bba6379e2850975b4859190bc6",
   *     "height": 912345,
   *     "major_version": 1,
   *     "minor_version": 2,
   *     "nonce": 1646,
   *     "orphan_status": false,
   *     "prev_hash": "b61c58b2e0be53fad5ef9d9731a55e8a81d972b8d90ed07c04fd37ca6403ff78",
   *     "reward": 7388968946286,
   *     "timestamp": 1452793716
   *   },
   *   "json": "...",
   *   "status": "OK"
   * }
   *
   */
  public function getblock_by_height($height)
  {
    $params = array('height' => $height);

    return $this->_run('getblock', $params);
  }

  /**
   *
   * Look up incoming and outgoing connections to your node
   *
   * @param  none
   *
   * @return object  Example: {
   *   "connections": [{
   *     "avg_download": 0,
   *     "avg_upload": 0,
   *     "current_download": 0,
   *     "current_upload": 0,
   *     "incoming": false,
   *     "ip": "76.173.170.133",
   *     "live_time": 1865,
   *     "local_ip": false,
   *     "localhost": false,
   *     "peer_id": "3bfe29d6b1aa7c4c",
   *     "port": "18080",
   *     "recv_count": 116396,
   *     "recv_idle_time": 23,
   *     "send_count": 176893,
   *     "send_idle_time": 1457726610,
   *     "state": "state_normal"
   *   },{
   *   ..
   *   }],
   *   "status": "OK"
   * }
   *
   */
  public function get_connections()
  {
    return $this->_run('get_connections');
  }

  /**
   *
   * Look up general information about the state of your node and the network
   *
   * @param  none
   *
   * @return object  Example: {
   *   "alt_blocks_count": 5,
   *   "difficulty": 972165250,
   *   "grey_peerlist_size": 2280,
   *   "height": 993145,
   *   "incoming_connections_count": 0,
   *   "outgoing_connections_count": 8,
   *   "status": "OK",
   *   "target": 60,
   *   "target_height": 993137,
   *   "testnet": false,
   *   "top_block_hash": "",
   *   "tx_count": 564287,
   *   "tx_pool_size": 45,
   *   "white_peerlist_size": 529
   * }
   *
   */
  public function get_info()
  {
    return $this->_run('get_info');
  }

  /**
   *
   * Look up information regarding hard fork voting and readiness
   *
   * @param  none
   *
   * @return object  Example: {
   *   "alt_blocks_count": 0,
   *   "block_size_limit": 600000,
   *   "block_size_median": 85,
   *   "bootstrap_daemon_address": ?,
   *   "cumulative_difficulty": 40859323048,
   *   "difficulty": 57406,
   *   "free_space": 888592449536,
   *   "grey_peerlist_size": 526,
   *   "height": 1066107,
   *   "height_without_bootstrap": 1066107,
   *   "incoming_connections_count": 1,
   *   "offline":  ?,
   *   "outgoing_connections_count": 1,
   *   "rpc_connections_count": 1,
   *   "start_time": 1519963719,
   *   "status": OK,
   *   "target": 120,
   *   "target_height": 1066073,
   *   "testnet": 1,
   *   "top_block_hash": e438aae56de8e5e5c8e0d230167fcb58bc8dde09e369ff7689a4af146040a20e,
   *   "tx_count": 52632,
   *   "tx_pool_size": 0,
   *   "untrusted": ?,
   *   "was_bootstrap_ever_used: ?,
   *   "white_peerlist_size": 5
   * }
   *
   */
  public function hardfork_info()
  {
    return $this->_run('hard_fork_info');
  }

  /**
   *
   * Ban another node by IP
   *
   * @param  array  $bans  Array of IP addresses to ban
   *
   * @return object  Example: {
   *   "status": "OK"
   * }
   *
   */
  public function set_bans($bans)
  {
    if (is_string($bans)) {
      $bans = array($bans);
    }
    $params = array('bans' => $bans);

    return $this->_run('set_bans', $params);
  }

  /**
   *
   * Alias of set_bans
   * }
   *
   */
  public function setbans($bans)
  {
    return $this->set_bans($params);
  }

  /**
   *
   * Get list of banned IPs
   *
   * @param  none
   *
   * @return object  Example: {
   *   "bans": [{
   *     "ip": 838969536,
   *     "seconds": 1457748792
   *   }],
   *   "status": "OK"
   * }
   *
   */
  public function get_bans()
  {
    return $this->_run('get_bans');
  }

  /**
   *
   * Alias of get_bans
   *
   */
  public function getbans()
  {
    return $this->get_bans();
  }

}
