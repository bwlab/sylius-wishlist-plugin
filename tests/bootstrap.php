<?php

declare(strict_types=1);

// Standalone checkout uses its own vendor; inside a shop (plugins/<dir>) falls back to the shop autoloader.
$loader = require (is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor/autoload.php' : __DIR__ . '/../../../vendor/autoload.php');
// Always test THIS checkout's code (git worktrees share the shop autoloader, which points to the main checkout).
$loader->addPsr4('Bwlab\\SyliusWishlistPlugin\\', __DIR__ . '/../src/', true);
$loader->addPsr4('Bwlab\\SyliusWishlistPlugin\\Tests\\', __DIR__ . '/', true);
