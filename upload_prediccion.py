import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ['ia_prediccion_pale.php', 'prediccion.json']
    for f in files:
        with open(f, 'rb') as local_file:
            ftp.storbinary(f'STOR {f}', local_file)
        print(f"Uploaded {f}")
except Exception as e:
    print("Error:", e)
