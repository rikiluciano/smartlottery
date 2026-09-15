import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files_to_upload = [
        ('security.php', 'security.php'),
        ('resultados.php', 'resultados.php'),
        ('panel_admin.php', 'panel_admin.php'),
        ('index.php', 'index.php'),
        ('.htaccess', '.htaccess')
    ]
    for l, r in files_to_upload:
        with open(l, 'rb') as f:
            ftp.storbinary(f'STOR {r}', f)
        print(f"Uploaded {r}")
    try:
        ftp.delete('index.html')
    except: pass
    print("Done")
except Exception as e:
    print("Error:", e)
