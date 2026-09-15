import ftplib

print("Connecting to FTP...")
ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
ftp.cwd('htdocs')
try:
    ftp.cwd('lottery')
except:
    pass

images = ['ai_bot_avatar_1788497063695.jpg', 'finance_ai_bg_1788497081588.jpg']
import os
for img in images:
    if os.path.exists(img):
        print(f"Uploading {img}...")
        with open(img, 'rb') as f:
            ftp.storbinary(f'STOR {img}', f)
    else:
        # Check inside brain folder
        brain_path = r'C:\Users\Ricardo\.gemini\antigravity\brain\220755e1-29fd-4d44-b7de-aa69b2b1d766'
        img_path = os.path.join(brain_path, img)
        if os.path.exists(img_path):
            print(f"Uploading {img} from brain...")
            with open(img_path, 'rb') as f:
                ftp.storbinary(f'STOR {img}', f)

ftp.quit()
print("Images uploaded.")
