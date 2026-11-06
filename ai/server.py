"""Private loopback service; run one uvicorn worker only."""
import asyncio
import io
import logging
import secrets
import threading
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Literal
from fastapi import Depends, FastAPI, File, Form, Header, HTTPException, UploadFile
from fastapi.responses import Response
from PIL import Image, ImageOps, UnidentifiedImageError
from runtime import ROOT, load_pipeline

pipeline = None
load_error = False
lock = threading.Lock()
Image.MAX_IMAGE_PIXELS = 4096 * 4096

def initialize():
    global pipeline, load_error
    try: pipeline = load_pipeline()
    except Exception:
        load_error = True
        logging.exception('FASHN initialization failed')

@asynccontextmanager
async def lifespan(app):
    task = asyncio.create_task(asyncio.to_thread(initialize))
    yield
    await task

app = FastAPI(lifespan=lifespan, docs_url=None, redoc_url=None)

def authenticate(authorization: str = Header(default='')):
    key_path = ROOT / '.runtime' / 'fashn-service.key'
    expected = key_path.read_text().strip() if key_path.exists() else ''
    if not expected or not secrets.compare_digest(authorization, 'Bearer ' + expected):
        raise HTTPException(401, 'Unauthorized')

@app.get('/health', dependencies=[Depends(authenticate)])
def health():
    return {'ready': pipeline is not None, 'loading': pipeline is None and not load_error, 'busy': lock.locked()}

def read_image(upload):
    data = upload.file.read(5 * 1024 * 1024 + 1)
    if len(data) > 5 * 1024 * 1024: raise HTTPException(413, 'Image too large')
    try:
        image = Image.open(io.BytesIO(data))
        if image.format not in {'JPEG','PNG','WEBP'} or min(image.size) < 256 or max(image.size) > 4096:
            raise HTTPException(422, 'Use JPG/PNG/WebP, 256–4096 pixels')
        image = ImageOps.exif_transpose(image)
        if image.mode == 'RGBA' or 'transparency' in image.info:
            image = image.convert('RGBA'); bg = Image.new('RGBA', image.size, 'white'); bg.alpha_composite(image); image = bg
        return image.convert('RGB')
    except (UnidentifiedImageError, OSError, Image.DecompressionBombError) as exc:
        raise HTTPException(422, 'Invalid image') from exc

@app.post('/generate', dependencies=[Depends(authenticate)])
def generate(person: UploadFile = File(...), garment: UploadFile = File(...), photo_type: Literal['flat-lay','model'] = Form('flat-lay')):
    if pipeline is None: raise HTTPException(503, 'Model not ready')
    if not lock.acquire(blocking=False): raise HTTPException(429, 'GPU busy')
    try:
        person_image, garment_image = read_image(person), read_image(garment)
        result = pipeline(person_image=person_image, garment_image=garment_image, category='tops', garment_photo_type=photo_type, num_samples=1, num_timesteps=20)
        output = io.BytesIO(); result.images[0].save(output, format='PNG')
        return Response(output.getvalue(), media_type='image/png', headers={'Cache-Control':'no-store'})
    except HTTPException: raise
    except Exception:
        logging.exception('FASHN inference failed (input photos are not logged)')
        raise HTTPException(500, 'Inference failed; check GPU memory and service log')
    finally:
        import torch
        torch.cuda.empty_cache()
        lock.release()
