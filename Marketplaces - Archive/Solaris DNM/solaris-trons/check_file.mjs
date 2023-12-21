#!/usr/bin/env tron

$.verbose = false;

try {
  const st = await fs.stat(argv._[0]);

  console.log(YAML.stringify({
    exists: true,
    isDirectory: st.isDirectory(),
    isFile: st.isFile(),
    isSymbolicLink: st.isSymbolicLink(),
    isSocket: st.isSocket(),
    mode: st.mode
  }));
} catch (ex) {
  console.log(YAML.stringify({
    exists: false,
    error: ex
  }))
  process.exit(1);
}
