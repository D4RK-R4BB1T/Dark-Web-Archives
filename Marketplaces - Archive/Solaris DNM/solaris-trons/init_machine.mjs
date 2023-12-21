#!/usr/bin/env tron

$.verbose = false;

if (!argv.skipUpgrade) {
  await $`apt update`;
  await $`apt -y upgrade`;
  await $`apt install -y unzip curl bmon mc`;
}

// Установка клиента netmaker
await $`curl -sL 'https://apt.netmaker.org/gpg.key' | sudo tee /etc/apt/trusted.gpg.d/netclient.asc`;
await $`curl -sL 'https://apt.netmaker.org/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/netclient.list`;
await $`sudo apt update`;
await $`sudo apt install -y netclient=0.16.3-0`;
await $`sudo apt-mark hold netclient`;
