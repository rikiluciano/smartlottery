import ftplib
import os

print("Connecting to FTP...")
ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
ftp.cwd('htdocs')
try:
    ftp.cwd('lottery')
except:
    ftp.mkd('lottery')
    ftp.cwd('lottery')

print("Uploading resultados.php...")
with open('resultados.php', 'rb') as f:
    ftp.storbinary('STOR resultados.php', f)

print("Uploading .htaccess...")
with open('.htaccess', 'rb') as f:
    ftp.storbinary('STOR .htaccess', f)

print("Creating img/logos directory in FTP...")
try: ftp.mkd('img')
except: pass
ftp.cwd('img')
try: ftp.mkd('logos')
except: pass
ftp.cwd('logos')

print("Uploading logos...")
logos_dir = r'resultado\RLabs\logos'
for filename in os.listdir(logos_dir):
    if filename.endswith('.svg'):
        filepath = os.path.join(logos_dir, filename)
        with open(filepath, 'rb') as f:
            ftp.storbinary(f'STOR {filename}', f)
        print(f"Uploaded {filename}")

ftp.quit()
print("All files uploaded successfully.")
