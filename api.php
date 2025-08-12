<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

// Отговаряме на OPTIONS заявки
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

// Клас за управление на демо файлове
class DemoManager {
    private $servers;
    
    public function __construct($servers) {
        $this->servers = $servers;
    }
    
    public function getServerFiles($serverId) {
        if (!array_key_exists($serverId, $this->servers)) {
            return ['error' => 'Неvalidен сървър'];
        }
        
        $server = $this->servers[$serverId];
        $files = $this->scanDemoDirectory($server['path'], $serverId);
        
        return [
            'server' => $server['name'],
            'serverId' => $serverId,
            'count' => count($files),
            'files' => $files
        ];
    }
    
    public function getAllServers() {
        $result = [];
        foreach ($this->servers as $serverId => $server) {
            $files = $this->scanDemoDirectory($server['path'], $serverId);
            $result[] = [
                'id' => $serverId,
                'name' => $server['name'],
                'count' => count($files)
            ];
        }
        return $result;
    }
    
    private function scanDemoDirectory($path, $serverId) {
        $files = [];
        
        if (!is_dir($path)) {
            return $files;
        }
        
        try {
            $entries = scandir($path);
            
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                
                $fullPath = $path . $entry;
                
                // Филтрираме само .dem файлове
                if (is_file($fullPath) && $this->isDemoFile($entry)) {
                    $stat = stat($fullPath);
                    
                    $files[] = [
                        'name' => $entry,
                        'size' => $stat['size'],
                        'sizeFormatted' => $this->formatFileSize($stat['size']),
                        'mtime' => $stat['mtime'],
                        'mtimeFormatted' => $this->formatDate($stat['mtime']),
                        'url' => "/{$serverId}/" . urlencode($entry),
                        'downloadUrl' => "/{$serverId}/" . urlencode($entry),
                        'fullUrl' => getBaseUrl() . "/{$serverId}/" . urlencode($entry)
                    ];
                }
            }
            
            // Сортираме по дата (най-новите първи)
            usort($files, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });
            
        } catch (Exception $e) {
            error_log("Грешка при четене на директория {$path}: " . $e->getMessage());
        }
        
        return $files;
    }
    
    private function isDemoFile($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $extension === 'dem';
    }
    
    private function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.1f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
    }
    
    private function formatDate($timestamp) {
        return date('d.m.Y H:i', $timestamp);
    }
}

// Обработка на заявките
try {
    $demoManager = new DemoManager($servers);
    
    $action = $_GET['action'] ?? 'getFiles';
    $serverId = $_GET['server'] ?? 'zescape';
    
    switch ($action) {
        case 'getFiles':
            $response = $demoManager->getServerFiles($serverId);
            break;
            
        case 'getServers':
            $response = [
                'servers' => $demoManager->getAllServers()
            ];
            break;
            
        case 'getStats':
            $stats = [];
            foreach ($servers as $sId => $server) {
                $files = $demoManager->getServerFiles($sId);
                $stats[] = [
                    'serverId' => $sId,
                    'serverName' => $server['name'],
                    'fileCount' => $files['count'],
                    'lastModified' => !empty($files['files']) ? $files['files'][0]['mtimeFormatted'] : 'Няма файлове'
                ];
            }
            $response = ['stats' => $stats];
            break;
            
        default:
            $response = ['error' => 'Неvalidно действие'];
            break;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Сървърна грешка',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>