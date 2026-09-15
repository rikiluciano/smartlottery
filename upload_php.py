import ftplib
import os

print("Connecting to FTP...")
ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
ftp.cwd('htdocs/lottery')

files_to_upload = [
    ('core/logic.php', 'core/logic.php')
]

for local_path, remote_path in files_to_upload:
    print(f"Uploading {local_path}...")
    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_path}', f)

ftp.quit()
print("Uploaded successfully.")
