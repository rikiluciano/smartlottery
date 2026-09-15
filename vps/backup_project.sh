#!/bin/bash
: "${LOTTERY_FTP_USER:?exporta LOTTERY_FTP_USER}"
: "${LOTTERY_FTP_PASS:?exporta LOTTERY_FTP_PASS}"
DATE=$(date +%Y-%m-%d)
BACKUP_DIR=$HOME/Lottery-Backup
mkdir -p $BACKUP_DIR/lottery_raw

echo "Fetching project files via wget..."
cd $BACKUP_DIR
wget -m ftp://"$LOTTERY_FTP_USER":"$LOTTERY_FTP_PASS"@"${LOTTERY_FTP_HOST:-ftpupload.net}"/htdocs/lottery/ -P lottery_raw

tar -czf lottery_backup_$DATE.tar.gz lottery_raw
echo "Backup saved as lottery_backup_$DATE.tar.gz"
