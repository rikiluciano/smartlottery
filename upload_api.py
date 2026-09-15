import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ['api_prediccion.php', 'ia_prediccion_pale.php']
    for f in files:
        with open(f, 'rb') as lf:
            ftp.storbinary(f'STOR {f}', lf)
        print(f"Uploaded {f}")
except Exception as e:
    print(e)
