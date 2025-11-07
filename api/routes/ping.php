<?php
require __DIR__.'/../lib/http.php';
cors();
json(['ok' => true, 'pong' => time()]);
