#!/usr/bin/env tron

$.verbose = false;

const result = await $`docker inspect ${argv._[0]} | grep '"IPAddress": "'`.nothrow();
if (result.exitCode === 0) {
  const lines = result.stdout.trim().split("\n");
  const regex = new RegExp(/(\b25[0-5]|\b2[0-4][0-9]|\b[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}/);
  const matchResult = regex.exec(lines[lines.length - 1]);
  if (matchResult && matchResult.length > 0) {
    console.log(matchResult[0].trim());
  } else {
    process.exit(1);
  }
} else {
  process.exit(1);
}
