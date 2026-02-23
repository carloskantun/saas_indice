// /modules/processes_tasks/js/organigrama-controls.js
console.log('[Organigrama] Controls JS loaded');

(function () {
    'use strict';

    // Zoom controls
    let currentZoom = 1;
    const zoomStep = 0.1;
    const minZoom = 0.3;
    const maxZoom = 3;

    const canvas = document.getElementById('org-canvas');
    const canvasWrap = document.getElementById('org-canvas-wrap');

    if (!canvas || !canvasWrap) {
        console.warn('[Organigrama Controls] Canvas elements not found');
        return;
    }

    // Zoom in function
    function zoomIn() {
        if (currentZoom < maxZoom) {
            currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
            applyZoom();
        }
    }

    // Zoom out function
    function zoomOut() {
        if (currentZoom > minZoom) {
            currentZoom = Math.max(currentZoom - zoomStep, minZoom);
            applyZoom();
        }
    }

    // Apply zoom transformation
    function applyZoom() {
        canvas.style.transform = `scale(${currentZoom})`;
        canvas.style.transformOrigin = 'center center';

        // Update SVG overlay if it exists
        const svg = document.getElementById('org-lines');
        if (svg) {
            svg.style.transform = `scale(${currentZoom})`;
            svg.style.transformOrigin = 'center center';
        }
    }

    // Center canvas function
    function centerCanvas() {
        if (canvasWrap.scrollTo) {
            const centerX = (canvasWrap.scrollWidth - canvasWrap.clientWidth) / 2;
            const centerY = (canvasWrap.scrollHeight - canvasWrap.clientHeight) / 2;
            canvasWrap.scrollTo({
                left: centerX,
                top: centerY,
                behavior: 'smooth'
            });
        }
    }

    // Auto layout function
    function autoLayout() {
        const nodes = canvas.querySelectorAll('.org-node');
        if (nodes.length === 0) return;

        // Group nodes by level or assign levels if not set
        const nodesByLevel = {};
        const LEVEL_HEIGHT = 160;
        const NODE_SPACING = 180;
        const START_X = 100;

        nodes.forEach((node, index) => {
            let level = parseInt(node.dataset.level || '1', 10);
            if (!level || level < 1) {
                // Assign level based on position or order
                level = Math.floor(index / 3) + 1; // 3 nodes per level max
                node.dataset.level = level.toString();
            }

            if (!nodesByLevel[level]) {
                nodesByLevel[level] = [];
            }
            nodesByLevel[level].push(node);
        });

        // Position nodes within their levels
        Object.keys(nodesByLevel).forEach(levelKey => {
            const level = parseInt(levelKey);
            const levelNodes = nodesByLevel[level];
            const y = (level - 1) * LEVEL_HEIGHT + Math.round(LEVEL_HEIGHT * 0.4);

            levelNodes.forEach((node, indexInLevel) => {
                const totalNodesInLevel = levelNodes.length;
                const totalWidth = (totalNodesInLevel - 1) * NODE_SPACING;
                const startX = START_X + Math.max(0, (600 - totalWidth) / 2); // Center horizontally

                const x = startX + (indexInLevel * NODE_SPACING);

                node.style.left = x + 'px';
                node.style.top = y + 'px';

                // Trigger node moved event for level system
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('org:nodeMoved', {
                        detail: { id: node.dataset.id }
                    }));
                }, 50);
            });
        });

        // Trigger redraw of connections if there's a redraw function
        setTimeout(() => {
            if (window.redrawConnections) {
                window.redrawConnections();
            }
        }, 100);

        // Show success message
        if (window.showToast) {
            window.showToast('Organigrama reorganizado por niveles', 'success');
        }
    }    // Reset organigrama function
    function resetOrganigrama() {
        if (confirm('¿Estás seguro de que quieres reiniciar el organigrama? Se perderán todas las posiciones y conexiones.')) {
            // Clear canvas
            canvas.innerHTML = '';

            // Clear SVG lines
            const svg = document.getElementById('org-lines');
            if (svg) {
                svg.innerHTML = '';
            }

            // Clear localStorage
            const companyId = window.IX_CTX?.companyId || 0;
            const keys = [
                `org_nodes_v1_${companyId}`,
                `org_rels_v1_${companyId}`,
                `org_levels_${companyId}`,
                'org_nodes_v1',
                'org_rels_v1'
            ];

            keys.forEach(key => {
                try {
                    localStorage.removeItem(key);
                } catch (e) {
                    console.warn('Could not remove localStorage key:', key);
                }
            });

            // Reset zoom
            currentZoom = 1;
            applyZoom();

            // Show success message
            if (window.showToast) {
                window.showToast('Organigrama reiniciado exitosamente', 'info');
            }
        }
    }

    // Bind events to floating controls
    document.addEventListener('DOMContentLoaded', function () {
        // Zoom controls
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', zoomIn);
        }

        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', zoomOut);
        }

        // Center control
        const centerBtn = document.getElementById('center-canvas');
        if (centerBtn) {
            centerBtn.addEventListener('click', centerCanvas);
        }

        // Auto layout controls (both floating and toolbar)
        const autoLayoutBtns = document.querySelectorAll('#auto-layout, #auto-layout-float');
        autoLayoutBtns.forEach(btn => {
            btn.addEventListener('click', autoLayout);
        });

        // Reset controls (both floating and toolbar)
        const resetBtns = document.querySelectorAll('#reset-org, #reset-org-float');
        resetBtns.forEach(btn => {
            btn.addEventListener('click', resetOrganigrama);
        });

        // Clear organigrama
        const clearBtn = document.getElementById('org-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (confirm('¿Limpiar el organigrama actual?')) {
                    canvas.innerHTML = '';
                    const svg = document.getElementById('org-lines');
                    if (svg) svg.innerHTML = '';

                    if (window.showToast) {
                        window.showToast('Organigrama limpiado', 'info');
                    }
                }
            });
        }

        // Help modal
        const helpBtn = document.getElementById('org-help');
        if (helpBtn) {
            helpBtn.addEventListener('click', function () {
                const modal = document.getElementById('org-onboarding');
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            });
        }

        // Mouse wheel zoom
        if (canvasWrap) {
            canvasWrap.addEventListener('wheel', function (e) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();

                    if (e.deltaY < 0) {
                        zoomIn();
                    } else {
                        zoomOut();
                    }
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Only activate when organigrama tab is active
            if (!document.body.dataset.tab || document.body.dataset.tab !== 'organigrama') {
                return;
            }

            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case '+':
                    case '=':
                        e.preventDefault();
                        zoomIn();
                        break;
                    case '-':
                        e.preventDefault();
                        zoomOut();
                        break;
                    case '0':
                        e.preventDefault();
                        currentZoom = 1;
                        applyZoom();
                        break;
                }
            }

            // Other shortcuts
            switch (e.key) {
                case 'c':
                    if (e.altKey) {
                        e.preventDefault();
                        centerCanvas();
                    }
                    break;
                case 'a':
                    if (e.altKey) {
                        e.preventDefault();
                        autoLayout();
                    }
                    break;
            }
        });
    });

    // Export functions to global scope for other scripts
    window.orgControls = {
        zoomIn,
        zoomOut,
        centerCanvas,
        autoLayout,
        resetOrganigrama,
        getCurrentZoom: () => currentZoom,
        setZoom: (zoom) => {
            currentZoom = Math.max(minZoom, Math.min(maxZoom, zoom));
            applyZoom();
        }
    };

})();
