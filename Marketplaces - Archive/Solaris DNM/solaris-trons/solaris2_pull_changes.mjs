#!/usr/bin/env tron

// обновляет git-репозиторий для указанной цели (флаг `-t`).

import { pull as gitPull } from "isomorphic-git";
import * as http from "isomorphic-git/http/node/index.cjs";
import fs from "node:fs";

$.verbose = false;

const oldCwd = process.cwd();

let gitDir;
switch (argv.t) {
  case 'code':
    gitDir = '/share/app/code';
    break;
  case 'share':
    gitDir = '/share';
    break;
  case 'trons':
    gitDir = '/opt/solaris-trons';
    break;
  default:
    console.error(`Unknown target: ${argv.t}`);
    process.exit(1);
}

cd(gitDir);

await gitPull({
  onAuth(u) {
    return {
      username: 'morph',
      password: 'glpat-pH8rxCGGi-pqfxMdsKqb'
    }
  },
  fs,
  http,
  dir: gitDir,
  ref: 'master',
  singleBranch: true,
  author: {
    name: 'morph',
    email: 'hpromatem@protonmail.com'
  },
  oauth2format: 'gitlab'
});

if (argv.t !== 'trons' && !argv.skippp) {
  const result = await $`${path.join(__dirname, "solaris2_post_process.mjs")}`.nothrow();
  if (result.exitCode === 0) {
    console.log("ok");
  } else {
    console.log(result.stderr.trim())
  }
} else if (argv.simplepp) {
  // Назначаем каждому шопу свой redis database index
  const shops = await getShops();

  for (const shopId of shops) {
    const shopBasePath = path.join(SOLARIS_PROJECTS_PATH, shopId);
    cd(shopBasePath);

    try {
      console.log(`[${shopId}] stopping docker`);
      await $`./docker-compose.sh down`;

      console.log(`[${shopId}] rm -R laravel_cache/*`);
      try {
        await $`rm -R laravel_cache/*`;
      } catch (ex) {
        console.log(`[${shopId}] clear cache error`);
        console.error(ex);
      }

      // ??? - оно может и не надо это тут, учитывая, что в `up` отдаётся флаг `--build`
      console.log(`[${shopId}] rebuilding docker`);
      await $`./docker-compose.sh build`;

      console.log(`[${shopId}] starting docker with --build`);
      await $`./docker-compose.sh up -d --build`;

      console.log(`[${shopId}] artisan route:clear`);
      await $`./docker-compose.sh exec php-fpm ./artisan route:clear`;

      // artisan ...
      console.log(`[${shopId}] artisan migrate --force`);
      await $`./docker-compose.sh exec php-fpm ./artisan migrate --force`;

    } catch (ex) {
      console.log(`[${shopId}] fail`);
      console.error(ex);
    }
  }
}


cd(oldCwd);