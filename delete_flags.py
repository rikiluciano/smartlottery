import ftplib

print("Connecting to FTP...")
ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
ftp.cwd('htdocs/lottery')

try:
    ftp.delete('db_wiped.flag')
    print("Deleted db_wiped.flag")
except:
    print("db_wiped.flag not found")

try:
    ftp.delete('resultados_pendientes.json')
    print("Deleted pending json")
except:
    pass

ftp.quit()
print("Clean up done.")
