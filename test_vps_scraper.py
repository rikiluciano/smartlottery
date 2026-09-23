import pytest
from vps_scraper import extract_date, sort_pale, fetch_lottery

def test_extract_date_valid():
    event_data = {'startDate': '2026-09-04T20:50:00-04:00'}
    assert extract_date(event_data) == '2026-09-04'

def test_extract_date_missing():
    assert extract_date({}) is None
    assert extract_date({'startDate': ''}) is None

def test_sort_pale():
    assert sort_pale('01', '10') == '01-10'
    assert sort_pale('10', '01') == '01-10'
    assert sort_pale('15', '15') == '15-15'

def test_fetch_lottery_mock(mocker):
    html_mock = """
    <html>
    <body>
    <script type="application/ld+json">
    [
        {
            "@type": "Event",
            "name": "Sorteo de Prueba",
            "description": "Los ganadores son: 12, 34, 56.",
            "startDate": "2026-09-23T20:00:00-04:00"
        }
    ]
    </script>
    </body>
    </html>
    """
    
    # Mocking requests.get
    mock_response = mocker.Mock()
    mock_response.text = html_mock
    mocker.patch('requests.get', return_value=mock_response)
    
    resultados = fetch_lottery('http://dummy.url')
    
    assert len(resultados) == 1
    assert resultados[0]['fecha'] == '2026-09-23'
    assert resultados[0]['nombre'] == 'Sorteo de Prueba'
    assert resultados[0]['numeros'] == ['12', '34', '56']
