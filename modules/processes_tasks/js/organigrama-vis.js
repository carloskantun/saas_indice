// /modules/processes_tasks/js/organigrama-vis.js
// Organigrama using vis.js network for better functionality
console.log('[Processes & Tasks] ORGANIGRAMA VIS.JS loaded');

(function(){
  let network = null;
  let nodes = null;
  let edges = null;
  let container = null;

  const state = {
    people: [],
    selectedNode: null,
    connecting: false
  };

  function initVisOrganigrama() {
    container = document.getElementById('vis-organigrama');
    if (!container) {
      console.warn('[Organigrama] vis-organigrama container not found');
      return;
    }

    // Load people data from the existing system
    loadPeopleData();

    // Initialize the network
    setupNetwork();
    
    // Setup UI controls
    setupControls();
  }

  function loadPeopleData() {
    // Get people from the existing organigrama state or DOM
    const existingPeople = window.debugOrganigrama ? 
      (window.state?.people || []) : 
      getPeopleFromDOM();
    
    state.people = existingPeople.length ? existingPeople : getDefaultPeople();
    console.log('[Organigrama Vis] Loaded people:', state.people.length);
  }

  function getPeopleFromDOM() {
    const orgUsers = document.querySelectorAll('.org-user[data-id]');
    return Array.from(orgUsers).map(el => ({
      id: Number(el.dataset.id),
      name: el.dataset.name || el.getAttribute('aria-label') || 'Usuario',
      position: el.dataset.position || '',
      unit: el.dataset.unit || '',
      department: el.dataset.department || '',
      status: el.dataset.status || 'active'
    }));
  }

  function getDefaultPeople() {
    return [
      {id: 1, name: 'Angelica Nohemi Guillermo Sanchez', position: 'Director(a) de área', unit: 'Administración', status: 'active'},
      {id: 2, name: 'nahum', position: 'Analista', unit: 'Tecnología', status: 'active'},
      {id: 3, name: 'nahum2', position: 'Chef', unit: 'Operaciones', status: 'active'}
    ];
  }

  function setupNetwork() {
    // Prepare nodes
    const visNodes = state.people.map(person => ({
      id: person.id,
      label: person.name,
      title: `${person.position}\n${person.unit}`,
      color: {
        background: person.status === 'active' ? '#e3f2fd' : '#ffecb3',
        border: person.status === 'active' ? '#1976d2' : '#f57c00',
        highlight: {
          background: '#c8e6c9',
          border: '#4caf50'
        }
      },
      font: { size: 14, color: '#333' },
      shape: 'box',
      margin: 10,
      widthConstraint: { minimum: 120, maximum: 200 }
    }));

    // Load saved connections
    const savedConnections = loadConnections();
    const visEdges = savedConnections.map(conn => ({
      id: `${conn.from}-${conn.to}`,
      from: conn.from,
      to: conn.to,
      arrows: 'to',
      color: { color: '#1976d2', highlight: '#4caf50' },
      width: 2,
      smooth: { type: 'cubicBezier', forceDirection: 'vertical', roundness: 0.4 }
    }));

    nodes = new vis.DataSet(visNodes);
    edges = new vis.DataSet(visEdges);

    const data = { nodes: nodes, edges: edges };

    const options = {
      layout: {
        hierarchical: {
          direction: 'UD',
          sortMethod: 'directed',
          nodeSpacing: 200,
          levelSeparation: 150
        }
      },
      physics: {
        enabled: false
      },
      interaction: {
        dragNodes: true,
        selectConnectedEdges: false,
        hover: true
      },
      nodes: {
        shape: 'box',
        margin: 10,
        font: { size: 14 }
      },
      edges: {
        arrows: 'to',
        smooth: {
          type: 'cubicBezier',
          forceDirection: 'vertical',
          roundness: 0.4
        }
      }
    };

    network = new vis.Network(container, data, options);

    // Add event listeners
    network.on('click', onNetworkClick);
    network.on('doubleClick', onNetworkDoubleClick);
  }

  function onNetworkClick(params) {
    if (params.nodes.length > 0) {
      const nodeId = params.nodes[0];
      
      if (state.connecting && state.selectedNode !== null && state.selectedNode !== nodeId) {
        // Create connection
        createConnection(state.selectedNode, nodeId);
        clearSelection();
      } else if (state.selectedNode === nodeId) {
        // Deselect
        clearSelection();
      } else {
        // Select node
        selectNode(nodeId);
      }
    } else {
      // Click on empty space - clear selection
      clearSelection();
    }
  }

  function onNetworkDoubleClick(params) {
    if (params.nodes.length > 0) {
      const nodeId = params.nodes[0];
      showNodeInfo(nodeId);
    }
  }

  function selectNode(nodeId) {
    state.selectedNode = nodeId;
    state.connecting = true;
    
    // Highlight the node
    const node = nodes.get(nodeId);
    node.color = {
      background: '#fff3e0',
      border: '#ff9800',
      highlight: { background: '#ffe0b2', border: '#f57c00' }
    };
    nodes.update(node);
    
    showToast('Nodo seleccionado. Haz clic en otro para conectar.', 'info');
    updateStatusBar(`Conectando desde: ${getPerson(nodeId)?.name || nodeId}`);
  }

  function clearSelection() {
    if (state.selectedNode !== null) {
      // Reset node color
      const node = nodes.get(state.selectedNode);
      const person = getPerson(state.selectedNode);
      node.color = {
        background: person?.status === 'active' ? '#e3f2fd' : '#ffecb3',
        border: person?.status === 'active' ? '#1976d2' : '#f57c00',
        highlight: { background: '#c8e6c9', border: '#4caf50' }
      };
      nodes.update(node);
    }
    
    state.selectedNode = null;
    state.connecting = false;
    updateStatusBar('Haz clic en un nodo para seleccionar');
  }

  function createConnection(fromId, toId) {
    const edgeId = `${fromId}-${toId}`;
    
    // Check if edge already exists
    if (edges.get(edgeId)) {
      showToast('Esta conexión ya existe', 'warning');
      return;
    }

    // Add edge
    edges.add({
      id: edgeId,
      from: fromId,
      to: toId,
      arrows: 'to',
      color: { color: '#1976d2', highlight: '#4caf50' },
      width: 2,
      smooth: { type: 'cubicBezier', forceDirection: 'vertical', roundness: 0.4 }
    });

    // Save connections
    saveConnections();
    
    const fromPerson = getPerson(fromId);
    const toPerson = getPerson(toId);
    showToast(`Conexión creada: ${fromPerson?.name || fromId} → ${toPerson?.name || toId}`, 'success');
    
    console.log('[Organigrama Vis] Connection created:', fromId, '→', toId);
  }

  function getPerson(id) {
    return state.people.find(p => p.id === Number(id));
  }

  function setupControls() {
    // Add control buttons
    const toolbar = document.querySelector('.org-toolbar');
    if (!toolbar) return;

    // Clear connections button
    const clearBtn = document.createElement('button');
    clearBtn.className = 'btn btn-outline-danger btn-sm';
    clearBtn.innerHTML = '<i class="bi bi-trash"></i> Limpiar conexiones';
    clearBtn.addEventListener('click', clearAllConnections);
    
    // Auto layout button
    const layoutBtn = document.createElement('button');
    layoutBtn.className = 'btn btn-outline-primary btn-sm';
    layoutBtn.innerHTML = '<i class="bi bi-diagram-3"></i> Auto layout';
    layoutBtn.addEventListener('click', applyAutoLayout);

    toolbar.appendChild(clearBtn);
    toolbar.appendChild(layoutBtn);
  }

  function clearAllConnections() {
    if (confirm('¿Estás seguro de que quieres eliminar todas las conexiones?')) {
      edges.clear();
      saveConnections();
      clearSelection();
      showToast('Conexiones eliminadas', 'success');
    }
  }

  function applyAutoLayout() {
    network.setOptions({
      layout: {
        hierarchical: {
          direction: 'UD',
          sortMethod: 'directed',
          nodeSpacing: 200,
          levelSeparation: 150
        }
      },
      physics: { enabled: true }
    });

    setTimeout(() => {
      network.setOptions({ physics: { enabled: false } });
    }, 2000);
  }

  function loadConnections() {
    try {
      const saved = localStorage.getItem('vis_organigrama_connections');
      return saved ? JSON.parse(saved) : [];
    } catch (e) {
      console.warn('[Organigrama Vis] Error loading connections:', e);
      return [];
    }
  }

  function saveConnections() {
    try {
      const connections = edges.get().map(edge => ({
        from: edge.from,
        to: edge.to
      }));
      localStorage.setItem('vis_organigrama_connections', JSON.stringify(connections));
    } catch (e) {
      console.warn('[Organigrama Vis] Error saving connections:', e);
    }
  }

  function showNodeInfo(nodeId) {
    const person = getPerson(nodeId);
    if (!person) return;

    const info = `
      <strong>${person.name}</strong><br>
      <em>${person.position}</em><br>
      Unidad: ${person.unit}<br>
      Estado: ${person.status}
    `;
    
    showToast(info, 'info', 5000);
  }

  function updateStatusBar(message) {
    const statusBar = document.getElementById('org-status-bar');
    if (statusBar) {
      statusBar.textContent = message;
    }
  }

  function showToast(message, type = 'info', duration = 3000) {
    if (window.showToast) {
      window.showToast(message, type);
    } else {
      console.log(`[Toast ${type}]`, message);
    }
  }

  // Public API
  window.visOrganigrama = {
    init: initVisOrganigrama,
    clear: clearAllConnections,
    layout: applyAutoLayout,
    addPerson: function(person) {
      state.people.push(person);
      if (nodes) {
        nodes.add({
          id: person.id,
          label: person.name,
          title: `${person.position}\n${person.unit}`,
          color: {
            background: person.status === 'active' ? '#e3f2fd' : '#ffecb3',
            border: person.status === 'active' ? '#1976d2' : '#f57c00'
          }
        });
      }
    }
  };

  // Auto-initialize when vis.js is available
  function tryInit() {
    if (typeof vis !== 'undefined' && vis.Network) {
      initVisOrganigrama();
    } else {
      setTimeout(tryInit, 100);
    }
  }

  document.addEventListener('DOMContentLoaded', tryInit);
})();