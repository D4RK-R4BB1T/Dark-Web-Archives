#!/usr/bin/env tron

import { getShops, runInternalScript } from './common.mjs';

$.verbose = true;

const shops = await getShops();
const concurrent = argv.c;
const queue = new Map();

const runNext = () => {
  for (let i = 0; i < concurrent - queue.size && shops.length > 0; i++) {
    const shopName = shops.shift();
    const p = runInternalScript(argv.s, true, shopName);
    p.then(() => {
      queue.delete(shopName);
      runNext();
    }).catch(() => {
      queue.delete(shopName);
      runNext();
    });
    queue.set(shopName, p);
  }
};

runNext();

const check = () => {
  if (queue.size > 0 || shops.length > 0) {
    setTimeout(check, 1000);
  }
}

setTimeout(check, 1000);