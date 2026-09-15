from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import ftplib
import os

def ftp_upload_dir(ftp, local_dir, remote_dir):
    try:
        ftp.cwd(remote_dir)
    except ftplib.error_perm:
        try:
            ftp.mkd(remote_dir)
            ftp.cwd(remote_dir)
        except ftplib.error_perm as e:
            print(f"Cannot create or cd to {remote_dir}: {e}")
            return
            
    for item in os.listdir(local_dir):
        local_path = os.path.join(local_dir, item)
        if os.path.isfile(local_path):
            if item == "ftp_test.py" or item == "ftp_upload.py" or item.endswith(".log"): continue
            with open(local_path, 'rb') as f:
                print(f"Uploading {local_path} to {item}...")
                ftp.storbinary(f'STOR {item}', f)
        elif os.path.isdir(local_path):
            if item == ".git" or item == "node_modules" or item == "scratch": continue
            print(f"Entering directory {item}...")
            ftp_upload_dir(ftp, local_path, item)
            ftp.cwd('..')

host = "ftpupload.net"
user = FTP_USER
passwd = FTP_PASS
local_dir = r"C:\Users\Ricardo\OneDrive\Escritorio\Lottery"

try:
    print(f"Connecting to {host}...")
    ftp = ftplib.FTP(host)
    ftp.login(user, passwd)
    print("Connected! Starting upload...")
    
    ftp.cwd('htdocs')
    try:
        ftp.cwd('lottery')
    except ftplib.error_perm:
        ftp.mkd('lottery')
        ftp.cwd('lottery')
    
    for item in os.listdir(local_dir):
        local_path = os.path.join(local_dir, item)
        if os.path.isfile(local_path):
            if item == "ftp_test.py" or item == "ftp_upload.py" or item.endswith(".log"): continue
            with open(local_path, 'rb') as f:
                print(f"Uploading {local_path} to {item}...")
                ftp.storbinary(f'STOR {item}', f)
        elif os.path.isdir(local_path):
            if item == ".git" or item == "node_modules" or item == "scratch": continue
            print(f"Entering directory {item}...")
            ftp_upload_dir(ftp, local_path, item)
            ftp.cwd('..')
            
    ftp.quit()
    print("Upload completed successfully!")
except Exception as e:
    print(f"Error during upload: {e}")
