#!/bin/bash
# Script de testing rápido para Backend API
# Ubicación: /modules/processes_tasks/includes/test_api.sh

echo "🧪 Testing Backend API - Processes & Tasks"
echo "=========================================="
echo ""

# Configuración
BASE_URL="http://localhost/modules/processes_tasks/includes"
SESSION_COOKIE="PHPSESSID=test_session_id"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para probar GET
test_get() {
    local endpoint=$1
    local description=$2
    echo -e "${YELLOW}📡 TEST:${NC} $description"
    echo "Endpoint: GET $endpoint"
    
    response=$(curl -s -w "\n%{http_code}" "$BASE_URL/$endpoint")
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -eq 200 ]; then
        echo -e "${GREEN}✓ Status: $http_code OK${NC}"
        echo "Response: $(echo "$body" | jq -r '.' 2>/dev/null || echo "$body")"
    else
        echo -e "${RED}✗ Status: $http_code ERROR${NC}"
        echo "Response: $body"
    fi
    echo ""
}

# Función para probar POST
test_post() {
    local endpoint=$1
    local data=$2
    local description=$3
    echo -e "${YELLOW}📡 TEST:${NC} $description"
    echo "Endpoint: POST $endpoint"
    echo "Data: $data"
    
    response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/$endpoint" \
        -H "Content-Type: application/json" \
        -b "$SESSION_COOKIE" \
        -d "$data")
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -eq 200 ]; then
        echo -e "${GREEN}✓ Status: $http_code OK${NC}"
    elif [ "$http_code" -eq 401 ]; then
        echo -e "${YELLOW}⚠ Status: $http_code UNAUTHORIZED (esperado sin sesión)${NC}"
    else
        echo -e "${RED}✗ Status: $http_code ERROR${NC}"
    fi
    echo "Response: $(echo "$body" | jq -r '.' 2>/dev/null || echo "$body")"
    echo ""
}

# Tests de Catálogos
echo "🗂️  CATÁLOGOS"
echo "============"
test_get "business.php" "Obtener negocios"
test_get "users.php" "Obtener usuarios"
test_get "projects.php" "Obtener proyectos"

# Tests de Lectura
echo "📖 LECTURA DE TAREAS"
echo "==================="
test_get "get_tasks.php?page=1&pageSize=5" "Primera página (5 registros)"
test_get "get_tasks.php?search=dashboard" "Búsqueda por palabra clave"
test_get "get_tasks.php?tipo=Tarea" "Filtro por tipo=Tarea"
test_get "get_tasks.php?status=En%20proceso" "Filtro por status=En proceso"
test_get "get_tasks.php?sortBy=fecha_inicio&sortDir=desc" "Ordenar por fecha descendente"

# Tests de Escritura
echo "✏️  ESCRITURA DE TAREAS"
echo "====================="
test_post "update_task.php" '{"id": 1, "field": "status", "value": "Terminada"}' "Actualizar status a Terminada"
test_post "update_task.php" '{"id": 1, "field": "ponderacion", "value": "5"}' "Actualizar ponderación a 5"
test_post "update_task.php" '{"id": 1, "field": "titulo", "value": "Nuevo título editado"}' "Actualizar título"

# Test de validaciones
echo "🔒 VALIDACIONES"
echo "=============="
test_post "update_task.php" '{"id": 1, "field": "ponderacion", "value": "10"}' "Valor inválido (ponderación > 5)"
test_post "update_task.php" '{"id": 1, "field": "campo_invalido", "value": "test"}' "Campo no permitido"
test_post "update_task.php" '{"id": 999, "field": "titulo", "value": "Test"}' "Tarea inexistente"

echo "=========================================="
echo -e "${GREEN}✓ Tests completados${NC}"
echo ""
echo "💡 Notas:"
echo "  - Tests de POST sin sesión mostrarán 401 (esperado)"
echo "  - Para pruebas completas, inicia sesión primero"
echo "  - Verifica los datos en phpMyAdmin o MySQL"
