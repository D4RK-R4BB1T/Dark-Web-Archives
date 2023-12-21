#!/usr/bin/env tron

import { floorToFixed3 } from "./common.mjs";

$.verbose = false;

if (argv._.length < 2) {
  console.log(`usage: ${path.basename(__filename)} <ACCOUNT> <ADDRESS> [<AMOUNT>]`);
  process.exit(1);
}

const account = argv._[0];
const address = argv._[1];
let userAmount = null;
if (argv._.length === 3) {
  userAmount = argv._[2];
}

const accountBalance = +(await $`bitcoin-cli -conf=/share/bitcoin.conf getbalance ${argv._[0]}`.nothrow()).stdout;
if (typeof accountBalance === 'number' && accountBalance > 0) {
  const withdrawAmount = userAmount === null ? floorToFixed3(accountBalance) : userAmount;
  const withdrawResult = await $`bitcoin-cli -conf=/share/bitcoin.conf sendfrom ${account} ${address} ${withdrawAmount}`.nothrow();
  if (withdrawResult.exitCode === 0) {
    console.log(`${account}: ${withdrawAmount} (${withdrawResult.stdout.trim()})`);
  } else {
    console.error(withdrawResult.stderr);
    process.exit(1);
  }
}
