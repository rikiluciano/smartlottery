import ftplib

host = "ftpupload.net"
user = "if0_40933868"
passwd = "WYDk3sCTGK8s0u"

try:
    ftp = ftplib.FTP(host)
    ftp.login(user, passwd)
    print("Conectado.")
    ftp.cwd('htdocs/lottery')
    with open('local_pendientes.json', 'wb') as f:
        ftp.retrbinary('RETR resultados_pendientes.json', f.write)
    print("Descargado.")
    ftp.quit()
except Exception as e:
    print(f"Error: {e}")
