# Shift Happens

A Laravel app you drive with a real gear shifter.

Built for a YouTube video: [link coming soon.](https://youtu.be/prN6b-KhfQU)

Second gear runs the test suite, third runs PHPStan, fourth lets Claude Code
fix whatever PHPStan found, and fifth gear deploys to production on Laravel
Cloud if you hold it for two full seconds. Reverse breaks the demo code again
so the whole loop can run one more time.

The browser reads the shifter with the Gamepad API and posts gear changes to
Laravel. Each gear maps to a queued job in `config/gears.php`, jobs run their
command through the Process facade and stream output into the cache, and the
dashboard polls it back.

## Running it

```bash
composer install
npm install
composer run dev
```

PS: You will need a USB shifter (mine is a
Thrustmaster TH8S) and, for gear 5, the
[Laravel Cloud CLI](https://laravel.com/cloud/docs/api/cli).
