<?php require_once 'security.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Scraping Bot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkbg: '#0f172a',
                        'neon-teal': '#2dd4bf',
                        'neon-purple': '#c084fc'
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0f172a; color: white; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        #log-container {
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
        }
    </style>
</head>
<body class="min-h-screen p-6 relative">
    
    <!-- Background styling -->
    <div class="fixed inset-0 pointer-events-none z-[-1]" style="background-image: url('finance_bg.jpg'); background-size: cover; background-position: center; opacity: 0.05; mix-blend-mode: screen;"></div>

    <div class="max-w-4xl mx-auto">
        <div class="glass-panel rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <div class="flex items-center space-x-4 mb-8 border-b border-white/10 pb-6">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-neon-purple/20 to-neon-teal/20 flex items-center justify-center border border-white/10 overflow-hidden shadow-[0_0_15px_rgba(45,212,191,0.3)]">
                    <img src="ai_avatar.jpg" alt="AI Bot" class="w-full h-full object-cover mix-blend-lighten opacity-90">
                </div>
                <div>
                    <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-neon-purple to-neon-teal">Bot Extractor de Resultados</h1>
                    <p class="text-gray-400 mt-1">Panel de control privado para sincronizar las 42 loterías.</p>
                </div>
            </div>

            <!-- Controles -->
            <div class="mt-8 flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Estado del Motor:</p>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.8)] animate-pulse" id="status-indicator"></span>
                        <span class="text-green-400 font-semibold tracking-wider" id="status-text">Operando 24/7 en VPS</span>
                    </div>
                </div>
                
                <div class="px-6 py-3 bg-gradient-to-r from-blue-600/50 to-indigo-600/50 text-white font-medium rounded-xl border border-blue-400/30 flex items-center gap-2 cursor-default">
                    <i class="fas fa-robot"></i> Totalmente Automatizado
                </div>
            </div>

            <div class="bg-black/50 rounded-xl p-4 border border-white/5">
                <h3 class="text-sm text-gray-400 mb-2 uppercase tracking-widest"><i class="fas fa-terminal mr-2"></i> Registro del Sistema</h3>
                <div id="log-container" class="text-sm text-gray-300 space-y-1 p-2">
                    <div class="text-gray-500">Esperando orden de ejecución...</div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-center space-x-4">
            <a href="crear_tablas.php" target="_blank" class="text-sm text-gray-500 hover:text-neon-purple transition-colors"><i class="fas fa-database mr-1"></i> Inicializar Tablas DB</a>
            <a href="resultados.php" class="text-sm text-gray-500 hover:text-neon-teal transition-colors"><i class="fas fa-eye mr-1"></i> Ver Interfaz Pública</a>
        </div>
    </div>

    <script src="scraper_bot.js?v=1"></script>
</body>
</html>
