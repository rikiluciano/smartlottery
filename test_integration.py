import pytest
import os
import tempfile
import json
from vps_scraper import update_local_data_lake, update_db_backup

def test_update_local_data_lake_integration(mocker):
    # Mock file path to use a temporary file
    with tempfile.NamedTemporaryFile('w', delete=False) as tf:
        tf.write("2026-09-01|01-02,01-03\n")
        temp_path = tf.name

    mocker.patch('os.path.expanduser', return_value=temp_path)

    # Input data: 2026-09-01 adds a new combination, 2026-09-02 is completely new
    resultados = [
        {'fecha': '2026-09-01', 'numeros': ['01', '04', '05']},
        {'fecha': '2026-09-02', 'numeros': ['10', '20', '30']}
    ]

    update_local_data_lake(resultados)

    with open(temp_path, 'r', encoding='utf-8') as f:
        content = f.read().strip().split('\n')
    
    os.remove(temp_path)

    assert len(content) == 2
    
    parts1 = content[0].split('|')
    assert parts1[0] == '2026-09-01'
    assert '01-02' in parts1[1]
    assert '01-04' in parts1[1]

    parts2 = content[1].split('|')
    assert parts2[0] == '2026-09-02'
    assert '10-20' in parts2[1]
    assert '10-30' in parts2[1]


def test_update_db_backup_integration(mocker):
    with tempfile.NamedTemporaryFile('w', delete=False) as tf:
        initial_data = [
            {'id': 1, 'fecha': '2026-09-01', 'nombre_loteria': 'Loteka', 'primera': '01', 'segunda': '02', 'tercera': '03'}
        ]
        json.dump(initial_data, tf)
        temp_path = tf.name

    mocker.patch('os.path.expanduser', return_value=temp_path)
    # mock os.system para evitar llamar script de python
    mocker.patch('os.system', return_value=0)

    resultados = [
        # Duplicado, debe ignorarlo
        {'fecha': '2026-09-01', 'nombre': 'Loteka', 'numeros': ['01', '02', '03']},
        # Nuevo
        {'fecha': '2026-09-02', 'nombre': 'Nacional', 'numeros': ['10', '20', '30']}
    ]

    update_db_backup(resultados)

    with open(temp_path, 'r', encoding='utf-8') as f:
        new_data = json.load(f)
    
    os.remove(temp_path)

    assert len(new_data) == 2
    assert new_data[1]['id'] == 2
    assert new_data[1]['nombre_loteria'] == 'Nacional'
