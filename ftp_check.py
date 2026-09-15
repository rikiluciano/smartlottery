import ftplib
import json

try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    print("Files on FTP:", files)
    
    if 'db_backup.json' in files:
        with open('local_backup.json', 'wb') as f:
            ftp.retrbinary('RETR db_backup.json', f.write)
            
        with open('local_backup.json', 'r') as f:
            data = json.load(f)
            print("Rows in DB backup:", len(data))
    else:
        print("db_backup.json not found.")
        
except Exception as e:
    print("Error:", e)
