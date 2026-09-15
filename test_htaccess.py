import ftplib
try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    with open('local_htaccess_test.txt', 'wb') as f:
        f.write(b"php_value auto_prepend_file 'sec.php'\n")
    with open('local_htaccess_test.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
    print("Uploaded .htaccess")
except Exception as e:
    print("Error:", e)
