#!/usr/bin/env tron

// Скрипт возвращает карту аккаунтов и их балансов в YAML-формате.

$.verbose = false;

let accounts = {};
const listAccountsResult = await $`bitcoin-cli -conf=/share/bitcoin.conf listaccounts`;
if (listAccountsResult.exitCode === 0) {
  accounts = JSON.parse(listAccountsResult.stdout);
}

if (argv.sum) {
  let totalAmount = 0;
  for (const amount of Object.values(accounts)) {
    totalAmount += amount;
  }
  accounts['sum'] = totalAmount;
}

console.log(YAML.stringify(accounts));
