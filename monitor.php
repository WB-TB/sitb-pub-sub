<?php
/**
 * Realtime Application Monitor for Linux Ubuntu 24.04
 * Monitors CPU and RAM usage for specific applications
 */

// Configuration
$config = [
    'refresh_interval' => 2, // seconds
    'applications' => [
        'php',
        // 'node',
        // 'nginx',
        // 'mysql',
        // 'postgres',
        // 'redis',
        // 'apache2',
    ],
    'display_mode' => 'cli', // 'cli' or 'html'
];

// Parse command line arguments
if (php_sapi_name() === 'cli') {
    $options = getopt('a:i:h', ['app:', 'interval:', 'help']);
    
    if (isset($options['h']) || isset($options['help'])) {
        displayHelp();
        exit(0);
    }
    
    if (isset($options['a']) || isset($options['app'])) {
        $apps = isset($options['a']) ? explode(',', $options['a']) : explode(',', $options['app']);
        $config['applications'] = array_map('trim', $apps);
    }
    
    if (isset($options['i']) || isset($options['interval'])) {
        $config['refresh_interval'] = isset($options['i']) ? intval($options['i']) : intval($options['interval']);
    }
}

// HTML mode detection
if (isset($_GET['app'])) {
    $config['applications'] = explode(',', $_GET['app']);
    $config['display_mode'] = 'html';
}

if (isset($_GET['interval'])) {
    $config['refresh_interval'] = intval($_GET['interval']);
}

/**
 * Get process information for a specific application
 */
function getProcessInfo($appName) {
    $info = [
        'name' => $appName,
        'pid' => [],
        'cpu_percent' => 0,
        'ram_mb' => 0,
        'ram_percent' => 0,
        'count' => 0,
        'threads' => 0,
    ];
    
    // Get PIDs for the application
    $pids = [];
    exec("pgrep -f '{$appName}' 2>/dev/null", $pids);
    
    if (empty($pids)) {
        return $info;
    }
    
    $info['pid'] = $pids;
    $info['count'] = count($pids);
    
    // Get total system memory
    $totalMem = getTotalSystemMemory();
    
    // Get detailed process information
    $totalCpu = 0;
    $totalRam = 0;
    $totalThreads = 0;
    
    foreach ($pids as $pid) {
        $processData = getProcessDetails($pid);
        if ($processData) {
            $totalCpu += $processData['cpu'];
            $totalRam += $processData['ram'];
            $totalThreads += $processData['threads'];
        }
    }
    
    $info['cpu_percent'] = round($totalCpu, 2);
    $info['ram_mb'] = round($totalRam / 1024 / 1024, 2);
    $info['ram_percent'] = $totalMem > 0 ? round(($totalRam / $totalMem) * 100, 2) : 0;
    $info['threads'] = $totalThreads;
    
    return $info;
}

/**
 * Get detailed process information for a specific PID
 */
function getProcessDetails($pid) {
    // Read /proc/[pid]/stat for CPU usage
    $statFile = "/proc/{$pid}/stat";
    if (!file_exists($statFile)) {
        return null;
    }
    
    $statContent = file_get_contents($statFile);
    $statParts = explode(' ', $statContent);
    
    // Get utime and stime (user and kernel time)
    if (count($statParts) < 17) {
        return null;
    }
    
    $utime = $statParts[13];
    $stime = $statParts[14];
    
    // Get total CPU time from /proc/stat
    $statContent = file_get_contents('/proc/stat');
    $lines = explode("\n", $statContent);
    $cpuLine = explode(' ', $lines[0]);
    $totalCpuTime = array_sum(array_slice($cpuLine, 1, 7));
    
    // Calculate CPU percentage (simplified)
    $processCpuTime = $utime + $stime;
    $cpuPercent = ($processCpuTime / $totalCpuTime) * 100;
    
    // Get memory usage from /proc/[pid]/status
    $statusFile = "/proc/{$pid}/status";
    if (!file_exists($statusFile)) {
        return null;
    }
    
    $statusContent = file_get_contents($statusFile);
    $ram = 0;
    $threads = 0;
    
    if (preg_match('/VmRSS:\s+(\d+)\s+kB/', $statusContent, $matches)) {
        $ram = intval($matches[1]) * 1024; // Convert to bytes
    }
    
    if (preg_match('/Threads:\s+(\d+)/', $statusContent, $matches)) {
        $threads = intval($matches[1]);
    }
    
    return [
        'cpu' => $cpuPercent,
        'ram' => $ram,
        'threads' => $threads,
    ];
}

/**
 * Get total system memory in bytes
 */
function getTotalSystemMemory() {
    $meminfo = file_get_contents('/proc/meminfo');
    if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
        return intval($matches[1]) * 1024; // Convert to bytes
    }
    return 0;
}

/**
 * Get system-wide CPU and memory usage
 */
function getSystemStats() {
    $stats = [
        'cpu_total' => 0,
        'cpu_user' => 0,
        'cpu_system' => 0,
        'cpu_idle' => 0,
        'mem_total' => 0,
        'mem_used' => 0,
        'mem_free' => 0,
        'mem_available' => 0,
        'mem_percent' => 0,
    ];
    
    // Get CPU stats from /proc/stat
    $statContent = file_get_contents('/proc/stat');
    $lines = explode("\n", $statContent);
    $cpuLine = explode(' ', $lines[0]);
    
    if (count($cpuLine) >= 8) {
        $user = intval($cpuLine[1]);
        $nice = intval($cpuLine[2]);
        $system = intval($cpuLine[3]);
        $idle = intval($cpuLine[4]);
        $iowait = intval($cpuLine[5]);
        $irq = intval($cpuLine[6]);
        $softirq = intval($cpuLine[7]);
        
        $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq;
        
        $stats['cpu_total'] = $total;
        $stats['cpu_user'] = $user;
        $stats['cpu_system'] = $system;
        $stats['cpu_idle'] = $idle;
    }
    
    // Get memory stats from /proc/meminfo
    $meminfo = file_get_contents('/proc/meminfo');
    
    if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
        $stats['mem_total'] = intval($matches[1]) * 1024;
    }
    
    if (preg_match('/MemFree:\s+(\d+)\s+kB/', $meminfo, $matches)) {
        $stats['mem_free'] = intval($matches[1]) * 1024;
    }
    
    if (preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $matches)) {
        $stats['mem_available'] = intval($matches[1]) * 1024;
    }
    
    $stats['mem_used'] = $stats['mem_total'] - $stats['mem_available'];
    $stats['mem_percent'] = $stats['mem_total'] > 0 
        ? round(($stats['mem_used'] / $stats['mem_total']) * 100, 2) 
        : 0;
    
    return $stats;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Display CLI output
 */
function displayCli($config) {
    // Clear screen
    system('clear');
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         REALTIME APPLICATION MONITOR - UBUNTU 24.04         ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    // System stats
    $systemStats = getSystemStats();
    
    echo "┌─ SYSTEM STATISTICS ──────────────────────────────────────────┐\n";
    echo "│ CPU Usage:                                                   │\n";
    echo "│   User:   " . str_pad(round(($systemStats['cpu_user'] / $systemStats['cpu_total']) * 100, 2) . "%", 51) . "│\n";
    echo "│   System: " . str_pad(round(($systemStats['cpu_system'] / $systemStats['cpu_total']) * 100, 2) . "%", 51) . "│\n";
    echo "│   Idle:   " . str_pad(round(($systemStats['cpu_idle'] / $systemStats['cpu_total']) * 100, 2) . "%", 51) . "│\n";
    echo "│                                                              │\n";
    echo "│ Memory Usage:                                                │\n";
    echo "│   Used:     " . str_pad(formatBytes($systemStats['mem_used']), 20) . str_pad($systemStats['mem_percent'] . "%", 29) . "│\n";
    echo "│   Total:    " . str_pad(formatBytes($systemStats['mem_total']), 49) . "│\n";
    echo "└──────────────────────────────────────────────────────────────┘\n\n";
    
    // Application stats
    echo "┌─ APPLICATION MONITORING ───────────────────────────────────────────┐\n";
    printf("│ %-15s │ %-8s │ %-12s │ %-10s │ %-8s │\n", 'APP NAME', 'PROCESSES', 'CPU (%)', 'RAM (MB)', 'RAM (%)');
    echo "├─────────────────┼───────────┼──────────────┼────────────┼──────────┤\n";
    
    foreach ($config['applications'] as $app) {
        $info = getProcessInfo($app);
        printf("│ %-15s │ %-9d │ %-12s │ %-10s │ %-8s │\n",
            $info['name'],
            $info['count'],
            $info['cpu_percent'],
            $info['ram_mb'],
            $info['ram_percent']
        );
    }
    
    echo "└─────────────────┴───────────┴──────────────┴────────────┴──────────┘\n\n";
    
    echo "Press Ctrl+C to exit. Refreshing every {$config['refresh_interval']} seconds...\n";
    echo "Last updated: " . date('Y-m-d H:i:s') . "\n";
}

/**
 * Display HTML output
 */
function displayHtml($config) {
    $systemStats = getSystemStats();
    $appsData = [];
    
    foreach ($config['applications'] as $app) {
        $appsData[] = getProcessInfo($app);
    }
    
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realtime Application Monitor - Ubuntu 24.04</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #1a1a2e; color: #eee; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        h1 { text-align: center; margin-bottom: 30px; color: #4ecca3; }
        .card { background: #16213e; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .card h2 { margin-bottom: 15px; color: #4ecca3; border-bottom: 2px solid #4ecca3; padding-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-box { background: #0f3460; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-box .label { font-size: 0.9em; color: #aaa; margin-bottom: 5px; }
        .stat-box .value { font-size: 1.5em; font-weight: bold; color: #4ecca3; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #0f3460; }
        th { background: #0f3460; color: #4ecca3; }
        tr:hover { background: #1a1a2e; }
        .progress-bar { width: 100%; height: 20px; background: #0f3460; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4ecca3, #45b792); transition: width 0.3s; }
        .progress-fill.high { background: linear-gradient(90deg, #e94560, #c73e54); }
        .progress-fill.medium { background: linear-gradient(90deg, #f39c12, #e67e22); }
        .last-updated { text-align: right; color: #aaa; font-size: 0.9em; margin-top: 10px; }
        .controls { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .controls input, .controls button { padding: 10px; border: none; border-radius: 5px; }
        .controls input { background: #0f3460; color: #eee; flex: 1; min-width: 200px; }
        .controls button { background: #4ecca3; color: #1a1a2e; cursor: pointer; font-weight: bold; }
        .controls button:hover { background: #45b792; }
        .chart-container { height: 300px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖥️ Realtime Application Monitor - Ubuntu 24.04</h1>
        
        <div class="controls">
            <input type="text" id="appInput" placeholder="Applications (comma-separated, e.g., php,node,nginx)" value="' . implode(',', $config['applications']) . '">
            <input type="number" id="intervalInput" placeholder="Refresh interval (seconds)" value="' . $config['refresh_interval'] . '" min="1" max="60">
            <button onclick="updateConfig()">Update</button>
        </div>
        
        <div class="card">
            <h2>📊 System Statistics</h2>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="label">CPU Usage</div>
                    <div class="value">' . round((($systemStats['cpu_user'] + $systemStats['cpu_system']) / $systemStats['cpu_total']) * 100, 2) . '%</div>
                </div>
                <div class="stat-box">
                    <div class="label">Memory Usage</div>
                    <div class="value">' . $systemStats['mem_percent'] . '%</div>
                </div>
                <div class="stat-box">
                    <div class="label">Memory Used</div>
                    <div class="value">' . formatBytes($systemStats['mem_used']) . '</div>
                </div>
                <div class="stat-box">
                    <div class="label">Memory Total</div>
                    <div class="value">' . formatBytes($systemStats['mem_total']) . '</div>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <div style="margin-bottom: 5px;">CPU Usage Progress</div>
                <div class="progress-bar">
                    <div class="progress-fill ' . getClassForPercent((($systemStats['cpu_user'] + $systemStats['cpu_system']) / $systemStats['cpu_total']) * 100) . '" style="width: ' . round((($systemStats['cpu_user'] + $systemStats['cpu_system']) / $systemStats['cpu_total']) * 100, 2) . '%"></div>
                </div>
            </div>
            
            <div>
                <div style="margin-bottom: 5px;">Memory Usage Progress</div>
                <div class="progress-bar">
                    <div class="progress-fill ' . getClassForPercent($systemStats['mem_percent']) . '" style="width: ' . $systemStats['mem_percent'] . '%"></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>🚀 Application Monitoring</h2>
            <table>
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Processes</th>
                        <th>CPU Usage (%)</th>
                        <th>RAM Usage (MB)</th>
                        <th>RAM Usage (%)</th>
                        <th>Threads</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($appsData as $app) {
        $status = $app['count'] > 0 ? '🟢 Running' : '🔴 Not Running';
        echo '<tr>
            <td><strong>' . htmlspecialchars($app['name']) . '</strong></td>
            <td>' . $app['count'] . '</td>
            <td>' . $app['cpu_percent'] . '%</td>
            <td>' . $app['ram_mb'] . '</td>
            <td>' . $app['ram_percent'] . '%</td>
            <td>' . $app['threads'] . '</td>
            <td>' . $status . '</td>
        </tr>';
    }
    
    echo '      </tbody>
            </table>
            <div class="last-updated">Last updated: ' . date('Y-m-d H:i:s') . '</div>
        </div>
        
        <div class="card">
            <h2>📈 CPU & Memory History</h2>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>
    </div>
    
    <script>
        let historyData = {
            labels: [],
            cpu: [],
            memory: []
        };
        let chart;
        
        function initChart() {
            const ctx = document.getElementById("historyChart").getContext("2d");
            chart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: historyData.labels,
                    datasets: [
                        {
                            label: "CPU Usage (%)",
                            data: historyData.cpu,
                            borderColor: "#4ecca3",
                            backgroundColor: "rgba(78, 204, 163, 0.1)",
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: "Memory Usage (%)",
                            data: historyData.memory,
                            borderColor: "#e94560",
                            backgroundColor: "rgba(233, 69, 96, 0.1)",
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: "#0f3460" },
                            ticks: { color: "#aaa" }
                        },
                        x: {
                            grid: { color: "#0f3460" },
                            ticks: { color: "#aaa" }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: "#eee" } }
                    }
                }
            });
        }
        
        function updateChart() {
            const now = new Date().toLocaleTimeString();
            historyData.labels.push(now);
            historyData.cpu.push(' . round((($systemStats['cpu_user'] + $systemStats['cpu_system']) / $systemStats['cpu_total']) * 100, 2) . ');
            historyData.memory.push(' . $systemStats['mem_percent'] . ');
            
            if (historyData.labels.length > 20) {
                historyData.labels.shift();
                historyData.cpu.shift();
                historyData.memory.shift();
            }
            
            chart.update();
        }
        
        function updateConfig() {
            const apps = document.getElementById("appInput").value;
            const interval = document.getElementById("intervalInput").value;
            window.location.href = "?app=" + encodeURIComponent(apps) + "&interval=" + interval;
        }
        
        initChart();
        setInterval(() => {
            location.reload();
        }, ' . ($config['refresh_interval'] * 1000) . ');
    </script>
</body>
</html>';
}

/**
 * Get CSS class based on percentage
 */
function getClassForPercent($percent) {
    if ($percent >= 80) return 'high';
    if ($percent >= 50) return 'medium';
    return '';
}

/**
 * Display help information
 */
function displayHelp() {
    echo "Realtime Application Monitor for Linux Ubuntu 24.04
===============================================

Usage: php monitor.php [options]

Options:
  -a, --app APP1,APP2,...    Comma-separated list of applications to monitor
  -i, --interval SECONDS     Refresh interval in seconds (default: 2)
  -h, --help                 Display this help message

Examples:
  php monitor.php --app php,node,nginx
  php monitor.php -a mysql,redis -i 5
  php monitor.php --app apache2 --interval 3

Web Interface:
  Open in browser: http://localhost/monitor-sitb/monitor.php?app=php,node,nginx&interval=2

Default applications monitored:
  php, node, nginx, mysql, postgres, redis, apache2
";
}

// Main execution
if ($config['display_mode'] === 'html') {
    displayHtml($config);
} else {
    while (true) {
        displayCli($config);
        sleep($config['refresh_interval']);
    }
}

