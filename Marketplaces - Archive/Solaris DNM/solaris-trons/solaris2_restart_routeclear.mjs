#!/usr/bin/env tron

import { SOLARIS_PROJECTS_PATH, getShops } from './common.mjs';

$.verbose = false;

const oldCwd = process.cwd();
const shops = await getShops();

for (const shopId of shops) {
  cd(path.join(SOLARIS_PROJECTS_PATH, shopId));

  const result = await $`./docker-compose.sh down && ./docker-compose.sh up -d`.nothrow();
  if (result.exitCode === 0) {
    await $`./docker-compose.sh exec php-fpm ./artisan route:clear`;
    console.log(`[${shopId}] ok`);
  } else {
    console.error(`[${shopId}] fail`);
    console.error(result.stderr.trim());
  }
}

cd(oldCwd);
