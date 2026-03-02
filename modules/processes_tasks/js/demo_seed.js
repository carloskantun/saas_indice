// /modules/processes_tasks/js/demo_seed.js
console.log('[Processes & Tasks] Demo seed data loaded');

(function () {
    'use strict';

    // Demo users data for organigrama
    const demoUsers = [
        {
            id: 1,
            nombre: 'Angelica Nohemi Guillermo Sanchez',
            name: 'Angelica Nohemi Guillermo Sanchez',
            puesto: 'Director(a) de área',
            position: 'Director(a) de área',
            unidad: 'Administración',
            unit: 'Administración',
            departamento: 'Dirección General',
            department: 'Dirección General',
            estatus: 'activo',
            status: 'active'
        },
        {
            id: 2,
            nombre: 'nahum',
            name: 'nahum',
            puesto: 'Analista',
            position: 'Analista',
            unidad: 'Tecnología',
            unit: 'Tecnología',
            departamento: 'IT',
            department: 'IT',
            estatus: 'activo',
            status: 'active'
        },
        {
            id: 3,
            nombre: 'nahum2',
            name: 'nahum2',
            puesto: 'Chef',
            position: 'Chef',
            unidad: 'Operaciones',
            unit: 'Operaciones',
            departamento: 'Cocina',
            department: 'Cocina',
            estatus: 'activo',
            status: 'active'
        },
        {
            id: 4,
            nombre: 'Carlos Rodriguez',
            name: 'Carlos Rodriguez',
            puesto: 'Gerente',
            position: 'Gerente',
            unidad: 'Ventas',
            unit: 'Ventas',
            departamento: 'Comercial',
            department: 'Comercial',
            estatus: 'activo',
            status: 'active'
        },
        {
            id: 5,
            nombre: 'Maria Gonzalez',
            name: 'Maria Gonzalez',
            puesto: 'Coordinadora',
            position: 'Coordinadora',
            unidad: 'Recursos Humanos',
            unit: 'Recursos Humanos',
            departamento: 'Administración',
            department: 'Administración',
            estatus: 'activo',
            status: 'active'
        },
        {
            id: 6,
            nombre: 'Juan Perez',
            name: 'Juan Perez',
            puesto: 'Supervisor',
            position: 'Supervisor',
            unidad: 'Producción',
            unit: 'Producción',
            departamento: 'Operaciones',
            department: 'Operaciones',
            estatus: 'activo',
            status: 'active'
        }
    ];

    // Function to populate people list when organigrama tab is active
    function populateOrganigramaPeopleList() {
        const peopleList = document.getElementById('org-people-list');
        const countEl = document.getElementById('org-count');

        if (!peopleList) {
            console.warn('[Demo Seed] People list element not found');
            return;
        }

        // Clear existing content
        peopleList.innerHTML = '';

        // Populate with demo users
        demoUsers.forEach(user => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'org-user list-group-item list-group-item-action d-flex align-items-center gap-2';
            button.setAttribute('data-id', user.id);
            button.setAttribute('data-status', user.status);
            button.setAttribute('data-name', user.nombre);
            button.setAttribute('data-unit', user.unidad);
            button.setAttribute('data-position', user.puesto);
            button.setAttribute('data-department', user.departamento);
            button.setAttribute('draggable', user.status === 'active' ? 'true' : 'false');
            button.setAttribute('aria-label', user.nombre);

            const avatar = document.createElement('span');
            avatar.className = 'avatar';
            avatar.textContent = user.nombre.substring(0, 1).toUpperCase();

            const content = document.createElement('span');
            content.className = 'flex-grow-1 text-start';

            const name = document.createElement('div');
            name.className = 'fw-semibold';
            name.textContent = user.nombre;

            const details = document.createElement('div');
            details.className = 'small text-muted';

            const parts = [];
            if (user.unidad) parts.push(`Unidad: ${user.unidad}`);
            if (user.puesto) parts.push(`Puesto: ${user.puesto}`);
            if (user.departamento) parts.push(`Depto: ${user.departamento}`);
            details.textContent = parts.join(' | ');

            content.appendChild(name);
            content.appendChild(details);
            button.appendChild(avatar);
            button.appendChild(content);
            peopleList.appendChild(button);
        });

        // Update count
        if (countEl) {
            countEl.textContent = `${demoUsers.length} colaboradores cargados`;
        }

        console.log('[Demo Seed] Populated organigrama with', demoUsers.length, 'users');
    }

    // Function to setup search functionality
    function setupOrganigramaSearch() {
        const searchInput = document.getElementById('org-search');
        const clearBtn = document.getElementById('org-clear-search');
        const peopleList = document.getElementById('org-people-list');

        if (!searchInput || !peopleList) return;

        function filterUsers(query) {
            const users = peopleList.querySelectorAll('.org-user');
            let visibleCount = 0;

            users.forEach(user => {
                const name = user.getAttribute('data-name') || '';
                const position = user.getAttribute('data-position') || '';
                const unit = user.getAttribute('data-unit') || '';
                const department = user.getAttribute('data-department') || '';

                const searchText = `${name} ${position} ${unit} ${department}`.toLowerCase();
                const isVisible = searchText.includes(query.toLowerCase());

                user.style.display = isVisible ? 'flex' : 'none';
                if (isVisible) visibleCount++;
            });

            const countEl = document.getElementById('org-count');
            if (countEl) {
                if (query) {
                    countEl.textContent = `${visibleCount} de ${demoUsers.length} colaboradores`;
                } else {
                    countEl.textContent = `${demoUsers.length} colaboradores cargados`;
                }
            }
        }

        searchInput.addEventListener('input', function (e) {
            filterUsers(e.target.value);
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                filterUsers('');
                searchInput.focus();
            });
        }
    }

    // Initialize when DOM is ready and organigrama tab is loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Check if we're on the organigrama tab
        const isOrganigramaTab = document.body.dataset.tab === 'organigrama' ||
            window.location.search.includes('tab=organigrama');

        if (isOrganigramaTab) {
            // Wait a bit for the DOM to be fully ready
            setTimeout(() => {
                populateOrganigramaPeopleList();
                setupOrganigramaSearch();
            }, 100);
        }
    });

    // Also listen for tab changes
    document.addEventListener('click', function (e) {
        const tabLink = e.target.closest('a[href*="tab=organigrama"]');
        if (tabLink) {
            // Wait for the page to load
            setTimeout(() => {
                populateOrganigramaPeopleList();
                setupOrganigramaSearch();
            }, 200);
        }
    });

    // Expose functions globally for other scripts
    window.demoSeed = {
        users: demoUsers,
        populateOrganigramaPeopleList,
        setupOrganigramaSearch
    };

})();
