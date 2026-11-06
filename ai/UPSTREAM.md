# FASHN-VTON integration

Upstream: https://github.com/fashn-AI/fashn-vton-1.5

Pinned commit: `7c0f10af3f91ad4048fe9729c470a13ef905d25a`

Upstream source and license live in `.runtime/fashn-vton`. Do not remove its Apache-2.0 license or attribution when redistributing. Human parser and DWPose have their own licenses. This integration subclasses the pipeline for CPU preprocessing and BF16 GPU inference; upstream source is not modified.

Weights are downloaded using upstream's script from Hugging Face. Input photos are only sent from Laravel to the loopback service; no hosted FASHN API is used.
