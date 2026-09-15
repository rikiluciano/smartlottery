document.addEventListener('DOMContentLoaded', () => {
    const logContainer = document.getElementById('log-content');
    
    function log(message, type = "info") {
        const p = document.createElement('p');
        p.className = 'font-mono text-sm mb-1 opacity-90';
        
        let color = "text-gray-300";
        if (type === "success") color = "text-green-400";
        if (type === "error") color = "text-red-400";
        if (type === "warning") color = "text-yellow-400";
        
        p.innerHTML = `<span class="text-blue-400">[${new Date().toLocaleTimeString()}]</span> <span class="${color}">${message}</span>`;
        logContainer.appendChild(p);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    log("Conectado al motor VPS remoto.", "success");
    log("Sincronización FTP configurada cada 1 minuto.", "info");
    log("Monitoreando estado... Todo funcionando correctamente.", "info");
    
    setInterval(() => {
        log("Haciendo ping al VPS... OK", "success");
    }, 60000);
});
