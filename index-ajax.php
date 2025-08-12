<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS Source Demos Archive</title>
    <link rel="Shortcut Icon" href="favicon.ico" />
    <link href="style/style.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://kit.fontawesome.com/f29912deb4.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class='header'>
        <a href="index.php" title="CS Source Demo Archive">
            <img src="img/demos_archive_2024.png" alt="CS Source Demos" style="max-width: 100%; height: auto;">
        </a>
    </div>
    
    <div class='navbar'>
        <div class='item'>
            <a href='index.php' title="Home"><i class="fa fa-home mr-1" aria-hidden="true"></i> Начало</a>
        </div>
        <div class='item'>
            <a href='#' onclick="refreshCurrentServer()" title="Refresh"><i class="fas fa-sync fa-lg" aria-hidden="true"></i> Обнови</a>
        </div>
        <div class='item'>
            <a href='#' onclick="showStats()" title="Statistics"><i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Статистики</a>
        </div>
        <div class='item'>
            <a href='#' onclick="showInfo()" title="Info"><i class="fa-solid fa-info-circle" aria-hidden="true"></i> Информация</a>
        </div>
    </div>
    
    <div class='subbar' id="serverTabs">
        <!-- Серверите ще се заредят динамично -->
    </div>

    <div class="ipsWidget_inner" style="padding-bottom: 0px;">
        <p class="ipsType_reset ipsType_medium ipsType_light"></p><br>
        <div class="ipsMessage ipsMessage_info ipsMargin_bottom" style="font-weight: bold;">
            <span><i class="fa-solid fa-circle-exclamation"></i> Демо файловете се запазват за ограничено време.</span>
        </div>
        <p></p>
    </div>

    <center>
        <div class='server' id='server'>
            <i class="fas fa-spinner fa-spin"></i> Зареждане на сървъри...
        </div>
    </center>

    <footer class="footer">
        <br>
        <div class="layout_container flex flex-jc:space-between flex-ai:center" style="padding-top:0px">
            <div class="flex flex-fd:column text:left" style='text-align:left'>
                <a href="index.php" title="CS Source Demos Archive">CS Source Demos Archive</a>
                <span><i class="fas fa-code"></i> PHP AJAX версия базирана на дизайна на <a href="https://demos.nide.gg/" target="_blank" rel="noopener" title="NiDE.GG">NiDE.GG</a></span>
                <span>Version: 2.1.0 PHP AJAX</span>
            </div>
            <div class="flex flex-fd:column text:right" style='text-align:right'>
                <span>Counter-Strike Source Demo Archive</span>
                <span><i class="fas fa-server"></i> Powered by PHP + Nginx + AJAX</span>
            </div>
        </div>
    </footer>

    <script>
        let currentServer = 'zescape';
        let serversData = {};
        
        // Зареждане при стартиране на страницата
        $(document).ready(function() {
            loadServers();
        });
        
        // Зареждане на списъка със сървъри
        function loadServers() {
            $.ajax({
                url: 'api.php',
                method: 'GET',
                data: { action: 'getServers' },
                dataType: 'json',
                success: function(response) {
                    if (response.servers) {
                        serversData = response.servers;
                        renderServerTabs();
                        loadServerFiles(currentServer);
                    } else {
                        showError('Грешка при зареждане на сървърите');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX грешка:', error);
                    showError('Грешка при връзката със сървъра');
                }
            });
        }
        
        // Показване на табовете със сървъри
        function renderServerTabs() {
            let html = '';
            serversData.forEach(function(server) {
                const isActive = server.id === currentServer ? 'active' : '';
                html += `<div class='item changeServ ${isActive}' onclick="switchServer('${server.id}')">
                    ${server.name} (${server.count})
                </div>`;
            });
            $('#serverTabs').html(html);
        }
        
        // Превключване на сървър
        function switchServer(serverId) {
            if (serverId === currentServer) return;
            
            currentServer = serverId;
            $('.changeServ').removeClass('active');
            $(`.changeServ`).each(function() {
                if ($(this).attr('onclick').includes(serverId)) {
                    $(this).addClass('active');
                }
            });
            
            loadServerFiles(serverId);
        }
        
        // Зареждане на файлове за конкретен сървър
        function loadServerFiles(serverId) {
            $('#server').html('<i class="fas fa-spinner fa-spin"></i> Зареждане...');
            
            $.ajax({
                url: 'api.php',
                method: 'GET',
                data: { 
                    action: 'getFiles',
                    server: serverId 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        showError(response.error);
                    } else {
                        renderDemoFiles(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX грешка:', error);
                    showError('Грешка при зареждане на файловете');
                }
            });
        }
        
        // Показване на демо файловете
        function renderDemoFiles(data) {
            const container = $('#server');
            
            if (!data.files || data.files.length === 0) {
                container.html(`<span style='font-size:20px'>Няма налични демо файлове за ${data.server}</span>`);
                return;
            }
            
            let html = `
                <div style="margin-bottom: 15px;">
                    <strong>${data.server}</strong> - 
                    <span>${data.count} файла</span>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: #1a1a1a; font-weight: bold;">
                        <td style="padding: 10px; text-align: left;">Име на файла</td>
                        <td style="padding: 10px; text-align: center;">Размер</td>
                        <td style="padding: 10px; text-align: center;">Дата</td>
                        <td style="padding: 10px; text-align: center;">Действие</td>
                    </tr>`;
            
            data.files.forEach(function(demo) {
                html += `
                    <tr>
                        <td style="padding: 8px; text-align: left; word-break: break-all;">
                            ${escapeHtml(demo.name)}
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            ${demo.sizeFormatted}
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            ${demo.mtimeFormatted}
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            <a href="${demo.downloadUrl}" class="button" download>
                                <i class="fas fa-download"></i> Изтегли
                            </a>
                        </td>
                    </tr>`;
            });
            
            html += '</table>';
            container.html(html);
        }
        
        // Обновяване на текущия сървър
        function refreshCurrentServer() {
            loadServers(); // Презареждаме всичко
        }
        
        // Показване на статистики
        function showStats() {
            $('#server').html('<i class="fas fa-spinner fa-spin"></i> Зареждане на статистики...');
            
            $.ajax({
                url: 'api.php',
                method: 'GET',
                data: { action: 'getStats' },
                dataType: 'json',
                success: function(response) {
                    if (response.stats) {
                        renderStats(response.stats);
                    } else {
                        showError('Грешка при зареждане на статистиките');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX грешка:', error);
                    showError('Грешка при зареждане на статистиките');
                }
            });
        }
        
        // Показване на статистиките
        function renderStats(stats) {
            let html = `
                <div style="margin-bottom: 15px;">
                    <strong>Статистики на сървърите</strong>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: #1a1a1a; font-weight: bold;">
                        <td style="padding: 10px; text-align: left;">Сървър</td>
                        <td style="padding: 10px; text-align: center;">Файлове</td>
                        <td style="padding: 10px; text-align: center;">Последна активност</td>
                    </tr>`;
            
            stats.forEach(function(stat) {
                html += `
                    <tr onclick="switchServer('${stat.serverId}')" style="cursor: pointer;">
                        <td style="padding: 8px; text-align: left;">
                            ${escapeHtml(stat.serverName)}
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            ${stat.fileCount}
                        </td>
                        <td style="padding: 8px; text-align: center;">
                            ${stat.lastModified}
                        </td>
                    </tr>`;
            });
            
            html += '</table>';
            html += '<br><button class="button" onclick="loadServerFiles(currentServer)">Назад към файловете</button>';
            
            $('#server').html(html);
        }
        
        // Показване на информация
        function showInfo() {
            alert(`CS Source Demo Archive\n\nДинамичен уеб интерфейс за преглеждане на демо файлове.\n\nФункции:\n• Реално време зареждане\n• Статистики на сървърите\n• Подробна информация за файловете\n• Търсене и филтриране\n\nВерсия: 2.1.0 PHP AJAX`);
        }
        
        // Показване на грешка
        function showError(message) {
            $('#server').html(`<span style="color: #ff4700; font-size: 18px;">${escapeHtml(message)}</span>`);
        }
        
        // Escape HTML за сигурност
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>