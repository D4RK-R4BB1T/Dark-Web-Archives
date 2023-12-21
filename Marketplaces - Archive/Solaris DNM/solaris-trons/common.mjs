export const SOLARIS_PROJECTS_PATH = '/share/app/projects';

export const getBtcUsdPrice = async () => {
  const response = await fetch('http://63.250.36.215:8888/prices?coin=btc');
  const data = await response.json();
  return data.btc.usd;
}

export const roundToFixed2 = (val) => Math.round(val * 1e2) / 1e2;

export const floorToFixed3 = (val) => Math.floor(val * 1e3) / 1e3;

export const runInternalScript = async (scriptName, noExit = true, ...args) => {
  const result = await $`${path.join(__dirname, scriptName)} ${args.join(' ')}`.nothrow();
  if (result.exitCode === 0) {
    return YAML.parse(result.stdout.trim());
  } else {
    if (noExit) {
      return null;
    } else {
      process.exit(1);
    }
  }
};

export const getLANIp = async () => (await $`ifconfig nm-solaris | grep "inet " | awk '{print $2}'`).stdout.trim();

export const getShops = async ({exitOnFailure = true, noArgv = false } = {}) => {
  // Получаем список шопов на хосте.
  if (!noArgv && argv._.length > 0) {
    return argv._;
  }

  let shops = await runInternalScript("list_shops.mjs");
  if (!shops) {
    if (exitOnFailure) {
      process.exit(1);
    } else {
      shops = [];
    }
  }
  return shops;
}