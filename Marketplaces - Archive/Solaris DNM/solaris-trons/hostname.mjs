#!/usr/bin/env tron

// Изменение системного `hostname`.

$.verbose = false;

if (argv._.length !== 1) {
  console.log(`usage: ${__filename} <name>`);
  process.exit(1);
}

const newHostname = argv._[0];
const oldHostname = (await $`hostname`.nothrow()).stdout.trim();
if (oldHostname !== newHostname) {
  await $`hostname ${newHostname}`;
  await $`sed -i 's/${oldHostname}/${newHostname}/g' /etc/hostname`;
  await $`sed -i 's/${oldHostname}/${newHostname}/g' /etc/hosts`;
  const changedHostname = (await $`hostnamectl`).stdout.split('\n')[0].split(': ')[1].trim();
  if (newHostname === changedHostname) {
    console.log(`hostname: ${changedHostname}`);
  }
}
