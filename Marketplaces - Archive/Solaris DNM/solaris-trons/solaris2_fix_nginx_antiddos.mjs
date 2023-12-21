#!/usr/bin/env tron

import { getLANIp } from './common.mjs';


$.verbose = false;

const lanIp = await getLANIp();

console.log(`fixing nginx antiddos files`);

const basePath = '/share/app/docker/nginx/antiddos';
const files = await fs.readdir(basePath);
for (const f of files) {
  await $`sed -i 's/red:connect("redis", 6379)/red:connect("${lanIp}", 6379)/g' ${basePath}/${f}`;
}