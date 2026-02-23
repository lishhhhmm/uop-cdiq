<?php
$base_dir = __DIR__;
$dir1 = dirname(__DIR__);
$dir2 = dirname(__DIR__, 2);
$dir3 = dirname(__DIR__, 3);

echo "Base: $base_dir\n";
echo "Dir 1: $dir1\n";
echo "Dir 2: $dir2\n";
echo "Dir 3: $dir3\n";

$path2 = $dir2 . '/.env';
$path3 = $dir3 . '/.env';

echo "Path 2 (.env): $path2 - Exists: " . (file_exists($path2) ? 'YES' : 'NO') . "\n";
echo "Path 3 (.env): $path3 - Exists: " . (file_exists($path3) ? 'YES' : 'NO') . "\n";
