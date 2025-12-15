<?php
echo "Testando SQLite...<br>";

if (extension_loaded('pdo_sqlite')) {
    echo "✅ PDO SQLite está carregado!<br>";
} else {
    echo "❌ PDO SQLite NÃO está carregado!<br>";
}

if (class_exists('PDO')) {
    echo "✅ PDO existe!<br>";
    $drivers = PDO::getAvailableDrivers();
    echo "Drivers disponíveis: " . implode(', ', $drivers);
} else {
    echo "❌ PDO não existe!";
}
?>