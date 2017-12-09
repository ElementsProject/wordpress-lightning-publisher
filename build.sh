#!/bin/sh
composer install --no-dev
zip -r wordpress-lightning-paywall.zip composer.json lightning-paywall.php vendor css js --exclude='vendor/bacon/bacon-qr-code/tests/*'

