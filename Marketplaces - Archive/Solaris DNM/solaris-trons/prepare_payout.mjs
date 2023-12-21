#!/usr/bin/env tron

import { roundToFixed2, SOLARIS_PROJECTS_PATH, getBtcUsdPrice } from './common.mjs';

$.verbose = false;

const btcPrice = await getBtcUsdPrice();
const oldCwd = process.cwd();

const now = await $`date '+%d%m%y'`;
const payoutAccount = `PAYOUT${now.stdout.trim()}`;

// Получаем балансы всех аккаунтов
let accounts = {};
const listAccountsResult = await $`bitcoin-cli -conf=/share/bitcoin.conf listaccounts`;
if (listAccountsResult.exitCode === 0) {
  accounts = JSON.parse(listAccountsResult.stdout);
}

const incomesResult = await $`${path.join(__dirname, "list_incomes.mjs")}`.nothrow();
if (incomesResult.exitCode !== 0) {
  console.error(incomesResult.stderr);
  process.exit(1);
}
const incomes = YAML.parse(incomesResult.stdout);

let totalBtcAmount = 0;

for (const [shop, btcAmount] of Object.entries(incomes)) {
  if (!btcAmount || btcAmount < 0) {
    continue;
  }

  // Проверяем достуность баланса у аккаунта
  if (accounts[shop] && accounts[shop] < btcAmount) {
    console.log(`${shop}: total amount of incomes (${btcAmount}) is greater than available account balance (${accounts[shop]}). ignore!`);
    continue;
  }

  cd(path.join(SOLARIS_PROJECTS_PATH, shop));
  const usdAmount = roundToFixed2(btcPrice * btcAmount);

  const bitcoinResult = await $`bitcoin-cli -conf=/share/bitcoin.conf move "${shop}" "${payoutAccount}" "${btcAmount}"`;
  if (bitcoinResult.exitCode === 0) {
    // console.log(`${path.join(SOLARIS_PROJECTS_PATH, shop, 'docker-compose.sh')} exec database mysql -t -uroot -prootpassword database "\${@:2}" -e "insert into incomes(wallet_id, amount_usd, amount_btc, description, created_at, updated_at) values('-1', '-${usdAmount}', '-${btcAmount}', 'Выплата комиссии', NOW(), NOW());" -N`);
    const mysqlResult = await $`${path.join(SOLARIS_PROJECTS_PATH, shop, 'docker-compose.sh')} exec database mysql -t -uroot -prootpassword database "\${@:2}" -e "insert into incomes(wallet_id, amount_usd, amount_btc, description, created_at, updated_at) values('-1', '-${usdAmount}', '-${btcAmount}', 'Выплата комиссии', NOW(), NOW());" -N`.nothrow();
    if (mysqlResult.exitCode === 0) {
      console.log(`${shop}:`, btcAmount);
      totalBtcAmount += btcAmount;
    }
  }
}
if (totalBtcAmount === 0) {
  console.log('Nothing to do');
} else {
  console.log("---");
  console.log('Total payout:', totalBtcAmount);
}

cd(oldCwd);