#!/usr/bin/env tron

$.verbose = false;

const payoutAccount = `TOTAL_PAYOUT`;

// Получаем балансы всех аккаунтов
let accounts = {};
const listAccountsResult = await $`bitcoin-cli -conf=/share/bitcoin.conf listaccounts`;
if (listAccountsResult.exitCode === 0) {
  accounts = JSON.parse(listAccountsResult.stdout);
}

let totalBtcAmount = 0;

for (const [shop, btcAmount] of Object.entries(accounts)) {
  if (!btcAmount || btcAmount < 0) {
    continue;
  }

  const bitcoinResult = await $`bitcoin-cli -conf=/share/bitcoin.conf move "${shop}" "${payoutAccount}" "${btcAmount}"`;
  if (bitcoinResult.exitCode === 0) {
    console.log(`${shop}:`, btcAmount);
    totalBtcAmount += btcAmount;
  }
}
if (totalBtcAmount === 0) {
  console.log('Nothing to do');
} else {
  console.log("---");
  console.log('Total payout:', totalBtcAmount);
}
