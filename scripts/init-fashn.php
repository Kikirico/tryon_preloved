<?php
$path = __DIR__.'/../.runtime/fashn-service.key';
if (!is_file($path)) file_put_contents($path, bin2hex(random_bytes(32)));
echo "Local FASHN service key ready (not displayed).\n";
