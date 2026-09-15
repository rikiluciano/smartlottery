import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    files = ftp.nlst()
    hist_files = [f for f in files if f.startswith('history_')]
    print("Number of history files:", len(hist_files))
except Exception as e:
    print("Error:", e)
