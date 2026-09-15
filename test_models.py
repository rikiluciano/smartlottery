import urllib.request
import json
import time

openRouterApiKey = "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"

def test_model(model_id):
    req = urllib.request.Request(
        "https://openrouter.ai/api/v1/chat/completions",
        data=json.dumps({
            "model": model_id,
            "messages": [{"role": "user", "content": "Hola"}],
            "max_tokens": 10
        }).encode('utf-8'),
        headers={
            "Authorization": f"Bearer {openRouterApiKey}",
            "Content-Type": "application/json"
        }
    )
    try:
        response = urllib.request.urlopen(req)
        data = json.loads(response.read())
        if 'error' not in data:
            return True, "OK"
        return False, data['error']
    except Exception as e:
        return False, str(e)

models = json.loads(urllib.request.urlopen('https://openrouter.ai/api/v1/models').read())['data']
free_models = [m['id'] for m in models if ':free' in m['id']]

for m in free_models:
    success, msg = test_model(m)
    print(f"Model: {m} - Success: {success} - {msg}")
    time.sleep(1)
