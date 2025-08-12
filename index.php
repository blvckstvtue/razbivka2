<?php
// Конфигурация на сървърите
$servers = [
    'zescape' => [
        'name' => 'CS:S ZEscape Server',
        'path' => '/home/martink1337/hub/cssource/zescape/cstrike/demos/'
    ],
    'csgomod' => [
        'name' => 'CS:S CSGOMod Server', 
        'path' => '/home/martink1337/hub/cssource/csgomod/cstrike/demos/'
    ]
];

// Функция за определяне на базовия URL (за работа с reverse proxy)
function getBaseUrl() {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || $_SERVER['SERVER_PORT'] == 443
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
    
    // Проверяваме за reverse proxy prefix
    $prefix = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';
    
    return $protocol . '://' . $host . $prefix;
}

// Получаваме избрания сървър от URL параметър
$selectedServer = $_GET['server'] ?? 'zescape';
if (!array_key_exists($selectedServer, $servers)) {
    $selectedServer = 'zescape';
}

// Функция за получаване на демо файлове
function getDemoFiles($path) {
    $files = [];
    
    if (!is_dir($path)) {
        return $files;
    }
    
    $entries = scandir($path);
    
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        
        $fullPath = $path . $entry;
        
        // Филтрираме само .dem файлове
        if (is_file($fullPath) && strtolower(pathinfo($entry, PATHINFO_EXTENSION)) === 'dem') {
            $stat = stat($fullPath);
            
            $files[] = [
                'name' => $entry,
                'size' => $stat['size'],
                'mtime' => $stat['mtime'],
                'url' => "/{$GLOBALS['selectedServer']}/" . urlencode($entry),
                'fullUrl' => getBaseUrl() . "/{$GLOBALS['selectedServer']}/" . urlencode($entry)
            ];
        }
    }
    
    // Сортираме по дата (най-новите първи)
    usort($files, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
    
    return $files;
}

// Функция за форматиране на размера на файла
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    
    return sprintf("%.1f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
}

// Функция за форматиране на датата
function formatDate($timestamp) {
    return date('d.m.Y H:i', $timestamp);
}

// Получаваме файловете за избрания сървър
$demoFiles = getDemoFiles($servers[$selectedServer]['path']);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Zane Demos Archive</title>
    <link rel="Shortcut Icon" href="favicon.ico" />
    <link href="style/style.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://kit.fontawesome.com/f29912deb4.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class='header'>
        <a href="index.php" title="Project Zane Demos Archive">
            <img src="img/demos_archive_2024.png" alt="Demos Archive" style="max-width: 100%; height: auto;">
        </a>
    </div>
    
    <div class='navbar'>
        <div class='item'>
            <a href='https://zane.zone.id/' title="Home"><i class="fa fa-home mr-1" aria-hidden="true"></i> Back to Zane Site</a>
        </div>
        <div class='item'>
            <a href='javascript:location.reload()' title="Refresh"><i class="fas fa-sync fa-lg" aria-hidden="true"></i> Refresh</a>
        </div>
        <div class='item'>
            <a href='#' onclick="showInfo()" title="Info"><i class="fa-solid fa-info-circle" aria-hidden="true"></i> Information</a>
        </div>
    </div>
    
    <div class='subbar'>
        <?php foreach ($servers as $serverId => $serverInfo): ?>
            <div class='item changeServ <?= $serverId === $selectedServer ? 'active' : '' ?>' 
                 onclick="window.location.href='index.php?server=<?= $serverId ?>'">
                <?= htmlspecialchars($serverInfo['name']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="ipsWidget_inner" style="padding-bottom: 0px;">
        <p class="ipsType_reset ipsType_medium ipsType_light"></p><br>
        <div class="ipsMessage ipsMessage_info ipsMargin_bottom" style="font-weight: bold;">
            <span><i class="fa-solid fa-circle-exclamation"></i> The demo files are saved for a limited time.</span>
        </div>
        <p></p>
    </div>

    <center>
        <div class='server' id='server'>
            <?php if (empty($demoFiles)): ?>
                <span style='font-size:20px'>No demo files available for <?= htmlspecialchars($servers[$selectedServer]['name']) ?></span>
            <?php else: ?>
                <div style="margin-bottom: 15px;">
                    <strong><?= htmlspecialchars($servers[$selectedServer]['name']) ?></strong> - 
                    <span><?= count($demoFiles) ?> files</span>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: #1a1a1a; font-weight: bold;">
                        <td style="padding: 10px; text-align: left;">Name</td>
                        <td style="padding: 10px; text-align: center;">Size</td>
                        <td style="padding: 10px; text-align: center;">Date</td>
                        <td style="padding: 10px; text-align: center;">Action</td>
                    </tr>
                    
                    <?php foreach ($demoFiles as $demo): ?>
                        <tr>
                            <td style="padding: 8px; text-align: left; word-break: break-all;">
                                <?= htmlspecialchars($demo['name']) ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <?= formatFileSize($demo['size']) ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <?= formatDate($demo['mtime']) ?>
                            </td>
                            <td style="padding: 8px; text-align: center;">
                                <a href="<?= htmlspecialchars($demo['url']) ?>" class="button" download>
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </center>

    <footer class="footer">
        <br>
        <div class="layout_container flex flex-jc:space-between flex-ai:center" style="padding-top:0px">
            <div class="flex flex-fd:column text:left" style='text-align:left'>
                <a href="index.php" title="CS Source Demos Archive">Project Zane Demos Archive</a>
                <span><i class="fas fa-code"></i> Custom PHP version made by <a href="#" target="_blank" rel="noopener" title="TRASHLORD">TRASHLORD</a></span>
                <span>Version: 2.0.0 PHP</span>
            </div>
            <div class="flex flex-fd:column text:right" style='text-align:right'>
                <span>Project Zane Demos Archive</span>
                <span><i class="fas fa-server"></i> Powered by PHP + Nginx</span>
            </div>
        </div>
    </footer>

    <script>
        function showInfo() {
            alert('Project Zane Demos Archive\n\nDisplays demo files from the servers.\nUse the tabs above to switch between servers.\n\nVersion: 2.0.0 PHP');
        }
        
        // Добавяме ефект за зареждане при смяна на сървър
        $('.changeServ').click(function() {
            if (!$(this).hasClass('active')) {
                $('#server').html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            }
        });
    </script>
</body>
</html>