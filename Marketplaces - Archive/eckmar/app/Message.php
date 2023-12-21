<?php

namespace App;

use App\Exceptions\RequestException;
use App\Marketplace\Encryption\Cipher;
use App\Marketplace\Encryption\DecryptionKey;
use App\Marketplace\Encryption\EncryptedMessage;
use App\Marketplace\Encryption\EncryptionKey;
use App\Marketplace\Encryption\Keypair;
use App\Traits\Uuids;
use Carbon\Carbon;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ParagonIE\EasyRSA\EasyRSA;
use ParagonIE\EasyRSA\PrivateKey;
use ParagonIE\EasyRSA\PublicKey;

class Message extends Model
{
    use Uuids;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = ['read'];

    /**
     * Determines if the parameter $message is encrypted
     *
     * @param $message
     * @return bool
     */
    public static function messageEncrypted($message) : bool
    {
        $message = trim($message); // fix for blank chars at the start and end
        $startsWith = "-----BEGIN PGP MESSAGE-----";
        $endsWith = "-----END PGP MESSAGE-----";

        // if the content starts with string and ends with pgp string
        return
            substr($message , 0, strlen($startsWith)) === $startsWith
            &&
            substr($message, - strlen($endsWith)) == $endsWith;
    }

    /**
     * Generate Market keypar if it is not generated
     */
    public static function generateMarketKeypair()
    {
        // create users public and private RSA Keys
        $keyPair = new Keypair();
        $privateKey = $keyPair->getPrivateKey();
        $publicKey =   $keyPair->getPublicKey();
        // encrypt private key with user's password
        $encryptedPrivate = encrypt($privateKey);
        $encryptedPublic = encrypt($publicKey);

        // save to files
        if(!Storage::exists('marketkey.private')
            && !Storage::exists('marketkey.public')) {

            Storage::put('marketkey.private', $encryptedPrivate);
            Storage::put('marketkey.public', $encryptedPublic);
        }
    }

    /**
     * Return encrypted Market public key
     *
     * @return mixed
     */
    public static function getMarketPublicKey()
    {
        if(!Storage::exists('marketkey.public'))
            self::generateMarketKeypair();
        return Storage::get('marketkey.public');
    }

    /**
     * Return encrypted private key of the market
     *
     * @return mixed
     */
    public static function getMarketPrivateKey()
    {
        if(!Storage::exists('marketkey.private'))
            self::generateMarketKeypair();
        return Storage::get('marketkey.private');
    }

    /**
     * Generate one-time private key for mass messages
     *
     * @return string
     * @throws \RequestException
     */
    public static function encryptedPrivateKey() : string
    {
            // create users public and private RSA Keys
            $keyPair = new Keypair();
            $privateKey = $keyPair->getPrivateKey();

            return $privateKey;
    }


    /**
     * Relationship with the User who sends the messages
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this -> hasOne(\App\User::class, 'id', 'sender_id');
    }

    /**
     * Relationship with the Conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation()
    {
        return $this -> belongsTo(\App\Conversation::class, 'conversation_id');
    }

    /**
     * Set the conversation of the message
     *
     * @param Conversation $conversation
     */
    public function setConversation(Conversation $conversation)
    {
        $this ->conversation_id = $conversation -> id;
    }

    /**
     * Set the user sender of the message
     *
     * @param User $user
     */
    public function setSender(User $user)
    {
        $this -> sender_id = $user -> id;
    }
    /**
     * Set the user receiver of the message
     *
     * @param User $user
     */
    public function setReceiver(User $user)
    {
        $this -> receiver_id = $user -> id;
    }

    /**
     * Returns if this message is sent by market
     *
     * @return bool
     */
    public function isMassMessage() : bool
    {
        return $this -> sender_id == null;
    }

    public function getReceiver(): User{
        return User::findOrFail($this->receiver_id);
    }
    public function getSender(): User{
        if($this -> sender_id)
            return User::findOrFail($this->sender_id);

        // Return stub user if it is not selected
        return User::stub();

    }

    public function getContentSenderAttribute($value){
        return decrypt($value);
    }
    public function getContentReceiverAttribute($value){
        return decrypt($value);
    }
    public function getNonceSenderAttribute($value){
        return decrypt($value);
    }
    public function getNonceReceiverAttribute($value){
        return decrypt($value);
    }

    public function setContentSenderAttribute($value){
        $this->attributes['content_sender'] = encrypt($value);
    }
    public function setContentReceiverAttribute($value){
        $this->attributes['content_receiver'] = encrypt($value);
    }
    public function setNonceSenderAttribute($value){
        $this->attributes['nonce_sender'] = encrypt($value);
    }
    public function setNonceReceiverAttribute($value){
        $this->attributes['nonce_receiver'] = encrypt($value);
    }

    /**
     * Returns string for time ago
     *
     * @return string
     */
    public function timeAgo()
    {
        return Carbon::parse($this -> created_at) -> diffForHumans();
    }

    /**
     * Determines if the message is encrypted
     *
     * @return bool
     *
     */
    public function isEncrypted() : bool
    {
        return self::messageEncrypted($this -> content);
    }


    /**
     * Setting mass message content
     *
     * @param $content
     * @param User $receiver
     * @throws \RequestException
     */
    public function setMassMessageContent($content, User $receiver)
    {
        try {
            /**
             * Set empty content sender
             */
            $this->content_sender = '';
            $this->nonce_sender = '';
            /**
             * Set content for receiver
             */
            $private_market_key = decrypt(Message::getMarketPrivateKey());
            $public_key_receiver = decrypt($receiver->msg_public_key);
            $receiver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                $private_market_key,
                $public_key_receiver
            );
            $nonce_receiver = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);

            $content_receiver = sodium_crypto_box(
                $content,
                $nonce_receiver,
                $receiver_keypair
            );
            $this->nonce_receiver = $nonce_receiver;
            $this->content_receiver = $content_receiver;
        }
        catch (\SodiumException $e){
            \Illuminate\Support\Facades\Log::error($e);
            throw new RequestException('Error with encryption, please try again!');
        }
    }

    /**
     * Setting content of the message, with encryption
     *
     * @param $content
     * @param User $sender
     * @param User $receiver
     * @throws \RequestException
     */
    public function setContent($content,User $sender ,User $receiver) {
        try{
            $private_key_sender = decrypt(session()->get('private_rsa_key_decrypted'));
            $public_key_sender = decrypt($sender->msg_public_key);
            /**
             * Set content for sender
             */

            $sender_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                $private_key_sender,
                $public_key_sender
            );
            $nonce_sender = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
            $content_sender = sodium_crypto_box(
                $content,
                $nonce_sender,
                $sender_keypair
            );
            $this->content_sender = $content_sender;
            $this->nonce_sender = $nonce_sender;
            /**
             * Set content for receiver
             */
            $public_key_receiver = decrypt($receiver->msg_public_key);
            $receiver_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                $private_key_sender,
                $public_key_receiver
            );
            $nonce_receiver= random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
            $content_receiver = sodium_crypto_box(
                $content,
                $nonce_receiver,
                $receiver_keypair
            );
            $this->nonce_receiver = $nonce_receiver;
            $this->content_receiver = $content_receiver;
        }
        catch (\SodiumException $e){
            throw new RequestException('Error with encryption, please try again!');
        }
    }
    /**
     * Decrypts content with key
     *
     * @return string
     */
    public function getContent() {

        $user = auth()->user();
        $user_private_key = decrypt(session()->get('private_rsa_key_decrypted'));

        // get sender check for existanse cause sender can be stub
        if($this -> getSender() -> id  && $user->id == $this->getSender()->id){
            /**
             * Decrypt sender content and return it
             */
            $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                $user_private_key,
                decrypt($user->msg_public_key)
            );
            $plaintext = sodium_crypto_box_open(
                $this->content_sender,
                $this->nonce_sender,
                $keypair
            );

            return urldecode($plaintext);

        } else if ($user->id == $this->getReceiver()->id){
            $plaintext = '';
            // normal message
            if($this -> getSender() -> exists) {
                /**
                 * Decrypt receiver content and return it
                 */
                $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                    $user_private_key,
                    decrypt($this->getSender()->msg_public_key)
                );
                $plaintext = sodium_crypto_box_open(
                    $this->content_receiver,
                    $this->nonce_receiver,
                    $keypair
                );
            }
            else {
                /**
                 * Decrypt receiver content and return it
                 */
                $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
                    $user_private_key,
                    decrypt(self::getMarketPublicKey())
                );
                $plaintext = sodium_crypto_box_open(
                    $this->content_receiver,
                    $this->nonce_receiver,
                    $keypair
                );
            }
            return urldecode($plaintext);
        }
        return '';
    }


}
