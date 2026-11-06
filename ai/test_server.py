import io
from types import SimpleNamespace
import pytest
from fastapi.testclient import TestClient
from PIL import Image
import server

def png(size=(300,400)):
    buf=io.BytesIO();Image.new('RGB',size,'white').save(buf,format='PNG');return buf.getvalue()

@pytest.fixture
def client(monkeypatch):
    monkeypatch.setattr(server, 'pipeline', lambda **kwargs: SimpleNamespace(images=[Image.new('RGB',(300,400),'blue')]))
    server.app.dependency_overrides[server.authenticate]=lambda: None
    yield TestClient(server.app)
    server.app.dependency_overrides.clear()

def test_unauthorized():
    assert TestClient(server.app).get('/health').status_code == 401

def test_generates_png_and_rejects_invalid_image(client):
    response=client.post('/generate',files={'person':('p.png',png()),'garment':('g.png',png())},data={'photo_type':'flat-lay'})
    assert response.status_code==200 and response.content.startswith(b'\x89PNG')
    assert client.post('/generate',files={'person':('p.png',b'not an image'),'garment':('g.png',png())}).status_code==422

def test_busy_and_missing_model(client,monkeypatch):
    server.lock.acquire()
    try: assert client.post('/generate',files={'person':('p.png',png()),'garment':('g.png',png())}).status_code==429
    finally: server.lock.release()
    monkeypatch.setattr(server,'pipeline',None)
    assert client.get('/health').json()['ready'] is False
