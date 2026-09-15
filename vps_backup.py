from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import ftplib
import os
import time
import zipfile
from datetime import datetime

backup_dir = "/home/ubuntu/Lottery-Backup"
if not os.path.exists(backup_dir):
    os.makedirs(backup_dir)

today_str = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
zip_filename = os.path.join(backup_dir, f"lottery_backup_{today_str}.zip")

ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
ftp.cwd('htdocs/lottery')

def download_dir(ftp_dir, local_zip):
    file_list = ftp.nlst()
    for item in file_list:
        if item in ['.', '..']: continue
        
        try:
            # check if it's a directory
            ftp.cwd(item)
            ftp.cwd('..')
            is_dir = True
        except:
            is_dir = False
            
        if is_dir:
            ftp.cwd(item)
            download_dir(f"{ftp_dir}/{item}" if ftp_dir else item, local_zip)
            ftp.cwd('..')
        else:
            local_path = f"/tmp/lottery_temp_{item}"
            with open(local_path, 'wb') as f:
                ftp.retrbinary(f"RETR {item}", f.write)
            arcname = f"{ftp_dir}/{item}" if ftp_dir else item
            local_zip.write(local_path, arcname)
            os.remove(local_path)

with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zf:
    download_dir("", zf)

ftp.quit()
print(f"Backup guardado exitosamente en {zip_filename}")
