<?php
require_once __DIR__ . '/bootstrap.php';
if (PHP_SAPI !== 'cli' && (string)getenv('CEPIN_MIGRATION_KEY') === '') { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=UTF-8');
$pdo=db();
$pdo->exec("CREATE TABLE IF NOT EXISTS cepin_migrations (id VARCHAR(120) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach(glob(__DIR__.'/migrations/*.php') as $file){$id=basename($file);$s=$pdo->prepare('SELECT 1 FROM cepin_migrations WHERE id=?');$s->execute([$id]);if($s->fetchColumn())continue;$fn=require $file;$pdo->beginTransaction();try{$fn($pdo);$i=$pdo->prepare('INSERT INTO cepin_migrations(id) VALUES(?)');$i->execute([$id]);$pdo->commit();echo "OK: {$id}\n";}catch(Throwable $e){$pdo->rollBack();http_response_code(500);echo "ERRO: {$id} - {$e->getMessage()}\n";exit;}}
// Compatibilidade com a tabela projects criada anteriormente.
$cols=$pdo->query('SHOW COLUMNS FROM projects')->fetchAll();$names=array_column($cols,'Field');if(!in_array('tags_json',$names,true))$pdo->exec('ALTER TABLE projects ADD COLUMN tags_json TEXT NULL AFTER participation_info');$pdo->exec('ALTER TABLE projects MODIFY owner_user_id INT NULL');
echo "Banco CEPIN-CIS pronto.\n";
