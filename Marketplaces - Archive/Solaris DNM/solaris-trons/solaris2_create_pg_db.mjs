#!/usr/bin/env tron

$.verbose = false;

const dbName = argv._[0];

const result = await $`sudo -u postgres createdb -O solaris -E UTF-8 ${dbName}`.nothrow();
if (result.exitCode === 0) {
  console.log(result.stdout);
} else {
  console.error(result.stderr);
}
