#!/usr/bin/env tron

import { SOLARIS_PROJECTS_PATH, getShops } from './common.mjs';

$.verbose = false;

const oldCwd = process.cwd();
const shops = await getShops();


for (const shop of shops) {
  cd(path.join(SOLARIS_PROJECTS_PATH, shop));

  let result = await $`./docker-compose.sh down && ./docker-compose.sh up -d`.nothrow();
  if (result.exitCode === 0) {
    console.log(`${shop}: ok`);
  } else {
    console.error(`${shop}: incomplete`);
  }
}

cd(oldCwd);
