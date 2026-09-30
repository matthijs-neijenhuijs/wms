function initWarehouseMap() {
    const canvas = document.getElementById('warehouse-map-canvas');

    if (!canvas || canvas.dataset.mapInitialized === '1') {
        return;
    }

    canvas.dataset.mapInitialized = '1';

    const dataEl = document.getElementById('warehouse-map-data');

    if (!dataEl) {
        return;
    }

    const data = JSON.parse(dataEl.textContent);
    const ctx = canvas.getContext('2d');

    const CANVAS_W = canvas.width;
    const CANVAS_H = canvas.height;
    const PADDING = 24;
    const MIN_SIZE = 0.1;

    const scale = Math.min(
        (CANVAS_W - PADDING * 2) / data.floorWidth,
        (CANVAS_H - PADDING * 2) / data.floorHeight,
    );

    const toPx = (units) => units * scale;
    const toUnits = (px) => px / scale;

    function getWireComponent() {
        const el = canvas.closest('[wire\\:id]');

        if (!el || !window.Livewire) {
            return null;
        }

        return window.Livewire.find(el.getAttribute('wire:id'));
    }

    function saveLayout(location) {
        const wire = getWireComponent();

        if (!wire) {
            return;
        }

        wire.call('saveLocationLayout', location.id, location.x, location.y, location.width, location.height)
            .catch((error) => console.error('Failed to save warehouse map location layout', error));
    }

    function isPlaced(location) {
        return location.x !== null && location.y !== null && location.width !== null && location.height !== null;
    }

    function placedLocations() {
        return data.locations.filter(isPlaced);
    }

    function unplacedLocations() {
        return data.locations.filter((location) => !isPlaced(location));
    }

    function findLocationById(id) {
        return data.locations.find((location) => location.id === id) || null;
    }

    function clampX(location, x) {
        return Math.max(0, Math.min(x, data.floorWidth - location.width));
    }

    function clampY(location, y) {
        return Math.max(0, Math.min(y, data.floorHeight - location.height));
    }

    function drawArrowhead(fromX, fromY, toX, toY) {
        const angle = Math.atan2(toY - fromY, toX - fromX);
        const headLength = 10;

        ctx.beginPath();
        ctx.moveTo(toX, toY);
        ctx.lineTo(
            toX - (headLength * Math.cos(angle - Math.PI / 6)),
            toY - (headLength * Math.sin(angle - Math.PI / 6)),
        );
        ctx.lineTo(
            toX - (headLength * Math.cos(angle + Math.PI / 6)),
            toY - (headLength * Math.sin(angle + Math.PI / 6)),
        );
        ctx.closePath();
        ctx.fillStyle = '#92400e';
        ctx.fill();
    }

    function centerOf(location) {
        return {
            x: PADDING + toPx(location.x) + (toPx(location.width) / 2),
            y: PADDING + toPx(location.y) + (toPx(location.height) / 2),
        };
    }

    function redraw() {
        ctx.clearRect(0, 0, CANVAS_W, CANVAS_H);

        ctx.strokeStyle = '#9ca3af';
        ctx.lineWidth = 1;
        ctx.strokeRect(PADDING, PADDING, toPx(data.floorWidth), toPx(data.floorHeight));

        const placed = placedLocations().slice().sort((a, b) => a.rank - b.rank);

        if (placed.length > 1) {
            ctx.strokeStyle = '#92400e';
            ctx.lineWidth = 2;
            ctx.beginPath();

            placed.forEach((location, index) => {
                const { x, y } = centerOf(location);

                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });

            ctx.stroke();

            const last = centerOf(placed[placed.length - 1]);
            const secondLast = centerOf(placed[placed.length - 2]);
            drawArrowhead(secondLast.x, secondLast.y, last.x, last.y);
        }

        placed.forEach((location) => {
            const px = PADDING + toPx(location.x);
            const py = PADDING + toPx(location.y);
            const pw = toPx(location.width);
            const ph = toPx(location.height);

            ctx.fillStyle = '#fde68a';
            ctx.strokeStyle = '#92400e';
            ctx.lineWidth = 1.5;
            ctx.fillRect(px, py, pw, ph);
            ctx.strokeRect(px, py, pw, ph);

            ctx.beginPath();
            ctx.arc(px + 10, py + 10, 9, 0, Math.PI * 2);
            ctx.fillStyle = '#92400e';
            ctx.fill();
            ctx.fillStyle = '#ffffff';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(String(location.rank), px + 10, py + 11);

            ctx.fillStyle = '#1f2937';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            let label = location.name;
            const maxWidth = pw - 6;

            while (ctx.measureText(label).width > maxWidth && label.length > 1) {
                label = label.slice(0, -1);
            }

            if (label !== location.name && label.length > 1) {
                label = `${label.slice(0, -1)}…`;
            }

            ctx.fillText(label, px + (pw / 2), py + (ph / 2) + 6);
        });

        renderUnplacedList();
    }

    function renderUnplacedList() {
        const list = document.getElementById('warehouse-map-unplaced-list');

        if (!list) {
            return;
        }

        list.innerHTML = '';

        unplacedLocations().forEach((location) => {
            const li = document.createElement('li');
            li.textContent = location.name;
            li.dataset.locationId = String(location.id);
            li.className = 'cursor-move rounded border border-gray-200 bg-gray-50 px-2 py-1 text-sm dark:border-white/10 dark:bg-gray-800';
            li.style.userSelect = 'none';
            li.style.touchAction = 'none';
            li.addEventListener('pointerdown', onUnplacedPointerDown);
            list.appendChild(li);
        });
    }

    let drag = null;

    function canvasPoint(event) {
        const rect = canvas.getBoundingClientRect();

        return {
            x: ((event.clientX - rect.left) / rect.width) * CANVAS_W,
            y: ((event.clientY - rect.top) / rect.height) * CANVAS_H,
        };
    }

    function hitTest(px, py) {
        const placed = placedLocations();

        for (let i = placed.length - 1; i >= 0; i--) {
            const location = placed[i];
            const x0 = PADDING + toPx(location.x);
            const y0 = PADDING + toPx(location.y);
            const x1 = x0 + toPx(location.width);
            const y1 = y0 + toPx(location.height);

            if (px >= x0 && px <= x1 && py >= y0 && py <= y1) {
                const nearCorner = Math.abs(px - x1) <= 8 && Math.abs(py - y1) <= 8;

                return { location, corner: nearCorner, originPx: x0, originPy: y0 };
            }
        }

        return null;
    }

    canvas.addEventListener('pointerdown', (event) => {
        const point = canvasPoint(event);
        const hit = hitTest(point.x, point.y);

        if (!hit) {
            return;
        }

        canvas.setPointerCapture(event.pointerId);

        if (hit.corner) {
            drag = { type: 'resize', location: hit.location };
        } else {
            drag = {
                type: 'move',
                location: hit.location,
                offsetXUnits: toUnits(point.x - hit.originPx),
                offsetYUnits: toUnits(point.y - hit.originPy),
            };
        }
    });

    canvas.addEventListener('pointermove', (event) => {
        if (!drag) {
            return;
        }

        const point = canvasPoint(event);
        const unitX = toUnits(point.x - PADDING);
        const unitY = toUnits(point.y - PADDING);
        const location = drag.location;

        if (drag.type === 'move') {
            location.x = clampX(location, unitX - drag.offsetXUnits);
            location.y = clampY(location, unitY - drag.offsetYUnits);
        } else if (drag.type === 'resize') {
            const newWidth = Math.min(unitX - location.x, data.floorWidth - location.x);
            const newHeight = Math.min(unitY - location.y, data.floorHeight - location.y);
            location.width = Math.max(MIN_SIZE, newWidth);
            location.height = Math.max(MIN_SIZE, newHeight);
        }

        redraw();
    });

    function endCanvasDrag() {
        if (!drag) {
            return;
        }

        saveLayout(drag.location);
        drag = null;
    }

    canvas.addEventListener('pointerup', endCanvasDrag);
    canvas.addEventListener('pointercancel', () => {
        drag = null;
    });

    function onUnplacedPointerDown(event) {
        const id = parseInt(event.currentTarget.dataset.locationId, 10);
        const location = findLocationById(id);

        if (!location) {
            return;
        }

        const defaultWidth = Math.min(1, data.floorWidth);
        const defaultHeight = Math.min(1, data.floorHeight);

        const onMove = (moveEvent) => {
            const rect = canvas.getBoundingClientRect();
            const overCanvas = moveEvent.clientX >= rect.left && moveEvent.clientX <= rect.right
                && moveEvent.clientY >= rect.top && moveEvent.clientY <= rect.bottom;

            if (overCanvas) {
                const px = ((moveEvent.clientX - rect.left) / rect.width) * CANVAS_W;
                const py = ((moveEvent.clientY - rect.top) / rect.height) * CANVAS_H;

                location.width = defaultWidth;
                location.height = defaultHeight;
                location.x = clampX(location, toUnits(px - PADDING) - (defaultWidth / 2));
                location.y = clampY(location, toUnits(py - PADDING) - (defaultHeight / 2));
            } else {
                location.x = null;
                location.y = null;
                location.width = null;
                location.height = null;
            }

            redraw();
        };

        const onUp = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);

            if (isPlaced(location)) {
                saveLayout(location);
            }

            redraw();
        };

        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
    }

    redraw();
}

initWarehouseMap();
document.addEventListener('livewire:navigated', initWarehouseMap);
