<?php

use Illuminate\Database\Seeder;
use \App\User;
class UsersSeeder extends Seeder
{


    private $numberOfAccounts = 50;
    private $fakerFactory;
    private $createdAccounts = 0;

    public function __construct() {
        $this->fakerFactory = Faker\Factory::create();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $start = microtime(true);
        $this->command->info('Creating users...');

        // if there is no buyer create it
        $buyer = User::where('username','buyer')->first();
        if ($buyer == null){
            $this->command->info('There is no [buyer] account, creating it...');
            $this->generateBuyerAccount();
        } else {
            $this->command->info('Account [buyer] is present');
        }

        // if there is no admin create it
        $admin = User::where('username','admin')->first();
        if ($admin == null){
            $this->command->info('There is no [admin] account, creating it...');
            $this->generateAdminAccount();
        } else {
            $this->command->info('Account [admin] is present');
        }

        $this->command->info('Starting generation of random accounts...');
        for ($i = 0; $i < $this->numberOfAccounts; $i++){
            $user = new User();
            $username =  $this->generateUsername();
            $user->username = $username;
            $userpassword = bcrypt($username.'123');
            $user->password = $userpassword;
            $user->mnemonic = bcrypt(hash('sha256',$username));
            $user->login_2fa = false;
            $user->referral_code = strtoupper(str_random(6));
            $userKeyPair =  new \App\Marketplace\Encryption\Keypair();
            $userPrivateKey = $userKeyPair->getPrivateKey();
            $userPublicKey = $userKeyPair->getPublicKey();
            $userEncryptedPrivateKey = \Defuse\Crypto\Crypto::encryptWithPassword($userPrivateKey, $userpassword);

            $user->msg_private_key = $userEncryptedPrivateKey;
            $user->msg_public_key = encrypt($userPublicKey);
            $user->pgp_key = 'test';
            $user->save();

            // generate deposit addresses for every user
            $this->generateDepositAddressSeed($user);//$user -> generateDepositAddresses();
            // every fifth user is vendor
           if ($i % 5 == 0){
               $user->becomeVendor('testAddress#'.strtoupper(str_random(6)));
           }
            $this->command->info('Created User '.($i+1).'/'.$this->numberOfAccounts);
            $this->createdAccounts++;
        }
        $end = (microtime(true) - $start);
        $this->command->info('Successfully generated '.$this->createdAccounts.' users. Elapsed time: '.$this->formatTime($end));

    }

    /**
     * Generate admin account
     *
     * @throws \App\Exceptions\RequestException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function generateAdminAccount(){
        $adminpassword = 'admin123';
        $admin = new User();
        $admin->username = 'admin';
        $admin->password = bcrypt($adminpassword);
        $admin->mnemonic = bcrypt(hash('sha256', "na kraj sela zuta kuca"));
        $admin->login_2fa = false;
        $admin->referral_code = "UUF7NZ";

        $adminKeyPair = new \App\Marketplace\Encryption\Keypair();
        $adminPrivateKey = $adminKeyPair->getPrivateKey();
        $adminPublicKey = $adminKeyPair->getPublicKey();
        $AdminEcnryptedPrivateKey = \Defuse\Crypto\Crypto::encryptWithPassword($adminPrivateKey, $adminpassword);

        $admin->msg_private_key = $AdminEcnryptedPrivateKey;
        $admin->msg_public_key = encrypt($adminPublicKey);
        $admin->pgp_key = 'test';
        $admin->save();
        $nowTime = \Carbon\Carbon::now();
        \App\Admin::insert([
            'id' => $admin->id,
            'created_at' => $nowTime,
            'updated_at' => $nowTime
        ]);
        $this->generateDepositAddressSeed($admin);//$admin -> generateDepositAddresses();
        $admin->becomeVendor('test');

        $this->command->info('Created [admin] account');
        $this->createdAccounts++;
    }

    /**
     * Generate buyer account
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function generateBuyerAccount(){

        $buyerpassword = 'buyer123';
        $buyer = new User();
        $buyer->username = 'buyer';
        $buyer->password = bcrypt($buyerpassword);
        $buyer->mnemonic = bcrypt(hash('sha256', "na kraj sela zuta kuca"));
        $buyer->login_2fa = false;
        $buyer->referral_code = "UUF7NZ";

        $buyerKeyPair = new \App\Marketplace\Encryption\Keypair();
        $buyerPrivateKey = $buyerKeyPair->getPrivateKey();
        $buyerPublicKey = $buyerKeyPair->getPublicKey();
        $buyerEcnryptedPrivateKey = \Defuse\Crypto\Crypto::encryptWithPassword($buyerPrivateKey, $buyerpassword);

        $buyer->msg_private_key = $buyerEcnryptedPrivateKey;
        $buyer->msg_public_key = encrypt($buyerPublicKey);

        $buyer->save();

        $this->generateDepositAddressSeed($buyer);//$buyer -> generateDepositAddresses();

        $this->command->info('Created [buyer] account');
        $this->createdAccounts++;

    }

    public function generateUsername(){
        $faker = $this->fakerFactory;
        $userName = $faker->userName;
        $user = User::where('username',$userName)->first();
        while ($user !== null){
            $userName = $faker->userName;
            $user = User::where('username',$userName)->first();
        }
        return $userName;
    }

    /**
     *  Accepts number of seconds elapsed and returns hours:minutes:seconds
     *
     * @param $s
     * @return string
     */
    private function formatTime($s)
    {
        $h = floor($s / 3600);
        $s -= $h * 3600;
        $m = floor($s / 60);
        $s -= $m * 60;
        return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
    }

    private function generateDepositAddressSeed(User $user){
        $coinsClasses = config('coins.coin_list');

        $coinsToSeed = config('marketplace.seeder_coins');

        $seederCoinsClasses = [];

        foreach ($coinsToSeed as $coin){
            $seederCoinsClasses[$coin] = $coinsClasses[$coin];
        }

        // vendor fee in usd
        $marketVendorFee =  config('marketplace.vendor_fee');


        // for each supported coin generate instance of the coin
        foreach ($seederCoinsClasses as $short => $coinClass){
            $coinsService = new $coinClass();
            try {
                // Add new deposit address
                $newDepositAddress = new \App\VendorPurchase();
                $newDepositAddress->user_id = $user->id;

                $newDepositAddress->address = $coinsService->generateAddress(['user' => $user->id]);
                $newDepositAddress->coin = $coinsService->coinLabel();

                $newDepositAddress->save();
            }catch(\Exception $e){
                \Illuminate\Support\Facades\Log::error($e);
            }
        }
    }
}
