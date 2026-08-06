<?php
$files = [
    __DIR__ . '/../pengurus_fakultas/dashboard.php',
    __DIR__ . '/../mahasiswa/dashboard.php',
    __DIR__ . '/../dekan/dashboard.php',
    __DIR__ . '/../kaprodi/dashboard.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Match the <div class="relative w-full md:w-auto group"> block containing the search input
        // Using a more robust regex to capture the entire div accurately without relying on greedy matches failing
        $pattern = '/<div class="relative w-full md:w-auto group">\s*<div class="absolute inset-y-0 left-0 pl-3\.5 flex items-center pointer-events-none">\s*<i data-lucide="search"[^>]*><\/i>\s*<\/div>\s*<input type="text" id="[^"]+" onkeyup="filterTable[^"]+"\s*class="[^"]+" \s*placeholder="Cari[^"]+">\s*<\/div>/s';
        
        $new_content = preg_replace($pattern, '', $content);
        
        if ($new_content !== $content) {
            file_put_contents($file, $new_content);
            echo "Updated " . basename(dirname($file)) . "/" . basename($file) . " - Removed search inputs.\n";
        } else {
            echo "No changes needed for " . basename(dirname($file)) . "/" . basename($file) . "\n";
        }
    }
}
?>
