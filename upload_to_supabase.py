import json
import requests
import time

URL = 'https://glzhfpwahowzjsrivvno.supabase.co/rest/v1/sorteos'
HEADERS = {
    'apikey': 'sb_publishable_-RJ_q92T8oWqrNhpncamBw_RKEPjyfL',
    'Authorization': 'Bearer sb_publishable_-RJ_q92T8oWqrNhpncamBw_RKEPjyfL',
    'Content-Type': 'application/json',
    'Prefer': 'return=minimal'
}

def upload_data():
    try:
        with open('db_backup.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print("Error loading db_backup.json:", e)
        return

    # Clean data to match schema
    clean_data = []
    for row in data:
        if not row.get('fecha') or not row.get('primera') or not row.get('segunda') or not row.get('tercera'):
            continue
            
        clean_row = {
            'fecha': row['fecha'],
            'nombre_loteria': row.get('nombre_loteria', row.get('loteria', 'Desconocida')),
            'primera': row['primera'],
            'segunda': row['segunda'],
            'tercera': row['tercera']
        }
        clean_data.append(clean_row)

    print(f"Total rows to upload: {len(clean_data)}")
    
    chunk_size = 1000
    for i in range(0, len(clean_data), chunk_size):
        chunk = clean_data[i:i + chunk_size]
        try:
            res = requests.post(URL, headers=HEADERS, json=chunk)
            if res.status_code in [200, 201, 204]:
                print(f"Uploaded chunk {i//chunk_size + 1}: rows {i} to {i+len(chunk)-1}")
            else:
                print(f"Error on chunk {i//chunk_size + 1}: {res.status_code} - {res.text}")
        except Exception as e:
            print(f"Request error on chunk {i//chunk_size + 1}: {e}")
        time.sleep(0.5)

    print("Migration complete!")

if __name__ == '__main__':
    upload_data()
