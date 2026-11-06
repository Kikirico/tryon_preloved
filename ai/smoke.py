"""Run real inference on upstream's sample images and record GPU usage."""
import json
import time
from pathlib import Path
from PIL import Image
import torch
from runtime import ROOT, load_pipeline

started = time.perf_counter()
pipeline = load_pipeline()
loaded = time.perf_counter()
samples = ROOT / '.runtime/fashn-vton/examples/data'
torch.cuda.reset_peak_memory_stats()
result = pipeline(person_image=Image.open(samples/'model.webp').convert('RGB'), garment_image=Image.open(samples/'garment.webp').convert('RGB'), category='tops', garment_photo_type='model', num_samples=1, num_timesteps=20)
output = ROOT / '.runtime/fashn-smoke.png'
result.images[0].save(output)
metrics = {'load_seconds':round(loaded-started,1),'inference_seconds':round(time.perf_counter()-loaded,1),'peak_vram_gib':round(torch.cuda.max_memory_allocated()/1024**3,2),'output':str(output)}
(ROOT/'.runtime/fashn-smoke.json').write_text(json.dumps(metrics,indent=2))
print(json.dumps(metrics))
