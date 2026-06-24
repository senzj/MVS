@props([
    'wireModel'   => 'image',
    'aspectRatio' => 1,
    'targetSize'  => 800,
])

<div
    x-data="{
        show:        false,
        wireModel:   @js($wireModel),
        aspectRatio: {{ (float) $aspectRatio }},
        targetSize:  {{ (int)   $targetSize  }},

        /* ── loaded image ───────────────────────────── */
        img:          null,
        imgNaturalW:  0,
        imgNaturalH:  0,

        /* ── canvas logical dimensions ──────────────── */
        canvasW: 0,
        canvasH: 380,

        /* ── fixed selection box (centred) ──────────── */
        selX: 0, selY: 0, selW: 0, selH: 0,

        /* ── image transform ─────────────────────────  */
        scale:   1,
        originX: 0,
        originY: 0,

        /* ── drag / pinch state ──────────────────────  */
        dragging:      false,
        lastX:         0,
        lastY:         0,
        lastPinchDist: null,
        listenersAdded: false,

        /* ════════════════════════════════════════════ */
        init() {
            window.addEventListener('open-cropper', (e) => {
                if (e.detail.wireModel && e.detail.wireModel !== this.wireModel) return;
                this.show = true;
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.setupCanvas();
                        this.loadImage(e.detail.src);
                    });
                });
            });
        },

        /* ── canvas bootstrap ──────────────────────── */
        setupCanvas() {
            const canvas    = this.$refs.canvas;
            const container = this.$refs.canvasWrap;

            this.canvasW = container.clientWidth;
            this.canvasH = 380;

            /* set intrinsic + CSS size together so nothing stretches */
            canvas.width  = this.canvasW;
            canvas.height = this.canvasH;
            canvas.style.width  = this.canvasW + 'px';
            canvas.style.height = this.canvasH + 'px';

            /* compute centred selection box that respects aspect ratio */
            const padX    = this.canvasW * 0.1;
            const padY    = this.canvasH * 0.1;
            const maxSelW = this.canvasW - padX * 2;
            const maxSelH = this.canvasH - padY * 2;

            if (maxSelW / maxSelH > this.aspectRatio) {
                this.selH = maxSelH;
                this.selW = this.selH * this.aspectRatio;
            } else {
                this.selW = maxSelW;
                this.selH = this.selW / this.aspectRatio;
            }
            this.selW = Math.round(this.selW);
            this.selH = Math.round(this.selH);
            this.selX = Math.round((this.canvasW - this.selW) / 2);
            this.selY = Math.round((this.canvasH - this.selH) / 2);

            /* attach low-level listeners once (passive:false for wheel/touch) */
            if (!this.listenersAdded) {
                canvas.addEventListener('mousedown',  (e) => this.onMouseDown(e));
                window.addEventListener('mousemove',  (e) => this.onMouseMove(e));
                window.addEventListener('mouseup',    ()  => this.onMouseUp());
                canvas.addEventListener('wheel',      (e) => this.onWheel(e),      { passive: false });
                canvas.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: false });
                canvas.addEventListener('touchmove',  (e) => this.onTouchMove(e),  { passive: false });
                canvas.addEventListener('touchend',   (e) => this.onTouchEnd(e));
                this.listenersAdded = true;
            }
        },

        // clamp the image origin so that the image always fully covers the selection box
        clampOrigin() {
            const imgW = this.imgNaturalW * this.scale;
            const imgH = this.imgNaturalH * this.scale;

            // how far the image origin can travel before an edge leaves the crop box
            const minX = this.selX + this.selW - imgW;  // right edge clamp
            const maxX = this.selX;                       // left edge clamp
            const minY = this.selY + this.selH - imgH;  // bottom edge clamp
            const maxY = this.selY;                       // top edge clamp

            this.originX = Math.max(minX, Math.min(maxX, this.originX));
            this.originY = Math.max(minY, Math.min(maxY, this.originY));
        },

        /* ── load & auto-fit ───────────────────────── */
        loadImage(src) {
            const image   = new Image();
            image.onload  = () => {
                this.img         = image;
                this.imgNaturalW = image.naturalWidth;
                this.imgNaturalH = image.naturalHeight;
                this.fitImage();
                this.draw();
            };
            image.src = src;
        },

        fitImage() {
            /* scale so the image covers the whole selection box, then centre it */
            const scaleX  = this.selW / this.imgNaturalW;
            const scaleY  = this.selH / this.imgNaturalH;
            this.scale    = Math.max(scaleX, scaleY);
            const imgW    = this.imgNaturalW * this.scale;
            const imgH    = this.imgNaturalH * this.scale;
            this.originX  = this.selX + (this.selW - imgW) / 2;
            this.originY  = this.selY + (this.selH - imgH) / 2;
        },

        /* ── render ─────────────────────────────────── */
        draw() {
            if (!this.img) return;
            const canvas = this.$refs.canvas;
            const ctx    = canvas.getContext('2d');

            ctx.clearRect(0, 0, this.canvasW, this.canvasH);

            /* image */
            ctx.drawImage(
                this.img,
                this.originX, this.originY,
                this.imgNaturalW * this.scale,
                this.imgNaturalH * this.scale
            );

            /* shade outside the selection */
            ctx.fillStyle = 'rgba(0,0,0,0.55)';
            ctx.fillRect(0, 0,                   this.canvasW,  this.selY);
            ctx.fillRect(0, this.selY + this.selH, this.canvasW, this.canvasH - this.selY - this.selH);
            ctx.fillRect(0, this.selY,             this.selX,    this.selH);
            ctx.fillRect(this.selX + this.selW, this.selY, this.canvasW - this.selX - this.selW, this.selH);

            /* selection border */
            ctx.strokeStyle = 'white';
            ctx.lineWidth   = 2;
            ctx.strokeRect(this.selX + 1, this.selY + 1, this.selW - 2, this.selH - 2);

            /* rule-of-thirds grid */
            ctx.strokeStyle = 'rgba(255,255,255,0.22)';
            ctx.lineWidth   = 1;
            ctx.beginPath();
            for (let i = 1; i < 3; i++) {
                const gx = this.selX + (this.selW * i / 3);
                const gy = this.selY + (this.selH * i / 3);
                ctx.moveTo(gx, this.selY); ctx.lineTo(gx, this.selY + this.selH);
                ctx.moveTo(this.selX, gy); ctx.lineTo(this.selX + this.selW, gy);
            }
            ctx.stroke();

            /* corner handles */
            const hs = 10;
            ctx.fillStyle = 'white';
            [
                [this.selX,                   this.selY                  ],
                [this.selX + this.selW - hs,  this.selY                  ],
                [this.selX,                   this.selY + this.selH - hs ],
                [this.selX + this.selW - hs,  this.selY + this.selH - hs ],
            ].forEach(([cx, cy]) => ctx.fillRect(cx, cy, hs, hs));
        },

        /* ── mouse events ───────────────────────────── */
        onMouseDown(e) {
            this.dragging            = true;
            this.lastX               = e.clientX;
            this.lastY               = e.clientY;
            this.$refs.canvas.style.cursor = 'grabbing';
        },
        onMouseMove(e) {
            if (!this.dragging) return;
            this.pan(e.clientX - this.lastX, e.clientY - this.lastY);
            this.lastX = e.clientX;
            this.lastY = e.clientY;
        },
        onMouseUp() {
            this.dragging = false;
            if (this.$refs.canvas) this.$refs.canvas.style.cursor = 'grab';
        },
        onWheel(e) {
            e.preventDefault();
            /* positive deltaY = scroll down = zoom out */
            const delta = e.deltaY < 0 ? 0.1 : -0.1;
            const rect  = this.$refs.canvas.getBoundingClientRect();
            this.zoom(delta, e.clientX - rect.left, e.clientY - rect.top);
        },

        /* ── touch events ───────────────────────────── */
        onTouchStart(e) {
            e.preventDefault();
            if (e.touches.length === 1) {
                this.dragging      = true;
                this.lastX         = e.touches[0].clientX;
                this.lastY         = e.touches[0].clientY;
                this.lastPinchDist = null;
            } else if (e.touches.length === 2) {
                this.dragging      = false;
                this.lastPinchDist = this.pinchDist(e.touches);
            }
        },
        onTouchMove(e) {
            e.preventDefault();
            if (e.touches.length === 1 && this.dragging) {
                this.pan(e.touches[0].clientX - this.lastX, e.touches[0].clientY - this.lastY);
                this.lastX = e.touches[0].clientX;
                this.lastY = e.touches[0].clientY;
            } else if (e.touches.length === 2 && this.lastPinchDist !== null) {
                const dist  = this.pinchDist(e.touches);
                const delta = (dist - this.lastPinchDist) / 300;
                const rect  = this.$refs.canvas.getBoundingClientRect();
                const midX  = (e.touches[0].clientX + e.touches[1].clientX) / 2 - rect.left;
                const midY  = (e.touches[0].clientY + e.touches[1].clientY) / 2 - rect.top;
                this.zoom(delta, midX, midY);
                this.lastPinchDist = dist;
            }
        },
        onTouchEnd(e) {
            if (e.touches.length === 0) { this.dragging = false; this.lastPinchDist = null; }
            if (e.touches.length === 1)   this.lastPinchDist = null;
        },
        pinchDist(touches) {
            return Math.hypot(
                touches[0].clientX - touches[1].clientX,
                touches[0].clientY - touches[1].clientY
            );
        },

        /* ── pan / zoom helpers ─────────────────────── */
        pan(dx, dy) {
            this.originX += dx;
            this.originY += dy;
            this.clampOrigin();
            this.draw();
        },
        zoom(delta, pivotX, pivotY) {
            /* minimum scale: image must always fully cover the selection box */
            const minScale = Math.max(
                this.selW / this.imgNaturalW,
                this.selH / this.imgNaturalH
            );
            const newScale = Math.max(minScale, Math.min(10, this.scale * (1 + delta)));
            const ratio    = newScale / this.scale;

            /* zoom toward the pivot point */
            this.originX = pivotX + ratio * (this.originX - pivotX);
            this.originY = pivotY + ratio * (this.originY - pivotY);
            this.scale   = newScale;
            this.clampOrigin();
            this.draw();
        },

        /* ── actions ────────────────────────────────── */
        resetCrop() {
            this.fitImage();
            this.draw();
        },

        applyCrop() {
            if (!this.img) return;

            /* map the selection rectangle back into image-space */
            const srcX = (this.selX - this.originX) / this.scale;
            const srcY = (this.selY - this.originY) / this.scale;
            const srcW =  this.selW / this.scale;
            const srcH =  this.selH / this.scale;

            const outW = this.targetSize;
            const outH = Math.round(this.targetSize / this.aspectRatio);

            const out = document.createElement('canvas');
            out.width  = outW;
            out.height = outH;
            out.getContext('2d').drawImage(this.img, srcX, srcY, srcW, srcH, 0, 0, outW, outH);

            out.toBlob((blob) => {
                const file = new File([blob], 'crop.jpg', { type: 'image/jpeg' });
                $wire.upload(
                    this.wireModel,
                    file,
                    () => this.close(),
                    () => alert(@js(__('Image upload failed. Please try again.'))),
                    () => {}
                );
            }, 'image/jpeg', 0.9);
        },

        close() {
            this.show = false;
            this.img  = null;
        },
    }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-70 bg-black/70 flex items-center justify-center p-4">

    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                <i class="fas fa-crop mr-2 text-blue-500"></i>{{ __('Adjust Image to Crop') }}
            </h3>
            <button type="button" @click="resetCrop()"
                class="cursor-pointer text-xs font-medium text-zinc-500 dark:text-zinc-400
                       hover:text-blue-600 dark:hover:text-blue-400 inline-flex items-center gap-1.5">
                <i class="fas fa-rotate-left"></i>{{ __('Reset') }}
            </button>
        </div>

        {{-- Canvas --}}
        <div class="p-4">
            <div x-ref="canvasWrap"
                 class="w-full rounded-xl overflow-hidden bg-zinc-900 select-none"
                 style="touch-action: none;">
                <canvas x-ref="canvas" style="display: block; cursor: grab;"></canvas>
            </div>
            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-2 text-center">
                <i class="fas fa-arrows-up-down-left-right mr-1"></i>
                {{ __('Drag to reposition · scroll or pinch to zoom') }}
            </p>
        </div>

        {{-- Footer --}}
        <div class="px-5 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
            <button type="button" @click="close()"
                class="cursor-pointer px-4 py-2 text-sm font-medium rounded-xl
                       border border-zinc-200 dark:border-zinc-600
                       text-zinc-700 dark:text-zinc-300
                       hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <i class="fas fa-times mr-1"></i>{{ __('Cancel') }}
            </button>
            <button type="button" @click="applyCrop()"
                class="cursor-pointer px-4 py-2 text-sm font-semibold rounded-xl
                       bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition-all">
                <i class="fas fa-check mr-1"></i>{{ __('Apply Crop') }}
            </button>
        </div>

    </div>
</div>
