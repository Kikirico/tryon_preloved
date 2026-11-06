"""8 GB VRAM profile: model in BF16, pose/parser on CPU."""
import os
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
os.environ.setdefault('HF_HOME', str(ROOT / '.runtime' / 'huggingface'))

def load_pipeline():
    import torch
    from fashn_vton import TryOnPipeline
    from fashn_vton.tryon_mmdit import TryOnModel
    from fashn_vton.dwpose import DWposeDetector
    from fashn_human_parser import FashnHumanParser
    from safetensors.torch import load_file

    if not torch.cuda.is_available():
        raise RuntimeError('CUDA tidak tersedia. Pasang PyTorch CUDA dan driver NVIDIA.')

    class LaptopPipeline(TryOnPipeline):
        def _setup_tryon_model(self):
            # Loading on CPU avoids duplicating the checkpoint in GPU memory.
            with torch.device('meta'):
                self.tryon_model = TryOnModel()
            state = load_file(str(Path(self.weights_dir) / 'model.safetensors'), device='cpu')
            self.tryon_model.load_state_dict(state, assign=True)
            del state
            self.tryon_model.to(device=self.device, dtype=self.inference_dtype).eval()

        def _setup_pose_model(self):
            self.pose_model = DWposeDetector(checkpoints_dir=str(Path(self.weights_dir) / 'dwpose'), device='cpu')

        def _setup_hp_model(self):
            self.hp_model = FashnHumanParser(device='cpu')

    torch.set_num_threads(min(4, os.cpu_count() or 4))
    return LaptopPipeline(weights_dir=str(ROOT / '.runtime' / 'fashn-weights'), device='cuda')
