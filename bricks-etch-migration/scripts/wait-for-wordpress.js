#!/usr/bin/env node

const http = require('http');

function waitForWordPress({ port, maxAttempts = 60, interval = 2000 }) {
  let attempt = 0;

  return new Promise((resolve, reject) => {
    const check = () => {
      attempt += 1;
      const options = {
        host: 'localhost',
        port,
        path: '/wp-admin/'
      };

      const req = http.request(options, (res) => {
        const { statusCode } = res;
        res.resume();

        if (statusCode === 200 || statusCode === 302) {
          console.log(`✓ WordPress ready on port ${port}`);
          resolve();
        } else {
          retry(`Received status ${statusCode}`);
        }
      });

      req.on('error', (err) => {
        retry(err.message);
      });

      req.end();
    };

    const retry = (reason) => {
      if (attempt >= maxAttempts) {
        reject(new Error(`WordPress on port ${port} not ready after ${maxAttempts} attempts. Last error: ${reason}`));
        return;
      }

      console.log(`Waiting for WordPress on port ${port}... (attempt ${attempt}/${maxAttempts})`);
      setTimeout(check, interval);
    };

    check();
  });
}

module.exports = waitForWordPress;
