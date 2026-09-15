import time
import os
import ftplib
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
from . import config

HOST = config.FTP_HOST
USER = config.FTP_USER
PASSWD = config.FTP_PASS
REMOTE_BASE_DIR = "/htdocs/lottery"
LOCAL_BASE_DIR = os.path.abspath(os.path.dirname(__file__))

IGNORE_LIST = [".git", "node_modules", "scratch", "__pycache__", ".vscode", "ftp_sync.py"]

class MyHandler(FileSystemEventHandler):
    def upload_file(self, local_path):
        # Convertir ruta local a ruta relativa
        rel_path = os.path.relpath(local_path, LOCAL_BASE_DIR)
        
        # Ignorar carpetas y archivos específicos
        path_parts = rel_path.split(os.sep)
        for ignore in IGNORE_LIST:
            if ignore in path_parts:
                return

        if not os.path.isfile(local_path):
            return

        # Construir ruta remota (InfinityFree usa barras /)
        remote_path = REMOTE_BASE_DIR + "/" + rel_path.replace(os.sep, "/")
        remote_dir = "/".join(remote_path.split("/")[:-1])
        filename = remote_path.split("/")[-1]

        try:
            print(f"[{time.strftime('%H:%M:%S')}] Detectado cambio en: {rel_path}")
            print(f"[{time.strftime('%H:%M:%S')}] Conectando FTP para subir...")
            ftp = ftplib.FTP(HOST)
            ftp.login(USER, PASSWD)
            
            # Navegar a la carpeta remota o crearla si no existe
            current_path = ""
            for part in remote_dir.split("/"):
                if not part:
                    continue
                current_path += "/" + part
                try:
                    ftp.cwd(current_path)
                except ftplib.error_perm:
                    try:
                        ftp.mkd(current_path)
                        ftp.cwd(current_path)
                    except:
                        pass # Si falla, probablemente ya existe pero no tenemos permisos
            
            # Subir el archivo
            with open(local_path, 'rb') as f:
                ftp.storbinary(f"STOR {filename}", f)
            ftp.quit()
            print(f"[{time.strftime('%H:%M:%S')}] ¡Subida exitosa de {filename}! ✓")
            print("-" * 50)
        except Exception as e:
            print(f"[{time.strftime('%H:%M:%S')}] ❌ Error subiendo {filename}: {e}")
            print("-" * 50)

    def on_modified(self, event):
        if not event.is_directory:
            self.upload_file(event.src_path)
            
    def on_created(self, event):
        if not event.is_directory:
            self.upload_file(event.src_path)

if __name__ == "__main__":
    print(f"==================================================")
    print(f"🚀 Iniciando sincronización automática con InfinityFree FTP...")
    print(f"📁 Monitoreando carpeta: {LOCAL_BASE_DIR}")
    print(f"==================================================")
    print("Presiona Ctrl+C en cualquier momento para detener.\n")
    
    event_handler = MyHandler()
    observer = Observer()
    observer.schedule(event_handler, LOCAL_BASE_DIR, recursive=True)
    observer.start()
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        print("\nSincronización detenida por el usuario.")
        observer.stop()
    observer.join()
