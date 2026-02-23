<?php
/**
 * Input Validation & Sanitization Helper
 * /modules/processes_tasks/includes/validation_helper.php
 */

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

/**
 * Validar y sanitizar entero
 * @param mixed $value Valor a validar
 * @param int $min Valor mínimo permitido
 * @param int $max Valor máximo permitido
 * @return int|false Entero sanitizado o false si falla
 */
function validateInt($value, $min = 0, $max = PHP_INT_MAX) {
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false || $int < $min || $int > $max) {
        return false;
    }
    return $int;
}

/**
 * Validar y sanitizar string
 * @param mixed $value Valor a validar
 * @param int $maxLength Longitud máxima
 * @param bool $allowHtml Si se permite HTML
 * @return string|false String sanitizado o false si falla
 */
function validateString($value, $maxLength = 255, $allowHtml = false) {
    if (!is_string($value)) {
        $value = (string)$value;
    }
    
    if (strlen($value) > $maxLength) {
        return false;
    }
    
    if (!$allowHtml) {
        $value = strip_tags($value);
    }
    
    return trim($value);
}

/**
 * Validar formato de fecha YYYY-MM-DD
 * @param mixed $value Valor a validar
 * @return string|false Fecha validada o false si falla
 */
function validateDate($value) {
    if (empty($value)) {
        return false;
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    
    $parts = explode('-', $value);
    if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
        return false;
    }
    
    return $value;
}

/**
 * Validar formato datetime YYYY-MM-DD HH:MM:SS
 * @param mixed $value Valor a validar
 * @return string|false Datetime validado o false si falla
 */
function validateDateTime($value) {
    if (empty($value)) {
        return false;
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return false;
    }
    
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $value) {
        return false;
    }
    
    return $value;
}

/**
 * Validar email
 * @param mixed $value Valor a validar
 * @return string|false Email validado o false si falla
 */
function validateEmail($value) {
    $email = filter_var($value, FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        return false;
    }
    return $email;
}

/**
 * Validar que el valor esté en una lista permitida
 * @param mixed $value Valor a validar
 * @param array $allowed Lista de valores permitidos
 * @return mixed Valor validado o false si falla
 */
function validateEnum($value, array $allowed) {
    if (!in_array($value, $allowed, true)) {
        return false;
    }
    return $value;
}

/**
 * Sanitizar input desde POST/GET con validación
 * @param string $source 'POST' o 'GET'
 * @param string $key Clave del input
 * @param string $type Tipo: 'int', 'string', 'date', 'datetime', 'email', 'enum'
 * @param array $options Opciones adicionales (max_length, min, max, allowed)
 * @return mixed Valor sanitizado o null si falla/no existe
 */
function sanitizeInput($source, $key, $type = 'string', array $options = []) {
    $data = $source === 'POST' ? $_POST : $_GET;
    
    if (!isset($data[$key])) {
        return null;
    }
    
    $value = $data[$key];
    
    switch ($type) {
        case 'int':
            $min = $options['min'] ?? 0;
            $max = $options['max'] ?? PHP_INT_MAX;
            return validateInt($value, $min, $max);
            
        case 'string':
            $maxLength = $options['max_length'] ?? 255;
            $allowHtml = $options['allow_html'] ?? false;
            return validateString($value, $maxLength, $allowHtml);
            
        case 'date':
            return validateDate($value);
            
        case 'datetime':
            return validateDateTime($value);
            
        case 'email':
            return validateEmail($value);
            
        case 'enum':
            $allowed = $options['allowed'] ?? [];
            return validateEnum($value, $allowed);
            
        default:
            return validateString($value, 255, false);
    }
}

/**
 * Validar múltiples inputs de una vez
 * @param array $rules Array de reglas: ['key' => ['source' => 'POST', 'type' => 'int', ...]]
 * @return array Array con valores sanitizados
 * @throws InvalidArgumentException Si alguna validación falla
 */
function validateInputs(array $rules) {
    $result = [];
    
    foreach ($rules as $key => $rule) {
        $source = $rule['source'] ?? 'POST';
        $type = $rule['type'] ?? 'string';
        $required = $rule['required'] ?? false;
        $options = $rule['options'] ?? [];
        
        $value = sanitizeInput($source, $key, $type, $options);
        
        if ($required && ($value === null || $value === false)) {
            throw new InvalidArgumentException("Campo requerido inválido o faltante: {$key}");
        }
        
        $result[$key] = $value;
    }
    
    return $result;
}
