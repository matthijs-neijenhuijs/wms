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

    const unplacedList = document.getElementById('warehouse-map-unplaced-list');
    const unplacedEmpty = document.getElementById('warehouse-map-unplaced-empty');
    const selectionPanel = document.getElementById('warehouse-map-selection');
    const selectionName = document.getElementById('warehouse-map-selection-name');
    const selectionRank = document.getElementById('warehouse-map-selection-rank');
    const removeButton = document.getElementById('warehouse-map-remove-button');
    const routeList = document.getElementById('warehouse-map-route-list');
    const routeEmpty = document.getElementById('warehouse-map-route-empty');
    const routeEditing = document.getElementById('warehouse-map-route-editing');
    const routeViewActions = document.getElementById('warehouse-map-route-view-actions');
    const routeEditActions = document.getElementById('warehouse-map-route-edit-actions');
    const routeEditButton = document.getElementById('warehouse-map-route-edit');
    const routeSaveButton = document.getElementById('warehouse-map-route-save');
    const routeUndoButton = document.getElementById('warehouse-map-route-undo');
    const routeCancelButton = document.getElementById('warehouse-map-route-cancel');

    const CANVAS_W = canvas.width;
    const CANVAS_H = canvas.height;
    const PADDING = 24;
    const MIN_SIZE = 0.1;
    const DEFAULT_SIZE_PX = 60;
    const HANDLE_SIZE = 10;
    const DELETE_RADIUS = 10;

    const scale = Math.min(
        (CANVAS_W - PADDING * 2) / data.floorWidth,
        (CANVAS_H - PADDING * 2) / data.floorHeight,
    );

    const toPx = (units) => units * scale;
    const toUnits = (px) => px / scale;

    let selectedId = null;
    let drag = null;
    let isEditingRoute = false;
    let routeDraft = [];

    function cssColor(variable, fallback) {
        const value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();

        return value || fallback;
    }

    function palette() {
        const isDark = document.documentElement.classList.contains('dark');

        return {
            floorFill: isDark ? '#111827' : '#ffffff',
            floorStroke: isDark ? '#4b5563' : '#9ca3af',
            grid: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)',
            boxFill: '#fde68a',
            boxStroke: '#92400e',
            label: '#1f2937',
            route: '#92400e',
            selected: cssColor('--primary-500', '#f59e0b'),
            danger: cssColor('--danger-600', '#dc2626'),
        };
    }

    function getWireComponent() {
        const el = canvas.closest('[wire\\:id]');

        if (!el || !window.Livewire) {
            return null;
        }

        return window.Livewire.find(el.getAttribute('wire:id'));
    }

    function callWire(method, ...args) {
        const wire = getWireComponent();

        if (!wire) {
            return Promise.reject(new Error('Livewire component not found'));
        }

        return wire.call(method, ...args)
            .catch((error) => {
                console.error(`Warehouse map: ${method} failed`, error);

                throw error;
            });
    }

    function saveLayout(location) {
        callWire('saveLocationLayout', location.id, location.x, location.y, location.width, location.height).catch(() => {});
    }

    function isPlaced(location) {
        return location.x !== null && location.y !== null && location.width !== null && location.height !== null;
    }

    function placedLocations() {
        return data.locations.filter(isPlaced);
    }

    function findLocationById(id) {
        return data.locations.find((location) => location.id === id) || null;
    }

    function selectedLocation() {
        const location = selectedId === null ? null : findLocationById(selectedId);

        return location && isPlaced(location) ? location : null;
    }

    function clampX(location, x) {
        return Math.max(0, Math.min(x, data.floorWidth - location.width));
    }

    function clampY(location, y) {
        return Math.max(0, Math.min(y, data.floorHeight - location.height));
    }

    function boxOf(location) {
        const x0 = PADDING + toPx(location.x);
        const y0 = PADDING + toPx(location.y);

        return { x0, y0, x1: x0 + toPx(location.width), y1: y0 + toPx(location.height) };
    }

    function centerOf(location) {
        const box = boxOf(location);

        return { x: (box.x0 + box.x1) / 2, y: (box.y0 + box.y1) / 2 };
    }

    function removeFromMap(location) {
        location.x = null;
        location.y = null;
        location.width = null;
        location.height = null;

        if (selectedId === location.id) {
            selectedId = null;
        }

        callWire('removeLocationFromMap', location.id).catch(() => {});
        redraw();
    }

    function drawArrowhead(fromX, fromY, toX, toY, color) {
        const angle = Math.atan2(toY - fromY, toX - fromX);
        const headLength = 10;

        ctx.beginPath();
        ctx.moveTo(toX, toY);
        ctx.lineTo(toX - (headLength * Math.cos(angle - Math.PI / 6)), toY - (headLength * Math.sin(angle - Math.PI / 6)));
        ctx.lineTo(toX - (headLength * Math.cos(angle + Math.PI / 6)), toY - (headLength * Math.sin(angle + Math.PI / 6)));
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
    }

    function drawFloor(colors) {
        const floorW = toPx(data.floorWidth);
        const floorH = toPx(data.floorHeight);

        ctx.fillStyle = colors.floorFill;
        ctx.fillRect(PADDING, PADDING, floorW, floorH);

        const gridStep = Math.max(1, Math.pow(10, Math.floor(Math.log10(data.floorWidth / 5))));

        ctx.strokeStyle = colors.grid;
        ctx.lineWidth = 1;
        ctx.beginPath();

        for (let units = gridStep; units < data.floorWidth; units += gridStep) {
            ctx.moveTo(PADDING + toPx(units), PADDING);
            ctx.lineTo(PADDING + toPx(units), PADDING + floorH);
        }

        for (let units = gridStep; units < data.floorHeight; units += gridStep) {
            ctx.moveTo(PADDING, PADDING + toPx(units));
            ctx.lineTo(PADDING + floorW, PADDING + toPx(units));
        }

        ctx.stroke();

        ctx.strokeStyle = colors.floorStroke;
        ctx.strokeRect(PADDING, PADDING, floorW, floorH);
    }

    function routeLocations() {
        if (isEditingRoute) {
            return routeDraft.map(findLocationById).filter((location) => location && isPlaced(location));
        }

        return placedLocations().slice().sort((a, b) => a.rank - b.rank);
    }

    function badgeNumberFor(location) {
        if (!isEditingRoute) {
            return location.rank;
        }

        const position = routeDraft.indexOf(location.id);

        return position === -1 ? null : position + 1;
    }

    function drawRouteBanner(colors) {
        const text = canvas.dataset.routeHint || '';

        ctx.font = '13px sans-serif';
        const width = ctx.measureText(text).width + 24;

        ctx.fillStyle = colors.selected;
        ctx.fillRect((CANVAS_W - width) / 2, 2, width, 20);
        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, CANVAS_W / 2, 12);
    }

    function drawRoute(placed, colors) {
        if (placed.length < 2) {
            return;
        }

        ctx.strokeStyle = colors.route;
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 4]);
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
        ctx.setLineDash([]);

        const last = centerOf(placed[placed.length - 1]);
        const secondLast = centerOf(placed[placed.length - 2]);
        drawArrowhead(secondLast.x, secondLast.y, last.x, last.y, colors.route);
    }

    function drawLocation(location, colors) {
        const { x0, y0, x1, y1 } = boxOf(location);
        const width = x1 - x0;
        const height = y1 - y0;
        const isSelected = !isEditingRoute && location.id === selectedId;
        const badgeNumber = badgeNumberFor(location);

        ctx.globalAlpha = isEditingRoute && badgeNumber === null ? 0.4 : 1;
        ctx.fillStyle = colors.boxFill;
        ctx.strokeStyle = isSelected ? colors.selected : colors.boxStroke;
        ctx.lineWidth = isSelected ? 3 : 1.5;
        ctx.fillRect(x0, y0, width, height);
        ctx.strokeRect(x0, y0, width, height);

        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        if (badgeNumber !== null) {
            ctx.beginPath();
            ctx.arc(x0 + 10, y0 + 10, 9, 0, Math.PI * 2);
            ctx.fillStyle = isEditingRoute ? colors.selected : colors.boxStroke;
            ctx.fill();
            ctx.fillStyle = '#ffffff';
            ctx.font = '10px sans-serif';
            ctx.fillText(String(badgeNumber), x0 + 10, y0 + 11);
        }

        ctx.fillStyle = colors.label;
        ctx.font = '12px sans-serif';

        let label = location.name;

        while (ctx.measureText(label).width > width - 6 && label.length > 1) {
            label = label.slice(0, -1);
        }

        if (label !== location.name && label.length > 1) {
            label = `${label.slice(0, -1)}…`;
        }

        ctx.fillText(label, x0 + (width / 2), y0 + (height / 2) + 6);
        ctx.globalAlpha = 1;

        if (!isSelected) {
            return;
        }

        ctx.fillStyle = colors.selected;
        ctx.fillRect(x1 - HANDLE_SIZE / 2, y1 - HANDLE_SIZE / 2, HANDLE_SIZE, HANDLE_SIZE);

        ctx.beginPath();
        ctx.arc(x1, y0, DELETE_RADIUS, 0, Math.PI * 2);
        ctx.fillStyle = colors.danger;
        ctx.fill();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(x1 - 4, y0 - 4);
        ctx.lineTo(x1 + 4, y0 + 4);
        ctx.moveTo(x1 + 4, y0 - 4);
        ctx.lineTo(x1 - 4, y0 + 4);
        ctx.stroke();
    }

    function redraw() {
        const colors = palette();

        ctx.clearRect(0, 0, CANVAS_W, CANVAS_H);
        drawFloor(colors);

        drawRoute(routeLocations(), colors);
        placedLocations().filter((location) => location.id !== selectedId).forEach((location) => drawLocation(location, colors));

        const selected = selectedLocation();

        if (selected) {
            drawLocation(selected, colors);
        }

        if (isEditingRoute) {
            drawRouteBanner(colors);
        }

        updateSidebar();
        renderRouteList();
    }

    function updateSidebar() {
        let unplacedCount = 0;

        unplacedList?.querySelectorAll('[data-location-id]').forEach((item) => {
            const location = findLocationById(parseInt(item.dataset.locationId, 10));
            const placed = !location || isPlaced(location);

            item.hidden = placed;

            if (!placed) {
                unplacedCount++;
            }
        });

        if (unplacedEmpty) {
            unplacedEmpty.hidden = unplacedCount > 0;
        }

        const selected = isEditingRoute ? null : selectedLocation();

        if (selectionPanel) {
            selectionPanel.hidden = !selected;
        }

        if (selected) {
            selectionName.textContent = selected.name;
            selectionRank.textContent = String(selected.rank);
        }
    }

    function renderRouteList() {
        if (!routeList) {
            return;
        }

        routeList.innerHTML = '';

        const ordered = routeLocations();
        const pending = isEditingRoute
            ? placedLocations().filter((location) => !routeDraft.includes(location.id)).sort((a, b) => a.rank - b.rank)
            : [];

        [...ordered, ...pending].forEach((location, index) => {
            const isPending = index >= ordered.length;
            const item = document.createElement('li');
            const badge = document.createElement('span');
            const name = document.createElement('span');

            item.className = isPending ? 'wm-route-item wm-route-pending' : 'wm-route-item';
            badge.className = 'wm-rank';
            badge.textContent = isPending ? '–' : String(isEditingRoute ? index + 1 : location.rank);
            name.className = 'wm-item-name';
            name.textContent = location.name;

            item.append(badge, name);
            routeList.appendChild(item);
        });

        if (routeEmpty) {
            routeEmpty.hidden = ordered.length + pending.length > 0;
        }

        if (routeEditing) {
            routeEditing.hidden = !isEditingRoute;
        }

        if (routeViewActions) {
            routeViewActions.hidden = isEditingRoute;
        }

        if (routeEditActions) {
            routeEditActions.hidden = !isEditingRoute;
        }

        if (routeEditButton) {
            routeEditButton.disabled = placedLocations().length < 2;
        }

        if (routeSaveButton) {
            routeSaveButton.disabled = routeDraft.length === 0;
        }

        if (routeUndoButton) {
            routeUndoButton.disabled = routeDraft.length === 0;
        }
    }

    function startRouteEditing() {
        isEditingRoute = true;
        routeDraft = [];
        selectedId = null;
        drag = null;
        canvas.focus();
        redraw();
    }

    function stopRouteEditing() {
        isEditingRoute = false;
        routeDraft = [];
        redraw();
    }

    function applyRanks(ranks) {
        data.locations.forEach((location) => {
            const rank = ranks[location.id];

            if (rank !== undefined) {
                location.rank = rank;
            }
        });

        unplacedList?.querySelectorAll('[data-location-id]').forEach((item) => {
            const location = findLocationById(parseInt(item.dataset.locationId, 10));
            const label = item.querySelector('[data-rank-label]');

            if (location && label) {
                label.textContent = String(location.rank);
            }
        });
    }

    function saveRoute() {
        if (routeDraft.length === 0) {
            return;
        }

        routeSaveButton.disabled = true;

        callWire('saveRoute', routeDraft)
            .then((ranks) => {
                applyRanks(ranks || {});
                stopRouteEditing();
            })
            .catch(() => {
                routeSaveButton.disabled = false;
            });
    }

    function onRouteCanvasClick(point) {
        const hit = hitTest(point.x, point.y);

        if (!hit) {
            return;
        }

        const id = hit.location.id;

        if (routeDraft[routeDraft.length - 1] === id) {
            routeDraft.pop();
        } else if (!routeDraft.includes(id)) {
            routeDraft.push(id);
        }

        redraw();
    }

    function canvasPoint(event) {
        const rect = canvas.getBoundingClientRect();

        return {
            x: ((event.clientX - rect.left) / rect.width) * CANVAS_W,
            y: ((event.clientY - rect.top) / rect.height) * CANVAS_H,
        };
    }

    function hitTest(px, py) {
        const selected = selectedLocation();

        if (selected) {
            const { x0, y0, x1, y1 } = boxOf(selected);

            if (Math.hypot(px - x1, py - y0) <= DELETE_RADIUS + 2) {
                return { location: selected, action: 'delete' };
            }

            if (Math.abs(px - x1) <= HANDLE_SIZE && Math.abs(py - y1) <= HANDLE_SIZE) {
                return { location: selected, action: 'resize' };
            }

            if (px >= x0 && px <= x1 && py >= y0 && py <= y1) {
                return { location: selected, action: 'move', originPx: x0, originPy: y0 };
            }
        }

        const placed = placedLocations();

        for (let i = placed.length - 1; i >= 0; i--) {
            const location = placed[i];
            const { x0, y0, x1, y1 } = boxOf(location);

            if (px >= x0 && px <= x1 && py >= y0 && py <= y1) {
                const nearCorner = Math.abs(px - x1) <= HANDLE_SIZE && Math.abs(py - y1) <= HANDLE_SIZE;

                return { location, action: nearCorner ? 'resize' : 'move', originPx: x0, originPy: y0 };
            }
        }

        return null;
    }

    function cursorFor(hit) {
        if (!hit) {
            return 'default';
        }

        return { delete: 'pointer', resize: 'nwse-resize', move: 'move' }[hit.action];
    }

    canvas.addEventListener('pointerdown', (event) => {
        canvas.focus();

        const point = canvasPoint(event);

        if (isEditingRoute) {
            onRouteCanvasClick(point);

            return;
        }

        const hit = hitTest(point.x, point.y);

        if (!hit) {
            selectedId = null;
            redraw();

            return;
        }

        if (hit.action === 'delete') {
            removeFromMap(hit.location);

            return;
        }

        selectedId = hit.location.id;
        canvas.setPointerCapture(event.pointerId);

        drag = hit.action === 'resize'
            ? { type: 'resize', location: hit.location, moved: false }
            : {
                type: 'move',
                location: hit.location,
                moved: false,
                offsetXUnits: toUnits(point.x - hit.originPx),
                offsetYUnits: toUnits(point.y - hit.originPy),
            };

        redraw();
    });

    canvas.addEventListener('pointermove', (event) => {
        const point = canvasPoint(event);

        if (!drag) {
            const hit = hitTest(point.x, point.y);

            canvas.style.cursor = isEditingRoute ? (hit ? 'pointer' : 'default') : cursorFor(hit);

            return;
        }

        const unitX = toUnits(point.x - PADDING);
        const unitY = toUnits(point.y - PADDING);
        const location = drag.location;

        if (drag.type === 'move') {
            location.x = clampX(location, unitX - drag.offsetXUnits);
            location.y = clampY(location, unitY - drag.offsetYUnits);
        } else {
            location.width = Math.max(MIN_SIZE, Math.min(unitX - location.x, data.floorWidth - location.x));
            location.height = Math.max(MIN_SIZE, Math.min(unitY - location.y, data.floorHeight - location.y));
        }

        drag.moved = true;
        redraw();
    });

    canvas.addEventListener('pointerup', () => {
        if (drag?.moved) {
            saveLayout(drag.location);
        }

        drag = null;
    });

    canvas.addEventListener('pointercancel', () => {
        drag = null;
    });

    canvas.addEventListener('keydown', (event) => {
        if (isEditingRoute) {
            if (event.key === 'Escape') {
                stopRouteEditing();
            } else if (event.key === 'Backspace' || event.key === 'Delete') {
                event.preventDefault();
                routeDraft.pop();
                redraw();
            }

            return;
        }

        const selected = selectedLocation();

        if (!selected) {
            return;
        }

        if (event.key === 'Delete' || event.key === 'Backspace') {
            event.preventDefault();
            removeFromMap(selected);
        } else if (event.key === 'Escape') {
            selectedId = null;
            redraw();
        }
    });

    removeButton?.addEventListener('click', () => {
        const selected = selectedLocation();

        if (selected) {
            removeFromMap(selected);
        }
    });

    routeEditButton?.addEventListener('click', startRouteEditing);
    routeSaveButton?.addEventListener('click', saveRoute);
    routeCancelButton?.addEventListener('click', stopRouteEditing);
    routeUndoButton?.addEventListener('click', () => {
        routeDraft.pop();
        redraw();
    });

    function onUnplacedPointerDown(event) {
        if (isEditingRoute) {
            return;
        }

        const item = event.currentTarget;
        const location = findLocationById(parseInt(item.dataset.locationId, 10));

        if (!location) {
            return;
        }

        event.preventDefault();
        item.classList.add('wm-item-dragging');

        const defaultWidth = Math.min(toUnits(DEFAULT_SIZE_PX), data.floorWidth);
        const defaultHeight = Math.min(toUnits(DEFAULT_SIZE_PX), data.floorHeight);

        const onMove = (moveEvent) => {
            const rect = canvas.getBoundingClientRect();
            const overCanvas = moveEvent.clientX >= rect.left && moveEvent.clientX <= rect.right
                && moveEvent.clientY >= rect.top && moveEvent.clientY <= rect.bottom;

            if (overCanvas) {
                const point = canvasPoint(moveEvent);

                location.width = defaultWidth;
                location.height = defaultHeight;
                location.x = clampX(location, toUnits(point.x - PADDING) - (defaultWidth / 2));
                location.y = clampY(location, toUnits(point.y - PADDING) - (defaultHeight / 2));
                selectedId = location.id;
            } else {
                location.x = null;
                location.y = null;
                location.width = null;
                location.height = null;
            }

            redraw();
            item.hidden = false;
        };

        const onUp = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            item.classList.remove('wm-item-dragging');

            if (isPlaced(location)) {
                saveLayout(location);
                canvas.focus();
            } else if (selectedId === location.id) {
                selectedId = null;
            }

            redraw();
        };

        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onUp);
    }

    unplacedList?.querySelectorAll('[data-location-id]').forEach((item) => {
        item.addEventListener('pointerdown', onUnplacedPointerDown);
    });

    redraw();
}

initWarehouseMap();
document.addEventListener('livewire:navigated', initWarehouseMap);
