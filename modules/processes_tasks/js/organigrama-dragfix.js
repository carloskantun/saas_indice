// /modules/processes_tasks/js/organigrama-dragfix.js
// Fixes para drag & drop en el organigrama

(function () {
    'use strict';

    console.log('[Organigrama] Drag fixes loaded');

    // Funciones de utilidad
    function getCanvas() {
        return document.getElementById('org-canvas');
    }

    function getPeopleList() {
        return document.getElementById('org-people-list');
    }

    // Configurar drag & drop cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function () {
        setupDragAndDrop();
    });

    function setupDragAndDrop() {
        const canvas = getCanvas();
        const peopleList = getPeopleList();

        if (!canvas || !peopleList) {
            console.warn('[Organigrama] Canvas o people list no encontrados, reintentando...');
            setTimeout(setupDragAndDrop, 500);
            return;
        }

        // Configurar eventos para las personas en la lista
        setupPeopleListDragEvents();

        // Configurar eventos para el canvas
        setupCanvasDragEvents();

        // Configurar eventos para nodos existentes
        setupNodeDragEvents();

        console.log('[Organigrama] Drag & drop configurado exitosamente');
    }

    function setupPeopleListDragEvents() {
        const peopleList = getPeopleList();
        if (!peopleList) return;

        // Usar delegación de eventos para manejar elementos dinámicos
        peopleList.addEventListener('dragstart', function (e) {
            const userElement = e.target.closest('.org-user');
            if (!userElement || userElement.getAttribute('draggable') !== 'true') {
                e.preventDefault();
                return;
            }

            // Datos a transferir
            const userData = {
                id: userElement.getAttribute('data-id'),
                name: userElement.getAttribute('data-name'),
                position: userElement.getAttribute('data-position'),
                unit: userElement.getAttribute('data-unit'),
                department: userElement.getAttribute('data-department')
            };

            e.dataTransfer.setData('text/plain', JSON.stringify(userData));
            e.dataTransfer.effectAllowed = 'copy';

            // Añadir clase visual
            userElement.classList.add('dragging');

            console.log('[Organigrama] Iniciando drag para usuario:', userData.name);
        });

        peopleList.addEventListener('dragend', function (e) {
            const userElement = e.target.closest('.org-user');
            if (userElement) {
                userElement.classList.remove('dragging');
            }
        });
    }

    function setupCanvasDragEvents() {
        const canvas = getCanvas();
        if (!canvas) return;

        canvas.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            canvas.classList.add('drag-over');
        });

        canvas.addEventListener('dragenter', function (e) {
            e.preventDefault();
            canvas.classList.add('drag-active');
        });

        canvas.addEventListener('dragleave', function (e) {
            // Solo remover si realmente salimos del canvas
            if (!canvas.contains(e.relatedTarget)) {
                canvas.classList.remove('drag-over', 'drag-active');
            }
        });

        canvas.addEventListener('drop', function (e) {
            e.preventDefault();
            canvas.classList.remove('drag-over', 'drag-active');

            try {
                const userData = JSON.parse(e.dataTransfer.getData('text/plain'));

                // Verificar si el nodo ya existe
                const existingNode = canvas.querySelector(`[data-id="${userData.id}"]`);
                if (existingNode) {
                    console.warn('[Organigrama] El usuario ya está en el canvas');
                    if (window.showToast) {
                        window.showToast('Este usuario ya está en el organigrama', 'warning');
                    }
                    return;
                }

                // Calcular posición relativa al canvas
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left + canvas.scrollLeft;
                const y = e.clientY - rect.top + canvas.scrollTop;

                // Crear nodo en el canvas
                createOrgNode(userData, x, y);

                console.log('[Organigrama] Usuario agregado al canvas:', userData.name);

                if (window.showToast) {
                    window.showToast(`${userData.name} agregado al organigrama`, 'success');
                }
            } catch (error) {
                console.error('[Organigrama] Error procesando drop:', error);
                if (window.showToast) {
                    window.showToast('Error al agregar usuario al organigrama', 'danger');
                }
            }
        });
    }

    function createOrgNode(userData, x, y) {
        const canvas = getCanvas();
        if (!canvas) return;

        // Calcular nivel basado en la posición Y
        const LEVEL_HEIGHT = 160;
        let level = Math.max(1, Math.ceil((y + (LEVEL_HEIGHT / 3)) / LEVEL_HEIGHT));

        // Ajustar Y para que se alinee con el nivel
        const adjustedY = (level - 1) * LEVEL_HEIGHT + Math.round(LEVEL_HEIGHT * 0.4);

        // Crear elemento del nodo
        const node = document.createElement('div');
        node.className = 'org-node';
        node.setAttribute('data-id', userData.id);
        node.setAttribute('data-level', level.toString());
        node.setAttribute('data-status', 'active');
        node.style.left = Math.max(0, x - 80) + 'px'; // Centrar en el cursor
        node.style.top = Math.max(0, adjustedY) + 'px';

        // Avatar
        const avatar = document.createElement('div');
        avatar.className = 'avatar node-avatar';
        avatar.textContent = (userData.name || '').substring(0, 1).toUpperCase() || '?';

        // Nombre
        const name = document.createElement('div');
        name.className = 'node-name';
        name.textContent = userData.name || 'Sin nombre';

        // Posición
        const position = document.createElement('div');
        position.className = 'node-position';
        position.textContent = userData.position || 'Sin puesto';

        // Unidad
        const unit = document.createElement('div');
        unit.className = 'node-unit';
        unit.textContent = userData.unit || '';

        // Botón de conexión
        const connectBtn = document.createElement('button');
        connectBtn.className = 'connect-btn';
        connectBtn.innerHTML = '●';
        connectBtn.title = 'Conectar con otro nodo';
        connectBtn.setAttribute('aria-label', 'Conectar nodo');

        // Ensamblar nodo
        node.appendChild(avatar);
        node.appendChild(name);
        node.appendChild(position);
        if (unit.textContent) {
            node.appendChild(unit);
        }
        node.appendChild(connectBtn);

        // Añadir al canvas
        canvas.appendChild(node);

        // Configurar eventos del nodo
        setupSingleNodeEvents(node);

        // Guardar posición y nivel
        saveNodePosition(userData.id, x, adjustedY);
        saveNodeLevel(userData.id, level);

        // Disparar evento para actualizar el sistema de niveles
        setTimeout(() => {
            document.dispatchEvent(new CustomEvent('org:nodeMoved', {
                detail: { id: userData.id }
            }));
        }, 50);
    }

    function setupSingleNodeEvents(node) {
        // Hacer el nodo draggable dentro del canvas
        node.setAttribute('draggable', 'true');

        let isDragging = false;
        let startX, startY;

        node.addEventListener('dragstart', function (e) {
            isDragging = true;
            const rect = node.getBoundingClientRect();
            startX = e.clientX - rect.left;
            startY = e.clientY - rect.top;

            node.classList.add('dragging');
            e.dataTransfer.setData('text/html', node.outerHTML);
            e.dataTransfer.setData('orgNodeId', node.getAttribute('data-id'));
            e.dataTransfer.effectAllowed = 'move';
        });

        node.addEventListener('dragend', function (e) {
            node.classList.remove('dragging');
            isDragging = false;
        });

        // Click para seleccionar/conectar
        node.addEventListener('click', function (e) {
            if (isDragging) return;
            e.stopPropagation();

            handleNodeSelection(node);
        });

        // Botón de conexión
        const connectBtn = node.querySelector('.connect-btn');
        if (connectBtn) {
            connectBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleNodeConnection(node);
            });
        }
    }

    function handleNodeSelection(node) {
        const canvas = getCanvas();
        if (!canvas) return;

        const nodeId = node.getAttribute('data-id');
        const selectedNodes = canvas.querySelectorAll('.org-node.selected');

        if (selectedNodes.length === 0) {
            // Primer nodo seleccionado
            node.classList.add('selected');
            console.log('[Organigrama] Nodo seleccionado para conexión:', nodeId);

            if (window.showToast) {
                window.showToast('Nodo seleccionado. Haz clic en otro nodo para conectar.', 'info');
            }
        } else if (selectedNodes.length === 1 && !node.classList.contains('selected')) {
            // Segundo nodo - crear conexión
            const sourceNode = selectedNodes[0];
            const sourceId = sourceNode.getAttribute('data-id');
            const targetId = nodeId;

            createConnection(sourceId, targetId);

            // Limpiar selecciones
            selectedNodes.forEach(n => n.classList.remove('selected'));
        } else {
            // Deseleccionar todo
            selectedNodes.forEach(n => n.classList.remove('selected'));

            if (window.showToast) {
                window.showToast('Selección cancelada', 'secondary');
            }
        }
    } function setupNodeDragEvents() {
        const canvas = getCanvas();
        if (!canvas) return;

        // Permitir drop de nodos dentro del canvas
        canvas.addEventListener('drop', function (e) {
            // Si es un nodo siendo movido dentro del canvas
            const draggedElement = document.querySelector('.org-node.dragging');
            if (draggedElement) {
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left + canvas.scrollLeft;
                const y = e.clientY - rect.top + canvas.scrollTop;

                // Calcular nivel basado en la posición Y
                const LEVEL_HEIGHT = 160;
                let level = Math.max(1, Math.ceil((y + (LEVEL_HEIGHT / 3)) / LEVEL_HEIGHT));

                // Ajustar Y para que se alinee con el nivel
                const adjustedY = (level - 1) * LEVEL_HEIGHT + Math.round(LEVEL_HEIGHT * 0.4);

                draggedElement.style.left = Math.max(0, x - 80) + 'px';
                draggedElement.style.top = Math.max(0, adjustedY) + 'px';
                draggedElement.setAttribute('data-level', level.toString());

                // Guardar nueva posición y nivel
                const nodeId = draggedElement.getAttribute('data-id');
                saveNodePosition(nodeId, x, adjustedY);
                saveNodeLevel(nodeId, level);

                // Disparar evento para actualizar sistema de niveles
                setTimeout(() => {
                    document.dispatchEvent(new CustomEvent('org:nodeMoved', {
                        detail: { id: nodeId }
                    }));
                }, 50);

                console.log('[Organigrama] Nodo reposicionado a nivel:', level, 'ID:', nodeId);
            }
        });
    }

    function handleNodeConnection(sourceNode) {
        console.log('[Organigrama] Iniciando conexión desde nodo:', sourceNode.getAttribute('data-id'));

        if (window.showToast) {
            window.showToast('Haz clic en otro nodo para crear la conexión', 'info');
        }

        // Añadir clase visual para indicar modo de conexión
        sourceNode.classList.add('connecting');

        // Escuchar clicks en otros nodos
        const canvas = getCanvas();
        if (!canvas) return;

        function connectionClickHandler(e) {
            const targetNode = e.target.closest('.org-node');

            if (targetNode && targetNode !== sourceNode) {
                const sourceId = sourceNode.getAttribute('data-id');
                const targetId = targetNode.getAttribute('data-id');

                createConnection(sourceId, targetId);

                // Limpiar modo de conexión
                canvas.removeEventListener('click', connectionClickHandler);
                sourceNode.classList.remove('connecting');

                console.log('[Organigrama] Conexión creada:', sourceId, '->', targetId);
            } else if (!targetNode) {
                // Click fuera de nodos - cancelar conexión
                canvas.removeEventListener('click', connectionClickHandler);
                sourceNode.classList.remove('connecting');

                if (window.showToast) {
                    window.showToast('Conexión cancelada', 'info');
                }
            }
        }

        canvas.addEventListener('click', connectionClickHandler);
    }

    function createConnection(sourceId, targetId) {
        console.log('[Organigrama] Creando conexión:', sourceId, '->', targetId);

        // Verificar que ambos nodos existan
        const canvas = getCanvas();
        const sourceNode = canvas.querySelector(`.org-node[data-id="${sourceId}"]`);
        const targetNode = canvas.querySelector(`.org-node[data-id="${targetId}"]`);

        if (!sourceNode || !targetNode) {
            console.warn('[Organigrama] No se encontraron los nodos para la conexión');
            return false;
        }

        // Verificar si ya existe la conexión
        const existingConnections = getStoredConnections();
        const connectionExists = existingConnections.some(conn =>
            (conn.parent_id === sourceId && conn.child_id === targetId) ||
            (conn.parent_id === targetId && conn.child_id === sourceId)
        );

        if (connectionExists) {
            if (window.showToast) {
                window.showToast('Ya existe una conexión entre estos nodos', 'warning');
            }
            return false;
        }

        // Guardar conexión
        saveConnection(sourceId, targetId);

        // Redibujar todas las conexiones
        redrawAllConnections();

        if (window.showToast) {
            window.showToast('Conexión creada exitosamente', 'success');
        }

        return true;
    }

    function getStoredConnections() {
        try {
            const companyId = window.IX_CTX?.companyId || 0;
            const key = `org_rels_v1_${companyId}`;
            return JSON.parse(localStorage.getItem(key) || '[]');
        } catch (error) {
            console.error('[Organigrama] Error obteniendo conexiones:', error);
            return [];
        }
    }

    function redrawAllConnections() {
        const canvas = getCanvas();
        const svg = document.getElementById('org-lines');

        if (!canvas || !svg) return;

        const connections = getStoredConnections();

        // Limpiar SVG
        svg.innerHTML = '';

        // Configurar dimensiones del SVG
        svg.setAttribute('width', canvas.clientWidth.toString());
        svg.setAttribute('height', canvas.clientHeight.toString());

        // Crear grupo para las líneas
        const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        group.setAttribute('fill', 'none');
        group.setAttribute('stroke', '#6366f1');
        group.setAttribute('stroke-width', '2');
        group.setAttribute('stroke-dasharray', '5,5');

        // Dibujar cada conexión
        connections.forEach(conn => {
            const sourceNode = canvas.querySelector(`.org-node[data-id="${conn.parent_id}"]`);
            const targetNode = canvas.querySelector(`.org-node[data-id="${conn.child_id}"]`);

            if (sourceNode && targetNode) {
                const path = createConnectionPath(sourceNode, targetNode, canvas);
                if (path) {
                    group.appendChild(path);
                }
            }
        });

        svg.appendChild(group);
    }

    function createConnectionPath(sourceNode, targetNode, canvas) {
        try {
            const sourceRect = sourceNode.getBoundingClientRect();
            const targetRect = targetNode.getBoundingClientRect();
            const canvasRect = canvas.getBoundingClientRect();

            // Calcular puntos de conexión
            const x1 = (sourceRect.left - canvasRect.left) + (sourceRect.width / 2);
            const y1 = (sourceRect.top - canvasRect.top) + sourceRect.height;
            const x2 = (targetRect.left - canvasRect.left) + (targetRect.width / 2);
            const y2 = (targetRect.top - canvasRect.top);

            // Crear path SVG con curva
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const midY = (y1 + y2) / 2;

            const pathData = `M ${x1} ${y1} L ${x1} ${midY} L ${x2} ${midY} L ${x2} ${y2}`;
            path.setAttribute('d', pathData);
            path.setAttribute('class', 'org-connection');

            return path;
        } catch (error) {
            console.error('[Organigrama] Error creando path de conexión:', error);
            return null;
        }
    } function saveNodePosition(nodeId, x, y) {
        try {
            const companyId = window.IX_CTX?.companyId || 0;
            const key = `org_nodes_v1_${companyId}`;
            const positions = JSON.parse(localStorage.getItem(key) || '{}');

            positions[nodeId] = { x, y };
            localStorage.setItem(key, JSON.stringify(positions));
        } catch (error) {
            console.error('[Organigrama] Error guardando posición:', error);
        }
    }

    function saveNodeLevel(nodeId, level) {
        try {
            const companyId = window.IX_CTX?.companyId || 0;
            const key = `org_levels_map_v1_${companyId}`;
            const levels = JSON.parse(localStorage.getItem(key) || '{}');

            levels[nodeId] = level;
            localStorage.setItem(key, JSON.stringify(levels));
        } catch (error) {
            console.error('[Organigrama] Error guardando nivel:', error);
        }
    } function saveConnection(sourceId, targetId) {
        try {
            const companyId = window.IX_CTX?.companyId || 0;
            const key = `org_rels_v1_${companyId}`;
            const connections = JSON.parse(localStorage.getItem(key) || '[]');

            // Evitar duplicados
            const exists = connections.some(conn =>
                conn.parent_id === sourceId && conn.child_id === targetId
            );

            if (!exists) {
                connections.push({
                    parent_id: sourceId,
                    child_id: targetId,
                    created_at: new Date().toISOString()
                });

                localStorage.setItem(key, JSON.stringify(connections));
            }
        } catch (error) {
            console.error('[Organigrama] Error guardando conexión:', error);
        }
    }

    // Exponer funciones globalmente si es necesario
    window.orgDragFix = {
        setupDragAndDrop,
        createOrgNode,
        handleNodeConnection,
        redrawAllConnections
    };

    // Exponer redrawConnections globalmente para compatibilidad
    window.redrawConnections = redrawAllConnections;

    // Auto-ejecutar redibujado cuando se cargue la página
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(() => {
            redrawAllConnections();
        }, 1000);
    });

})();
