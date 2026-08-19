<?php

header('Content-Type: text/plain; charset=utf-8');

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "PDO instalado: " . (class_exists('PDO') ? 'SIM' : 'NAO') . PHP_EOL;
echo "pdo_mysql carregado: " . (extension_loaded('pdo_mysql') ? 'SIM' : 'NAO') . PHP_EOL;
echo PHP_EOL;

echo "Drivers PDO disponíveis:" . PHP_EOL;
print_r(PDO::getAvailableDrivers());