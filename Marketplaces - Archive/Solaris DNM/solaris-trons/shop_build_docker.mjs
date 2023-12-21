#!/usr/bin/env tron

import { SOLARIS_PROJECTS_PATH } from './common.mjs';

$.verbose = false;

const oldCwd = process.cwd();

cd(path.join(SOLARIS_PROJECTS_PATH, argv._[0]));
let result = await $`./docker-compose.sh build`.nothrow();
cd(oldCwd);
process.exit(result.exitCode === 0 ? 0 : 1);