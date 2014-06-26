<?php
/**
 * PHP cURL JSON RPC wrapper for different crypt-currency daemons
 *
 * @author Oleg Ilyushyn
 * @version 0.1
 * @date 25.06.2014
 */

namespace softcommerce\xcoin;


class BitcoinRpc
{
    protected $host = null;
    protected $port = null;
    protected $username = null;
    protected $password = null;

    public function __construct($host, $port, $username, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Make raw call to coin daemon
     */
    protected function rawRpcCall($data)
    {
        $url = 'http://' . $this->host . ':' . $this->port;
        $process = curl_init($url);
        $headers = [
            'Content-Type: text/json',
            'Content-Length: '.strlen($data),
        ];
        curl_setopt($process, CURLOPT_HTTPHEADER,$headers);
        curl_setopt($process, CURLOPT_HEADER, false);
        curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($process);
        curl_close($process);
        return $result;
    }

    /**
     * Make JSON RPC call to coin daemon
     *
     * @param string $method
     * @param array  $params
     *
     * @throws ApiCallException
     * @return \stdClass|null|mixed
     */
    protected function makeRpcCall($method, $params = [])
    {
        $result = $this->rawRpcCall(
            json_encode(
                [
                    'jsonrpc' => '2.0',
                    'method' => strtolower($method),
                    'params' => $params,
                    'id' => 1,
                ]
            )
        );
        $data = json_decode($result);
        if ($data !== null) {
            if ($data->error === null) {
                return $data->result;
            }
            throw new ApiCallException($data->error->message, $data->error->code);
        }
        return $data;
    }

    /**
     * Add a nRequired-to-sign multi-signature address to the wallet.
     * Each key is a bitcoin address or hex-encoded public key.
     * If [account] is specified, assign address to [account].
     *
     * @param int   $nRequired
     * @param array $keys
     * @param null  $account
     *
     * @return \stdClass|null
     */
    public function addMultiSignatureAddress($nRequired, $keys, $account = null)
    {
        $params = [$nRequired, $keys];
        if (!is_null($account)) {
            $params[] = $account;
        }
        return $this->makeRpcCall('addMultiSigAddress', $params);
    }

    /**
     *  Attempts add or remove <node> from the addNode list or try a connection to <node> once.
     *
     * @version 0.8
     *
     * @param $node
     * @param string $operation - (add/remove/onetry)
     *
     * @return \stdClass|null
     */
    public function addNode($node, $operation)
    {
        return $this->makeRpcCall('addNode', [$node, $operation]);
    }

    /**
     * Safely copies wallet.dat to destination, which can be a directory or a path with filename.
     *
     * @param string $destination
     *
     * @return \stdClass|null
     */
    public function backupWallet($destination)
    {
        return $this->makeRpcCall('backupWallet', [$destination]);
    }

    /**
     * Creates a multi-signature address and returns a json object
     *
     * @param int   $nRequired
     * @param array $keys
     *
     * @return \stdClass|null
     */
    public function createMultiSignatureAddress($nRequired, $keys)
    {
        return $this->makeRpcCall('createMultiSig', [$nRequired, $keys]);
    }

    /**
     * Creates a raw transaction spending given inputs.
     * @link https://en.bitcoin.it/wiki/Raw_Transactions
     * @version 0.7
     *
     * @param array $data [{"txid":txid,"vout":n},...]
     * @param array $payments {address:amount,...}
     *
     * @return \stdClass|null
     */
    public function createRawTransaction($data, $payments)
    {
        return $this->makeRpcCall('createRawTransaction', [$data, $payments]);
    }

    /**
     * Produces a human-readable JSON object for a raw transaction.
     * @link https://en.bitcoin.it/wiki/Raw_Transactions
     * @version 0.7
     *
     * @param string $hexString
     *
     * @return \stdClass|null
     */
    public function decodeRawTransaction($hexString)
    {
        return $this->makeRpcCall('decodeRawTransaction', [$hexString]);
    }

    /**
     * Reveals the private key corresponding to <address>
     * Requires unlocked wallet.
     *
     * @param string $address
     *
     * @return \stdClass|null
     */
    public function dumpPrivateKey($address)
    {
        return $this->makeRpcCall('dumpPrivKey', [$address]);
    }

    /**
     * Encrypts the wallet with <passPhrase>.
     *
     * @param string $passPhrase
     *
     * @return \stdClass|null
     */
    public function encryptWallet($passPhrase)
    {
        return $this->makeRpcCall('encryptWallet', [$passPhrase]);
    }

    /**
     * Returns the account associated with the given address.
     *
     * @param string $address
     *
     * @return \stdClass|null
     */
    public function getAccount($address)
    {
        return $this->makeRpcCall('getAccount', [$address]);
    }

    /**
     * Returns the current bitcoin address for receiving payments to this account.
     * If <account> does not exist, it will be created along with an associated new address that will be returned.
     *
     * @param string $account
     *
     * @return \stdClass|null
     */
    public function getAccountAddress($account)
    {
        return $this->makeRpcCall('getAccountAddress', [$account]);
    }

    /**
     * Returns information about the given added node, or all added nodes.
     * (note that onetry addNodes are not listed here)
     * If dns is false, only a list of added nodes will be provided,
     * otherwise connected information will also be available.
     * @version 0.8
     *
     * @param bool $dns
     * @param string|null $node
     *
     * @return null|\stdClass
     */
    public function getAddedNodeInfo($dns, $node = null)
    {
        $params = [$dns];
        if (!is_null($node)) {
            $params[] = $node;
        }
        return $this->makeRpcCall('getAddedNodeInfo', $params);
    }

    /**
     * Returns the list of addresses for the given account.
     *
     * @param string $account
     *
     * @return \stdClass|null
     */
    public function getAddressesByAccount($account)
    {
        return $this->makeRpcCall('getAddressesByAccount', [$account]);
    }

    /**
     * If [account] is not specified, returns the server's total available balance.
     * If [account] is specified, returns the balance in the account.
     *
     * @param string $account
     * @param int $minConf
     *
     * @return \stdClass|null
     */
    public function getBalance($account = null, $minConf = 1)
    {
        $params = [];
        if (!is_null($account)) {
            $params[] = $account;
        }
        if (!is_null($minConf)) {
            $params[] = $minConf;
        }
        return $this->makeRpcCall('getBalance', $params);
    }

    /**
     * Returns the hash of the best (tip) block in the longest block chain.
     * @version 0.9
     *
     * @return \stdClass|null
     */
    public function getBestBlockHash()
    {
        return $this->makeRpcCall('getBestBlockHash');
    }

    /**
     * Returns information about the block with the given hash.
     * [
     *  'hash' => string,
     *  'confirmations' => int,
     *  'size' => int,
     *  'height' => int,
     *  'version' => int,
     *  'merkleroot' => string,
     *  'tx' => [
     *      string,
     *      string,
     *      . . .
     *  ],
     *  'time' => int,
     *  'nonce' => int,
     *  'bits' => string,
     *  'difficulty' => float,
     *  'previousblockhash' => string,
     *  'nextblockhash' => string,
     * ]
     *
     * @param string $hash
     *
     * @return \stdClass|null
     */
    public function getBlock($hash)
    {
        return $this->makeRpcCall('getBlock', [$hash]);
    }

    /**
     * Returns the number of blocks in the longest block chain.
     *
     * @return \stdClass|null
     */
    public function getBlockCount()
    {
        return $this->makeRpcCall('getBlockCount');
    }

    /**
     * Returns hash of block in best-block-chain at <index>; index 0 is the genesis block
     * @link https://en.bitcoin.it/wiki/Genesis_block
     *
     * @param int $index
     *
     * @return \stdClass|null
     */
    public function getBlockHash($index)
    {
        return $this->makeRpcCall('getBlockHash', [$index]);
    }

    /**
     * Deprecated. Removed in version 0.7.
     * Use getBlockCount.
     */
    public function getBlockNumber()
    {
        return $this->getBlockCount();
    }

    /**
     * Returns data needed to construct a block to work on. See BIP_0022 for more info on params.
     * @link https://en.bitcoin.it/wiki/BIP_0022
     *
     * @param array $params
     *
     * @return \stdClass|null
     */
    public function getBlockTemplate($params)
    {
        return $this->makeRpcCall('getBlockTemplate', [$params]);
    }

    /**
     * Returns the number of connections to other nodes.
     *
     * @returns int|null
     */
    public function getConnectionCount()
    {
        return $this->makeRpcCall('getConnectionCount');
    }

    /**
     * Returns the proof-of-work difficulty as a multiple of the minimum difficulty.
     *
     * @return float|null
     */
    public function getDifficulty()
    {
        return $this->makeRpcCall('getDifficulty');
    }

    /**
     * Returns true or false whether bitcoind is currently generating hashes
     *
     * @return bool
     */
    public function getGenerate()
    {
        return $this->makeRpcCall('getGenerate');
    }

    /**
     * Returns a recent hashes per second performance measurement while generating.
     *
     * @return int
     */
    public function getHashesPerSec()
    {
        return $this->makeRpcCall('getHashesPerSec');
    }

    /**
     * Returns an object containing various state info.
     *
     * @return \stdClass|null
     */
    public function getInfo()
    {
        return $this->makeRpcCall('getInfo');
    }

    /**
     * Replaced in v0.7.0 with getBlockTemplate, submitBlock, getRawMemPool
     */
    public function getMemoryPool()
    {
        return null;
    }

    /**
     * Returns an object containing mining-related information:
     * [
     *  'blocks' => int,
     *  'currentblocksize' => int,
     *  'currentblocktx' => int,
     *  'difficulty' => float,
     *  'errors' => string,
     *  'generate' => bool,
     *  'genproclimit' => int,
     *  'hashespersec' => int,
     *  'networkhashps' => int,
     *  'pooledtx' => int,
     *  'testnet' => bool,
     * ]
     *
     * @return \stdClass|null
     */
    public function getMiningInfo()
    {
        return $this->makeRpcCall('getMiningInfo');
    }

    /**
     * Returns a new bitcoin address for receiving payments.
     * If [account] is specified payments received with the address will be credited to [account].
     *
     * @param string $account
     *
     * @return string|null
     */
    public function getNewAddress($account)
    {
        return $this->makeRpcCall('getNewAddress', [$account]);
    }

    /**
     * Returns data about each connected node.
     * @version 0.7
     *
     * @return \stdClass|null
     */
    public function getPeerInfo()
    {
        return $this->makeRpcCall('getPeerInfo');
    }

    /**
     * Returns a new Bitcoin address, for receiving change.
     * This is for use with raw transactions, NOT normal use.
     * @version 0.9
     *
     * @param string $account
     *
     * @return string|null
     */
    public function getRawChangeAddress($account)
    {
        return $this->makeRpcCall('getRawChangeAddress', [$account]);
    }

    /**
     * Returns all transaction ids in memory pool
     * @version 0.7
     *
     * @return array|null
     */
    public function getRawMemPool()
    {
        return $this->makeRpcCall('getRawMemPool');
    }

    /**
     * Returns raw transaction representation for given transaction id.
     * @link https://en.bitcoin.it/wiki/Raw_Transactions
     *
     * @param string $txId
     * @param int $verbose
     *
     * @return \stdClass|null
     */
    public function getRawTransaction($txId, $verbose = 0)
    {
        return $this->makeRpcCall('getRawTransaction', [$txId, $verbose]);
    }

    /**
     * Returns an object about the given transaction containing:
     * [
     *  'amount' => decimal,
     *  'confirmations' => int,
     *  'txid' => string,
     *  'time' => string,
     *  'details' => [
     *      'account' => string,
     *      'address' => string,
     *      'category' => string,
     *      'amount' => decimal,
     *      'fee' => decimal,
     *  ],
     * ]
     *
     * @param string $txId
     *
     * @return \stdClass|null
     */
    public function getTransaction($txId)
    {
        return $this->makeRpcCall('getTransaction', [$txId]);
    }

    /**
     * Returns details about an unspent transaction output (UTXO)
     *
     * @param string $txId
     * @param int $n
     * @param bool $includeMemoryPool
     *
     * @return \stdClass|null
     */
    public function getTxOut($txId, $n, $includeMemoryPool = true)
    {
        return $this->makeRpcCall('getTxOut', [$txId, $n, $includeMemoryPool]);
    }

    /**
     * Returns statistics about the unspent transaction output (UTXO) set
     *
     * @return \stdClass|null
     */
    public function getTxOutSetInfo()
    {
        return $this->makeRpcCall('getTxOutSetInfo');
    }

    /**
     * If [data] is not specified, returns formatted hash data to work on:
     * [
     *  'midstate' => string,
     *  'data' => string,
     *  'hash1' => string,
     *  'target' => string,
     * ]
     *
     * If [data] is specified, tries to solve the block and returns true if it was successful.
     *
     * @param array $data
     *
     * @return \stdClass|bool|null
     */
    public function getWork($data)
    {
        return $this->makeRpcCall('getWork', [$data]);
    }

    /**
     * List commands, or get help for a command.
     *
     * @param string $command
     *
     * @return string|null
     */
    public function help($command = null)
    {
        $params = [];
        if (!is_null($command)) {
            $params[] = $command;
        }
        return $this->makeRpcCall('help', $params);
    }

    /**
     * Adds a private key (as returned by dumpprivkey) to your wallet.
     * This may take a while, as a rescan is done, looking for existing transactions.
     * Optional [rescan] parameter added in 0.8.0.
     * Note: There's no need to import public key, as in ECDSA (unlike RSA) this can be computed from private key.
     * Requires unlocked wallet
     *
     * @param string $key
     * @param string $label
     * @param bool $rescan
     *
     * @return bool|null
     */
    public function importPrivateKey($key, $label = null, $rescan = true)
    {
        $this->makeRpcCall('importPrivKey', [$key, $label, $rescan]);
    }

    /**
     * Fills the keyPool, requires wallet passPhrase to be set.
     * Requires unlocked wallet
     *
     * @return bool|null
     */
    public function keyPoolRefill()
    {
        return $this->makeRpcCall('keyPoolRefill');
    }

    /**
     * Returns Object that has account names as keys, account balances as values.
     *
     * @param int $minConf
     *
     * @return array|null
     */
    public function listAccounts($minConf = 1)
    {
        return $this->makeRpcCall('listAccounts', [$minConf]);
    }

    /**
     * Returns all addresses in the wallet and info used for coin control.
     * @version 0.7
     *
     * @return array|null
     */
    public function listAddressGroupings()
    {
        return $this->makeRpcCall('listAddressGroupings');
    }

    /**
     * Returns an array of objects containing:
     * [
     *  'account' => string,
     *  'amount' => decimal,
     *  'confirmations' => true,
     * ]
     *
     * @param int $minConf
     * @param bool $includeEmpty
     *
     * @return array|null
     */
    public function listReceivedByAccount($minConf = 1, $includeEmpty = false)
    {
        return $this->makeRpcCall('listReceivedByAccount', [$minConf, $includeEmpty]);
    }

    /**
     * Returns an array of objects containing:
     * [
     *  'address' => string,
     *  'account' => string,
     *  'amount' => decimal,
     *  'confirmations' => true,
     * ]
     * To get a list of accounts on the system, execute @listReceivedByAddress 0 true
     *
     * @param int $minConf
     * @param bool $includeEmpty
     *
     * @return array|null
     */
    public function listReceivedByAddress($minConf = 1, $includeEmpty = false)
    {
        return $this->makeRpcCall('listReceivedByAddress', [$minConf, $includeEmpty]);
    }

    /**
     * Get all transactions in blocks since block [blockhash], or all transactions if omitted.
     * [target-confirmations] intentionally does not affect the list of returned transactions,
     * but only affects the returned "lastblock" value.
     * @link https://github.com/bitcoin/bitcoin/pull/199#issuecomment-1514952
     *
     * @param string|null $blockHash
     * @param int|null $targetConfirmations
     *
     * @return \stdClass|null
     */
    public function listSinceBlock($blockHash = null, $targetConfirmations = null)
    {
        return $this->makeRpcCall('listSinceBlock', [$blockHash, $targetConfirmations]);
    }

    /**
     * Returns up to [count] most recent transactions skipping the first [from] transactions for account [account].
     * If [account] not provided it'll return recent transactions from all accounts.
     *
     * @param string|null $account
     * @param int|null $count
     * @param int|null $from
     *
     * @return array|null
     */
    public function listTransactions($account = null, $count = 10, $from = 0)
    {
        return $this->makeRpcCall('listTransactions', [$account, $count, $from]);
    }

    /**
     * Returns array of unspent transaction inputs in the wallet.
     * @version 0.7
     *
     * @param int $minConf
     * @param int $maxConf
     *
     * @return array|null
     */
    public function listUnspent($minConf = 1, $maxConf = 999999)
    {
        return $this->makeRpcCall('listUnspent', [$minConf, $maxConf]);
    }

    /**
     * Returns list of temporarily unspendable outputs
     * @version 0.8
     *
     * @return array|null
     */
    public function listLockUnspent()
    {
        return $this->makeRpcCall('listLockUnspent');
    }

    /**
     * Updates list of temporarily unspendable outputs
     * @version 0.8
     *
     * @param bool $unlock
     * @param array|null $objects
     *
     * @return bool|null
     */
    public function lockUnspent($unlock, $objects = null)
    {
        $params = [$unlock];
        if (!is_null($objects)) {
            $params[] = $objects;
        }
        return $this->makeRpcCall('lockUnspent', $params);
    }

    /**
     * Move from one account in your wallet to another
     *
     * @param string      $fromAccount
     * @param string      $toAccount
     * @param float       $amount
     * @param int         $minConf
     * @param string|null $comment
     *
     * @return bool|null
     */
    public function move($fromAccount, $toAccount, $amount, $minConf = 1, $comment = null)
    {
        $params = [
            $fromAccount,
            $toAccount,
            $amount,
            $minConf,
        ];
        if (!is_null($comment)) {
            $params[] = $comment;
        }
        return $this->makeRpcCall('move', $params);
    }

    /**
     * <amount> is a real and is rounded to 8 decimal places.
     * Will send the given amount to the given address,
     * ensuring the account has a valid balance using [minConf] confirmations.
     * Returns the transaction ID if successful (not in JSON object).
     * Requires unlocked wallet
     *
     * @param string        $fromAccount
     * @param string        $toAddress
     * @param float         $amount
     * @param int           $minConf
     * @param string|null   $comment
     * @param string|null   $commentTo
     *
     * @return string|null
     */
    public function sendFrom($fromAccount, $toAddress, $amount, $minConf = 1, $comment = null, $commentTo = null)
    {
        $params = [
            $fromAccount,
            $toAddress,
            $amount,
            $minConf,
        ];
        if (!is_null($comment)) {
            $params[] = $comment;
        }
        if (!is_null($commentTo)) {
            $params[] = $commentTo;
        }
        return $this->makeRpcCall('sendFrom', $params);
    }

    /**
     * amounts are double-precision floating point numbers
     * Requires unlocked wallet
     *
     * Receivers array:
     * [
     *  address => amount,
     *  . . .
     * ]
     *
     * @param string        $fromAccount
     * @param array         $receivers
     * @param int           $minConf
     * @param string|null   $comment
     *
     * @return \stdClass|null
     */
    public function sendMany($fromAccount, $receivers, $minConf = 1, $comment = null)
    {
        $params = [
            $fromAccount,
            $receivers,
            $minConf
        ];
        if (!is_null($comment)) {
            $params[] = $comment;
        }
        return $this->makeRpcCall('sendMany', $params);
    }

    /**
     * Submits raw transaction (serialized, hex-encoded) to local node and network.
     * @link https://en.bitcoin.it/wiki/Raw_Transactions
     * @version 0.7
     *
     * @param string $hexString
     *
     * @return string|null
     */
    public function sendRawTransaction($hexString)
    {
        return $this->makeRpcCall('sendRawTransaction', [$hexString]);
    }

    /**
     * <amount> is a real and is rounded to 8 decimal places.
     * Returns the transaction ID <txId> if successful.
     * Requires unlocked wallet
     *
     * @param string      $address
     * @param float       $amount
     * @param string|null $comment
     * @param string|null $commentTo
     *
     * @return string|null
     */
    public function sendToAddress($address, $amount, $comment = null, $commentTo = null)
    {
        $params = [
            $address,
            $amount,
        ];
        if (!is_null($comment)) {
            $params[] = $comment;
        }
        if (!is_null($commentTo)) {
            $params[] = $commentTo;
        }
        return $this->makeRpcCall('sendToAddress', $params);
    }

    /**
     * Sets the account associated with the given address.
     * Assigning address that is already assigned to the same account
     * will create a new address associated with that account.
     *
     * @param string $address
     * @param string $account
     *
     * @return string|null
     */
    public function setAccount($address, $account)
    {
        return $this->makeRpcCall('setAccount', [$address, $account]);
    }

    /**
     * <generate> is true or false to turn generation on or off.
     * Generation is limited to [genProcessorLimit] processors, -1 is unlimited.
     *
     * @param bool $generate
     * @param int  $genProcessorLimit
     */
    public function setGenerate($generate, $genProcessorLimit = -1)
    {
        $this->makeRpcCall('setGenerate', [$generate, $genProcessorLimit]);
    }

    /**
     * <amount> is a real and is rounded to the nearest 0.00000001
     *
     * @param float $amount
     *
     * @return bool|null
     */
    public function setTxFee($amount)
    {
        return $this->makeRpcCall('setTxFee', [$amount]);
    }

    /**
     * Sign a message with the private key of an address.
     * Requires unlocked wallet
     *
     * @param string $address
     * @param string $message
     *
     * @return bool|null
     */
    public function signMessage($address, $message)
    {
        return $this->makeRpcCall('signMessage', [$address, $message]);
    }

    /**
     * Adds signatures to a raw transaction and returns the resulting raw transaction.
     * May require unlocked wallet
     * @link https://en.bitcoin.it/wiki/Raw_Transactions
     * @version 0.7
     *
     * @param string $hexString
     * @param array|null $data
     * @param array|null $keys
     *
     * @return \stdClass|bool|string|null
     */
    public function signRawTransaction($hexString, $data = null, $keys = null)
    {
        $params = [$hexString];
        if (!is_null($data)) {
            $params[] = $data;
        }
        if (!is_null($keys)) {
            $params[] = $keys;
        }
        return $this->makeRpcCall('signRawTransaction', $params);
    }

    /**
     * Stop bitcoin server.
     */
    public function stop()
    {
        $this->makeRpcCall('stop');
    }

    /**
     * Attempts to submit new block to network.
     *
     * @param string     $hexData
     * @param mixed|null $optionals
     *
     * @return true|null
     */
    public function submitBlock($hexData, $optionals = null)
    {
        $params = [$hexData];
        if (!is_null($optionals)) {
            $params[] = $optionals;
        }
        return $this->makeRpcCall('submitBlock', $params);
    }

    /**
     * Return information about <address>.
     *
     * @param string $address
     *
     * @return \stdClass|null
     */
    public function validateAddress($address)
    {
        return $this->makeRpcCall('validateAddress', [$address]);
    }

    /**
     * Verify a signed message.
     *
     * @param string $address
     * @param string $signature
     * @param string $message
     *
     * @return bool|null
     */
    public function verifyMessage($address, $signature, $message)
    {
        return $this->makeRpcCall('verifyMessage', [$address, $signature, $message]);
    }

    /**
     * Removes the wallet encryption key from memory, locking the wallet.
     * After calling this method, you will need to call walletPassPhrase again
     * before being able to call any methods which require the wallet to be unlocked.
     *
     * @return bool|null
     */
    public function walletLock()
    {
        return $this->makeRpcCall('walletLock');
    }

    /**
     * Stores the wallet decryption key in memory for <timeout> seconds.
     *
     * @param string $passPhrase
     * @param int $timeout
     *
     * @return bool|null
     */
    public function walletPassPhrase($passPhrase, $timeout)
    {
        return $this->makeRpcCall('walletPassPhrase', [$passPhrase, $timeout]);
    }

    /**
     * Changes the wallet passPhrase from <oldPassPhrase> to <newPassPhrase>.
     *
     * @param $oldPassPhrase
     * @param $newPassPhrase
     *
     * @return bool|null
     */
    public function walletPassPhraseChange($oldPassPhrase, $newPassPhrase)
    {
        return $this->makeRpcCall('walletPassPhraseChange', [$oldPassPhrase, $newPassPhrase]);
    }

    /**
     * Returns the estimated network hashes per second based on the last 120 blocks.
     * Pass in [blocks] to override # of blocks, -1 specifies since last difficulty change.
     * Pass in [height] to estimate the network speed at the time when a certain block was found.
     *
     * @version 0.9
     *
     * @param int|null  $blocks
     * @param int|null  $height
     *
     * @return int|null
     */
    public function getNetworkHashPerSecond($blocks = null, $height = null)
    {
        $params = [];
        if (!is_null($blocks)) {
            $params[] = $blocks;
        }
        if (!is_null($height)) {
            $params[] = $height;
        }
        return $this->makeRpcCall('getNetworkHashPs', $params);
    }
}