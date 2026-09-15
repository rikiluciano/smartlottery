import ftplib

try:
    ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
    ftp.cwd('htdocs/lottery')
    
    with open('local_htaccess_dl.txt', 'wb') as f:
        ftp.retrbinary('RETR .htaccess', f.write)
        
    with open('local_htaccess_dl.txt', 'r', encoding='utf-8') as f:
        content = f.read()
        
    exception_block = """
# Permitir acceso al JSON publico de prediccion
<Files "prediccion.json">
    Require all granted
</Files>
"""
    if 'prediccion.json' not in content:
        content = content + exception_block
        
    with open('local_htaccess_dl.txt', 'w', encoding='utf-8') as f:
        f.write(content)
        
    with open('local_htaccess_dl.txt', 'rb') as f:
        ftp.storbinary('STOR .htaccess', f)
        
    print("Fixed .htaccess uploaded.")
except Exception as e:
    print("Error:", e)
