<?php
require __DIR__ . '/bootstrap.php';
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $payload_raw = $_POST['payload'] ?? '';
        $payload = json_decode($payload_raw, true);
        if (!is_array($payload)) { throw new Exception('Payload inválido'); }

        $step1 = isset($payload['step1']) && is_array($payload['step1']) ? $payload['step1'] : [];
        $step2 = isset($payload['step2']) && is_array($payload['step2']) ? $payload['step2'] : [];
        $step3 = isset($payload['step3']) && is_array($payload['step3']) ? $payload['step3'] : [];
        $step4 = isset($payload['step4']) && is_array($payload['step4']) ? $payload['step4'] : [];

        $nombre_empresa    = $step2['companyName'] ?? '';
        $industria         = $step2['industry'] ?? '';
        $descripcion       = $step2['description'] ?? '';
        $telefono_contacto = $step1['phone'] ?? '';
        $num_sucursales    = isset($step3['unidades']) && is_array($step3['unidades']) ? count($step3['unidades']) : 0;
        $num_colaboradores = isset($step3['colaboradores']) ? (int)$step3['colaboradores'] : 0;
        $canales_venta     = isset($step4['ventas']) && is_array($step4['ventas']) ? implode(',', $step4['ventas']) : '';
        $metodos_pago      = isset($step4['pagos']) && is_array($step4['pagos']) ? implode(',', $step4['pagos']) : '';
        $payload_json      = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $stmt = db()->prepare("INSERT INTO empresas_registro (
            nombre_empresa, industria, descripcion, telefono_contacto,
            num_sucursales, num_colaboradores, canales_venta, metodos_pago, payload_json, fecha_registro
        ) VALUES (:nombre_empresa, :industria, :descripcion, :telefono_contacto, :num_sucursales, :num_colaboradores, :canales_venta, :metodos_pago, :payload_json, NOW())");
        $stmt->execute([
            ':nombre_empresa' => $nombre_empresa,
            ':industria' => $industria,
            ':descripcion' => $descripcion,
            ':telefono_contacto' => $telefono_contacto,
            ':num_sucursales' => $num_sucursales,
            ':num_colaboradores' => $num_colaboradores,
            ':canales_venta' => $canales_venta,
            ':metodos_pago' => $metodos_pago,
            ':payload_json' => $payload_json
        ]);

        header('Location: https://app.indiceapp.com/auth/register?success=true');
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Prueba Gratis</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .registro-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .registro-container h2 {
            text-align: center;
            margin-bottom: 24px;
        }
        .form-section {
            margin-bottom: 32px;
        }
        .form-section h3 { margin-bottom: 18px; color: var(--brand); }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        .dynamic-list {
            margin-bottom: 16px;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 28px;
            margin-bottom: 16px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            background: #f7f7f7;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 400;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
            cursor: pointer;
            transition: background 0.2s;
            min-height: 48px;
        }
        .checkbox-group label:hover {
            background: var(--brand-soft);
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        button[type="submit"] { width: 100%; padding: 12px; background: var(--brand); color: #fff; border: none; border-radius: 10px; font-size: 17px; cursor: pointer; }
        button[type="submit"]:hover { filter: saturate(1.05) brightness(1.02); }

        /* Wizard por pasos */
        .progress-wrapper {
            margin-bottom: 20px;
        }
        .progress {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress .bar { height: 100%; width: 0; background: var(--brand); transition: width 0.3s ease; }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 13px;
            color: #666;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .buttons {
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-top: 8px;
        }
        .btn-secondary { background: #eef1f4; color: #0b1220; border: 1px solid #d5dae1; border-radius: 10px; padding: 10px 16px; cursor: pointer; }
        .btn-secondary:hover { background: #e9eef7; }
        .btn-primary { background: var(--brand); color: #fff; border: none; border-radius: 10px; padding: 10px 16px; cursor: pointer; }
        .btn-primary:hover { filter: saturate(1.05) brightness(1.02); }
        .btn-submit { width: 100%; }

        /* Campos y animaciones */
        .field { margin-bottom: 16px; opacity: 1; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px);} to { opacity: 1; transform: translateY(0);} }
        @keyframes ripple { from { transform: scale(0); opacity: .35;} to { transform: scale(4); opacity: 0;} }
        @keyframes shake { 10%, 90% { transform: translateX(-1px);} 20%, 80% { transform: translateX(2px);} 30%, 50%, 70% { transform: translateX(-4px);} 40%, 60% { transform: translateX(4px);} }
        .shake { animation: shake .45s linear; }

        /* Bloque condicional inventario */
        #condicionalInventario { max-height: 0; opacity: 0; overflow: hidden; transition: max-height .35s ease, opacity .25s ease; }
        #condicionalInventario.show { max-height: 800px; opacity: 1; }

        /* Chips */
        .chips { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
        .chips input { border: none; outline: none; flex: 1; min-width: 140px; margin: 0; }
        .chip { background: var(--brand-soft); color: var(--brand); padding: 6px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px; }
        .chip .close { cursor: pointer; font-weight: bold; }

        /* Métodos de pago como botones */
        .btn-check-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
        .btn-check { position: relative; }
        .btn-check input { position: absolute; opacity: 0; pointer-events: none; }
        .btn-choice { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 10px 14px; border-radius: 8px; background: #f7f7f7; box-shadow: 0 1px 2px rgba(0,0,0,.05); cursor: pointer; transition: background .2s, box-shadow .2s; overflow: hidden; }
        .btn-choice:hover { background: #f1f4fa; }
        .btn-check input:checked + .btn-choice { background: var(--brand-soft); box-shadow: inset 0 0 0 2px var(--brand); color: var(--brand); }
        .btn-choice .ripple { position: absolute; width: 12px; height: 12px; border-radius: 999px; background: currentColor; transform: scale(0); opacity: .35; animation: ripple .5s ease-out forwards; pointer-events: none; }

        /* Paso 2 – Ubicación y facturación */
        .subsection-title { margin: 14px 0 10px; font-weight: 600; color: var(--brand); }
        .subsection-title.inline { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .inline { display: flex; align-items: center; gap: 10px; }
        .inline input[type="checkbox"] { margin-right: 8px; }
        input[type="checkbox"] { accent-color: var(--brand); }
        .tax-cards { display: flex; flex-direction: column; gap: 16px; margin-top: 8px; }
        .tax-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background: #fafafa; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
        .tax-card h4 { margin: 0 0 10px; font-size: 16px; color: #111827; }
        .help-line { font-size: 12px; color: #6b7280; margin-top: -10px; margin-bottom: 10px; }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .row-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .error-text { color: #c62828; font-size: 12px; margin-top: -10px; margin-bottom: 10px; display: none; }
        .is-invalid + .error-text { display: block; }
        .chips-box { display:flex; flex-wrap:wrap; gap:8px; padding:8px; border:1px solid #ccc; border-radius:6px; }
        .chips-box input { border:none; outline:none; flex:1; min-width:140px; margin:0; }
        /* Paso 3 – Estructura jerárquica */
        .unit-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
        .unit-card h4 { margin: 0 0 10px; font-size: 16px; color: #111827; }
        .biz-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
        .biz-list .chip { background: var(--brand-soft); color: var(--brand); }
        /* Full width for step 2 blocks */
        #monoControls, #multiControls, #taxCards { width: 100%; }
        @media (max-width: 640px) {
            .row-2, .row-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <h2>Comienza tu Prueba Gratis</h2>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <div class="progress-wrapper">
            <div class="progress"><div class="bar" id="progress-bar"></div></div>
            <div class="step-indicator">
                <span>Paso 1</span><span>Paso 2</span><span>Paso 3</span><span>Paso 4</span>
            </div>
        </div>

        <form action="registro.php" method="post" id="form-registro">
            <input type="hidden" id="payload" name="payload">
            <!-- PASO 1 – Cuenta de usuario -->
            <div class="step active" data-step="1">
                <h3>Cuenta de usuario</h3>
                <label for="nombre_completo">Nombre completo</label>
                <input type="text" id="nombre_completo" name="nombre_completo" required>

                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required>

                <label for="phone">Teléfono de contacto</label>
                <input type="tel" id="phone" name="phone" placeholder="+52 55 1234 5678">

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>

                <label for="password_confirm">Confirmar contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required>

                <div class="buttons">
                    <span></span>
                    <button type="button" class="btn-primary" onclick="nextStep()">Siguiente</button>
                </div>
            </div>

            <!-- PASO 2 – Información de la empresa -->
            <div class="step" data-step="2">
                <h3>Información de la empresa</h3>
                <label for="nombre_empresa">Nombre de la empresa</label>
                <input type="text" id="nombre_empresa" name="nombre_empresa" required>

                <label for="industria">Industria</label>
                <select id="industria" name="industria" required>
                    <option value="">Selecciona una opción</option>
                    <option value="agroindustria">Agroindustria</option>
                    <option value="alimentos_bebidas">Alimentos y bebidas</option>
                    <option value="automotriz">Automotriz</option>
                    <option value="construccion">Construcción</option>
                    <option value="comercio_mayorista">Comercio mayorista</option>
                    <option value="comercio_minorista">Comercio minorista / Retail</option>
                    <option value="consultoria_servicios">Consultoría y servicios profesionales</option>
                    <option value="educacion">Educación</option>
                    <option value="energia_recursos">Energía y recursos naturales</option>
                    <option value="entretenimiento_medios">Entretenimiento y medios</option>
                    <option value="finanzas_seguros">Finanzas y seguros</option>
                    <option value="gobierno_publico">Gobierno y sector público</option>
                    <option value="hospitalidad_hoteleria">Hospitalidad / Hotelería</option>
                    <option value="inmobiliario_bienes">Inmobiliario y bienes raíces</option>
                    <option value="logistica_transporte">Logística y transporte</option>
                    <option value="manufactura_ligera">Manufactura ligera</option>
                    <option value="manufactura_pesada">Manufactura pesada / industrial</option>
                    <option value="moda_textil">Moda y textil</option>
                    <option value="restaurantes_catering">Restaurantes y catering</option>
                    <option value="salud_bienestar">Salud y bienestar</option>
                    <option value="servicios_limpieza">Servicios de limpieza y mantenimiento</option>
                    <option value="servicios_tecnologicos">Servicios tecnológicos / IT</option>
                    <option value="startups_emprendimientos">Startups y emprendimientos</option>
                    <option value="telecomunicaciones">Telecomunicaciones</option>
                    <option value="turismo_viajes">Turismo y viajes</option>
                    <option value="otros">Otros</option>
                </select>

                <label for="descripcion_empresa">Descripción breve de la empresa</label>
                <textarea id="descripcion_empresa" name="descripcion_empresa" rows="3" required></textarea>

                <div class="form-section" id="ubicacionFacturacion">
                    <div class="subsection-title inline">
                        <span>Ubicación y facturación</span>
                        <label class="inline" style="gap:8px; cursor:pointer; margin:0;">
                            <input type="checkbox" id="isMulti"> ¿La empresa es multinacional?
                        </label>
                    </div>

                    <!-- Controles modo NO multinacional -->
                    <div id="monoControls" class="field">
                        <label for="country">País</label>
                        <select id="country">
                            <option value="">Selecciona un país</option>
                            <option>México</option>
                            <option>Colombia</option>
                            <option>Guatemala</option>
                            <option>Costa Rica</option>
                            <option>Brasil</option>
                            <option>Chile</option>
                            <option>Argentina</option>
                            <option>Perú</option>
                            <option>Otro</option>
                        </select>
                        <div id="countryOtherWrap" style="display:none;">
                            <label for="countryOther">Especifica el país</label>
                            <input type="text" id="countryOther" placeholder="Ej. Bolivia">
                        </div>
                    </div>

                    <!-- Controles modo multinacional -->
                    <div id="multiControls" class="field" style="display:none;">
                        <label>Países</label>
                        <div class="inline" style="gap:8px;">
                            <select id="countryPicker" style="flex:1;">
                                <option value="">Agregar país…</option>
                                <option>México</option>
                                <option>Colombia</option>
                                <option>Guatemala</option>
                                <option>Costa Rica</option>
                                <option>Brasil</option>
                                <option>Chile</option>
                                <option>Argentina</option>
                                <option>Perú</option>
                                <option>Otro</option>
                            </select>
                            <button type="button" class="btn-secondary" id="addCountryBtn">Agregar</button>
                        </div>
                        <div id="countriesMulti" class="chips-box" style="margin-top:8px;">
                            <input type="text" id="countriesMultiInput" placeholder="(Click en Agregar para sumar países)">
                        </div>
                        <div id="countriesOtherWrap" style="display:none; margin-top:10px;">
                            <label for="countriesOther">Agregar países "Otro"</label>
                            <div class="chips-box" id="countriesOtherBox">
                                <input type="text" id="countriesOther" placeholder="Escribe un país y Enter">
                            </div>
                            <small style="color:#6b7280;">Incluye al menos dos países en total.</small>
                        </div>
                    </div>

                    <!-- Tarjetas fiscales por país -->
                    <div id="taxCards" class="tax-cards"></div>
                    <div id="step2Errors" class="error-text" style="display:none; margin-top:6px;"></div>
                </div>

                <div class="buttons">
                    <button type="button" class="btn-secondary" onclick="prevStep()">Anterior</button>
                    <button type="button" class="btn-primary" onclick="nextStep()">Siguiente</button>
                </div>
            </div>

            <!-- PASO 3 – Estructura organizacional -->
            <div class="step" data-step="3">
                <h3>Estructura organizacional</h3>
                <label for="num_unidades">Número de unidades de negocio</label>
                <input type="number" id="num_unidades" name="num_unidades" min="1" max="100" required oninput="actualizarUnidades()">
                <div id="unidades-list" class="dynamic-list"></div>

                <label for="num_colaboradores">Número total de colaboradores</label>
                <input type="number" id="num_colaboradores" name="num_colaboradores" min="1" required>

                <div class="buttons">
                    <button type="button" class="btn-secondary" onclick="prevStep()">Anterior</button>
                    <button type="button" class="btn-primary" onclick="nextStep()">Siguiente</button>
                </div>
            </div>

            <!-- PASO 4 – Productos y/o servicios -->
            <div class="step" data-step="4">
                <h3>Productos y/o servicios</h3>
                <div class="field">
                    <label for="ofrece_empresa">¿Qué ofrece la empresa?</label>
                    <select id="ofrece_empresa" name="ofrece_empresa" required>
                        <option value="">Selecciona una opción</option>
                        <option value="productos">Productos</option>
                        <option value="servicios">Servicios</option>
                        <option value="ambos">Ambos</option>
                    </select>
                </div>

                <div class="field">
                    <label for="principales_productos">Principales productos o servicios</label>
                    <div class="chips" id="chipsWrapper">
                        <input type="text" id="chipsInput" placeholder="Escribe y presiona Enter o coma">
                        <input type="hidden" name="catalogo_tags" id="chipsHidden">
                    </div>
                </div>

                <div id="condicionalInventario">
                    <div class="field">
                        <label for="gestiona_inventario">¿Gestiona inventario físico?</label>
                        <select id="gestiona_inventario" name="gestiona_inventario">
                            <option value="">Selecciona una opción</option>
                            <option value="si">Sí</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="num_productos">¿Cuántos productos o servicios maneja?</label>
                        <input type="number" id="num_productos" name="num_productos" min="0">
                    </div>
                </div>

                <div class="field">
                    <label>Métodos principales de venta</label>
                    <div class="checkbox-group" id="ventasGroup">
                        <label><input type="checkbox" value="mostrador"> Mostrador / Tienda física</label>
                        <label><input type="checkbox" value="ecommerce"> En línea (e-commerce propio)</label>
                        <label><input type="checkbox" value="distribuidores"> Distribuidores / Mayoristas</label>
                        <label><input type="checkbox" value="marketplaces"> Marketplaces (Amazon, Mercado Libre, etc.)</label>
                        <label><input type="checkbox" value="redes"> Redes sociales (Facebook, Instagram, TikTok, WhatsApp Business)</label>
                        <label><input type="checkbox" value="callcenter"> Call center / Ventas telefónicas</label>
                        <label><input type="checkbox" value="fuerza"> Fuerza de ventas / Representantes comerciales</label>
                        <label><input type="checkbox" value="suscripciones"> Suscripciones / Membresías</label>
                        <label><input type="checkbox" value="eventos"> Eventos / Ferias / Puntos temporales</label>
                        <label><input type="checkbox" value="otas"> Reservas en OTAs (Airbnb, Booking, Expedia)</label>
                        <label><input type="checkbox" value="otros" id="venta_otros_chk"> Otros</label>
                    </div>
                    <div id="ventaOtrosWrap" style="display:none; margin-top:8px;">
                        <label for="venta_otros_text">Especifica otros métodos de venta</label>
                        <input type="text" id="venta_otros_text" placeholder="Describe otros métodos">
                    </div>
                </div>

                <div class="field">
                    <label>Métodos de pago aceptados</label>
                    <div class="checkbox-group" id="pagosGroup">
                        <label><input type="checkbox" value="efectivo"> Efectivo</label>
                        <label><input type="checkbox" value="debito"> Tarjeta de débito</label>
                        <label><input type="checkbox" value="credito"> Tarjeta de crédito</label>
                        <label><input type="checkbox" value="transferencia"> Transferencia bancaria</label>
                        <label><input type="checkbox" value="deposito"> Depósito en banco</label>
                        <label><input type="checkbox" value="qr_spei_pix"> Pago QR / SPEI / PIX</label>
                        <label><input type="checkbox" value="paypal"> PayPal</label>
                        <label><input type="checkbox" value="mercadopago"> Mercado Pago</label>
                        <label><input type="checkbox" value="stripe"> Stripe</label>
                        <label><input type="checkbox" value="cheque"> Cheque</label>
                        <label><input type="checkbox" value="cripto"> Criptomonedas</label>
                        <label><input type="checkbox" value="otros" id="pago_otros_chk"> Otros</label>
                    </div>
                    <div id="pagoOtrosWrap" style="display:none; margin-top:8px;">
                        <label for="pago_otros_text">Especifica otros métodos de pago</label>
                        <input type="text" id="pago_otros_text" placeholder="Describe otros métodos">
                    </div>
                </div>
 
                 <div class="buttons">
                     <button type="button" class="btn-secondary" onclick="prevStep()">Anterior</button>
                     <button type="submit" class="btn-primary btn-submit">Enviar</button>
                 </div>
             </div>
         </form>
    </div>
    <script>
        // === Paso 2: TAX MAP ===
        const TAX_MAP = {
          "México": {
            individual: { label: "RFC", name: "Registro Federal de Contribuyentes", sigla: "RFC", authority: "SAT" },
            company:   { label: "RFC", name: "Registro Federal de Contribuyentes", sigla: "RFC", authority: "SAT" },
            requiresAddress: true
          },
          "Colombia": {
            all: { label: "NIT", name: "Número de Identificación Tributaria", sigla: "NIT", authority: "DIAN" },
            requiresAddress: true
          },
          "Guatemala": {
            all: { label: "NIT", name: "Número de Identificación Tributaria", sigla: "NIT", authority: "SAT (Superintendencia de Administración Tributaria)" },
            requiresAddress: true
          },
          "Costa Rica": {
            person:  { label: "Cédula de Identidad", name: "Cédula de Identidad (persona física contribuyente)", sigla: "-", authority: "Ministerio de Hacienda" },
            company: { label: "Cédula Jurídica",     name: "Cédula Jurídica (empresa)",                         sigla: "-", authority: "Ministerio de Hacienda" },
            requiresAddress: true
          },
          "Brasil": {
            person:  { label: "CPF",  name: "Cadastro de Pessoas Físicas",      sigla: "CPF",  authority: "Receita Federal do Brasil" },
            company: { label: "CNPJ", name: "Cadastro Nacional da Pessoa Jurídica", sigla: "CNPJ", authority: "Receita Federal do Brasil" },
            requiresAddress: true
          },
          "Chile": {
            all: { label: "RUT", name: "Rol Único Tributario", sigla: "RUT", authority: "SII (Servicio de Impuestos Internos)" },
            requiresAddress: true
          },
          "Argentina": {
            person:  { label: "CUIL", name: "Código Único de Identificación Laboral",  sigla: "CUIL", authority: "AFIP" },
            company: { label: "CUIT", name: "Clave Única de Identificación Tributaria", sigla: "CUIT", authority: "AFIP" },
            requiresAddress: true
          },
          "Perú": {
            all: { label: "RUC", name: "Registro Único de Contribuyentes", sigla: "RUC", authority: "SUNAT" },
            requiresAddress: true
          },
          "Otro": {
            all: { label: "ID fiscal", name: "Identificador fiscal local", sigla: "-", authority: "Autoridad tributaria local" },
            requiresAddress: false
          }
        };

        // Utilidades Paso 2
        const isMultiEl = document.getElementById('isMulti');
        const monoControls = document.getElementById('monoControls');
        const multiControls = document.getElementById('multiControls');
        const countrySel = document.getElementById('country');
        const countryOtherWrap = document.getElementById('countryOtherWrap');
        const countryOther = document.getElementById('countryOther');
        const countryPicker = document.getElementById('countryPicker');
        const addCountryBtn = document.getElementById('addCountryBtn');
        const countriesMulti = document.getElementById('countriesMulti');
        const countriesMultiInput = document.getElementById('countriesMultiInput');
        const countriesOtherWrap = document.getElementById('countriesOtherWrap');
        const countriesOtherInput = document.getElementById('countriesOther');
        const countriesOtherBox = document.getElementById('countriesOtherBox');
        const taxCards = document.getElementById('taxCards');
        const step2Errors = document.getElementById('step2Errors');

        let selectedCountries = [];
        function toDisplayName(item){ return item.code === 'Otro' && item.otherName ? item.otherName : item.code; }

        function ensureUniqueAdd(code, otherName=null) {
            if (code === 'Otro' && otherName) {
                selectedCountries.push({ code, otherName });
            } else {
                if (!selectedCountries.some(c => c.code === code)) selectedCountries.push({ code, otherName: null });
            }
            renderCountriesChips();
            renderTaxCards();
        }

        function removeCountryAt(idx){ selectedCountries.splice(idx,1); renderCountriesChips(); renderTaxCards(); }

        function renderCountriesChips() {
            countriesMulti.querySelectorAll('.chip').forEach(n=>n.remove());
            selectedCountries.forEach((c, idx) => {
                const chip = document.createElement('span');
                chip.className = 'chip';
                chip.textContent = toDisplayName(c);
                const close = document.createElement('span');
                close.className = 'close';
                close.textContent = '×';
                close.addEventListener('click', ()=> removeCountryAt(idx));
                chip.appendChild(close);
                countriesMulti.insertBefore(chip, countriesMultiInput);
            });
            const hasOtro = selectedCountries.some(c=>c.code==='Otro');
            countriesOtherWrap.style.display = hasOtro ? 'block' : 'none';
        }

        function renderTaxCards(){
            taxCards.innerHTML = '';
            const list = isMultiEl.checked ? selectedCountries : buildSingleList();
            list.forEach((item, idx) => taxCards.appendChild(buildTaxCard(item, idx)));
        }

        function buildSingleList(){
            const code = countrySel.value;
            if (!code) return [];
            if (code === 'Otro') return countryOther.value.trim() ? [{ code: 'Otro', otherName: countryOther.value.trim() }] : [];
            return [{ code, otherName: null }];
        }

        function buildTaxCard(item, idx){
            const countryCode = item.code;
            const displayName = toDisplayName(item);
            const config = TAX_MAP[countryCode] || TAX_MAP['Otro'];
            const requiresAddress = !!config.requiresAddress;
            const personaKeys = config.all ? ['all'] : (config.individual ? ['individual','company'] : (config.person ? ['person','company'] : ['all']));
            const personaDefault = personaKeys[0];
            const taxInfo = config[personaDefault] || config.all;
            const taxLabel = taxInfo.label;

            const card = document.createElement('div');
            card.className = 'tax-card';
            card.dataset.countryCode = countryCode;
            card.dataset.countryOtherName = item.otherName || '';
            card.innerHTML = `
                <h4>Datos fiscales — ${displayName}</h4>
                <label>Nombre o razón social (como está registrado en la autoridad fiscal)</label>
                <input type="text" class="razon" placeholder="Razón social legal" required>
                <div class="error-text">Requerido</div>

                <div class="field">
                    <label>Tipo de persona</label>
                    ${ personaKeys.includes('all') ? `
                        <div><small style="color:#6b7280;">Se aplica un identificador único para este país.</small></div>
                        <input type="hidden" class="tipoPersona" id="tipoPersona-${idx}" value="all">
                    ` : `
                        <div class="inline" style="gap:20px;">
                            <label class="inline"><input type="radio" name="tipoPersona-${idx}" value="${personaKeys[0]}" class="tipoPersona" id="tipoPersona-${idx}" checked> ${personaKeys[0]==='individual' || personaKeys[0]==='person' ? 'Física' : 'Moral/Empresa'}</label>
                            <label class="inline"><input type="radio" name="tipoPersona-${idx}" value="${personaKeys[1]}" class="tipoPersona"> ${personaKeys[1]==='company' ? 'Moral/Empresa' : 'Física'}</label>
                        </div>
                    `}
                </div>

                <div class="field">
                    <label class="taxLabel">${taxLabel}</label>
                    <input type="text" class="taxId" placeholder="${placeholderFor(countryCode, personaDefault)}" required>
                    <div class="help-line taxHelp">${taxInfo.name} (${taxInfo.sigla}) — Autoridad: ${taxInfo.authority}</div>
                    <div class="error-text">Requerido</div>
                </div>

                <div class="field">
                    <label>Domicilio fiscal completo</label>
                    <div class="row-3">
                        <input type="text" class="addr-street" placeholder="Calle" ${requiresAddress?'required':''}>
                        <input type="text" class="addr-number" placeholder="Número" ${requiresAddress?'required':''}>
                        <input type="text" class="addr-district" placeholder="Colonia/Barrio" ${requiresAddress?'required':''}>
                    </div>
                    <div class="row-3" style="margin-top:8px;">
                        <input type="text" class="addr-city" placeholder="Ciudad" ${requiresAddress?'required':''}>
                        <input type="text" class="addr-state" placeholder="Estado/Provincia" ${requiresAddress?'required':''}>
                        <input type="text" class="addr-postal" placeholder="Código Postal" ${requiresAddress?'required':''}>
                    </div>
                    <div class="error-text">Dirección requerida para este país</div>
                </div>

                <div class="field">
                    <label>Teléfono de contacto (opcional)</label>
                    <input type="tel" class="phone" placeholder="+52 55 1234 5678">
                </div>
            `;

            card.querySelectorAll('input.tipoPersona').forEach(r => {
                r.addEventListener('change', () => updateTaxLabels(card, countryCode));
            });
            return card;
        }

        function placeholderFor(countryCode, persona){
            const map = {
                'México': 'RFC: ABCD001122XYZ',
                'Colombia': 'NIT: 900123456-7',
                'Guatemala': 'NIT: 1234567-8',
                'Costa Rica': persona==='company' ? 'Cédula Jurídica: 3-101-123456' : 'Cédula de Identidad',
                'Brasil': persona==='company' ? 'CNPJ: 12.345.678/0001-99' : 'CPF: 123.456.789-09',
                'Chile': 'RUT: 12.345.678-9',
                'Argentina': persona==='company' ? 'CUIT: 20-12345678-3' : 'CUIL: 20-12345678-3',
                'Perú': 'RUC: 20123456789',
                'Otro': 'ID fiscal local'
            };
            return map[countryCode] || 'ID fiscal';
        }

        function updateTaxLabels(card, countryCode){
            const config = TAX_MAP[countryCode] || TAX_MAP['Otro'];
            const tipo = (card.querySelector('input.tipoPersona')?.value) || (card.querySelector('input[name^="tipoPersona-"]:checked')?.value) || 'all';
            const info = config[tipo] || config.all;
            const labelEl = card.querySelector('.taxLabel');
            const helpEl = card.querySelector('.taxHelp');
            const taxId = card.querySelector('.taxId');
            if (info) {
                labelEl.textContent = info.label;
                helpEl.textContent = `${info.name} (${info.sigla}) — Autoridad: ${info.authority}`;
                taxId.placeholder = placeholderFor(countryCode, tipo);
            }
        }

        function switchMode(){
            const isMulti = isMultiEl.checked;
            monoControls.style.display = isMulti ? 'none' : 'block';
            multiControls.style.display = isMulti ? 'block' : 'none';
            if (!isMulti) {
                selectedCountries = [];
                renderCountriesChips();
            }
            renderTaxCards();
        }

        isMultiEl.addEventListener('change', switchMode);
        countrySel.addEventListener('change', () => {
            countryOtherWrap.style.display = countrySel.value === 'Otro' ? 'block' : 'none';
            renderTaxCards();
        });
        countryOther.addEventListener('input', renderTaxCards);

        addCountryBtn.addEventListener('click', () => {
            const val = countryPicker.value;
            if (!val) return;
            if (val === 'Otro') {
                if (!selectedCountries.some(c=>c.code==='Otro')) {
                    selectedCountries.push({ code: 'Otro', otherName: null });
                }
                renderCountriesChips();
            } else {
                ensureUniqueAdd(val);
            }
            countryPicker.value = '';
        });

        function renderCountriesOtherChips(){
            countriesOtherBox.querySelectorAll('.chip').forEach(n=>n.remove());
            selectedCountries.forEach((c, idx) => {
                if (c.code==='Otro' && c.otherName) {
                    const chip = document.createElement('span');
                    chip.className = 'chip';
                    chip.textContent = c.otherName;
                    const close = document.createElement('span');
                    close.className = 'close';
                    close.textContent = '×';
                    close.addEventListener('click', ()=> removeCountryAt(idx));
                    chip.appendChild(close);
                    countriesOtherBox.insertBefore(chip, countriesOtherInput);
                }
            });
        }
        countriesOtherInput.addEventListener('keydown', (e)=>{
            if (e.key==='Enter' || e.key===','){
                e.preventDefault();
                const name = countriesOtherInput.value.trim().replace(/,$/, '');
                if (name) {
                    ensureUniqueAdd('Otro', name);
                    countriesOtherInput.value='';
                    renderCountriesOtherChips();
                }
            } else if (e.key==='Backspace' && !countriesOtherInput.value){
                for (let i=selectedCountries.length-1;i>=0;i--){
                    if (selectedCountries[i].code==='Otro' && selectedCountries[i].otherName){
                        removeCountryAt(i); break;
                    }
                }
                renderCountriesOtherChips();
            }
        });

        function scrollToError(el){
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('shake'); setTimeout(()=> el.classList.remove('shake'), 500);
        }

        function validateStep2(){
            let ok = true; let firstError = null; step2Errors.style.display='none'; step2Errors.textContent='';
            const baseRequired = ['nombre_empresa','industria','descripcion_empresa'];
            baseRequired.forEach(id=>{
                const el = document.getElementById(id);
                if (!el.value){ ok=false; if(!firstError) firstError=el; el.classList.add('is-invalid'); }
                else el.classList.remove('is-invalid');
            });
            const isMulti = isMultiEl.checked;
            let list = [];
            if (!isMulti){
                list = buildSingleList();
                if (list.length !== 1){ ok=false; if(!firstError) firstError = countrySel; }
                if (countrySel.value==='Otro' && !countryOther.value.trim()) { ok=false; if(!firstError) firstError=countryOther; countryOther.classList.add('is-invalid'); }
                else countryOther.classList.remove('is-invalid');
            } else {
                list = selectedCountries.filter(c=> c.code !== 'Otro' || (c.code==='Otro' && c.otherName));
                if (list.length < 2){ ok=false; if(!firstError) firstError = countryPicker; step2Errors.textContent='Selecciona al menos 2 países.'; step2Errors.style.display='block'; }
            }
            const cards = Array.from(taxCards.children);
            cards.forEach(card => {
                const razon = card.querySelector('.razon');
                const taxId = card.querySelector('.taxId');
                if (!razon.value.trim()){ ok=false; if(!firstError) firstError=razon; razon.classList.add('is-invalid'); } else razon.classList.remove('is-invalid');
                if (!taxId.value.trim()){ ok=false; if(!firstError) firstError=taxId; taxId.classList.add('is-invalid'); } else taxId.classList.remove('is-invalid');
                const addrReq = (TAX_MAP[card.dataset.countryCode]||TAX_MAP['Otro']).requiresAddress;
                if (addrReq){
                    ['.addr-street','.addr-number','.addr-district','.addr-city','.addr-state','.addr-postal'].forEach(sel=>{
                        const f = card.querySelector(sel);
                        if (!f.value.trim()){ ok=false; if(!firstError) firstError=f; f.classList.add('is-invalid'); } else f.classList.remove('is-invalid');
                    });
                }
            });
            if (!ok && firstError) scrollToError(firstError);
            return ok;
        }

        function getStep2Data(){
            const data = {
                companyName: document.getElementById('nombre_empresa').value || '',
                industry: document.getElementById('industria').value || '',
                description: document.getElementById('descripcion_empresa').value || '',
                isMulti: isMultiEl.checked,
                countries: []
            };
            const list = isMultiEl.checked ? selectedCountries.filter(c=> c.code!=='Otro' || (c.code==='Otro' && c.otherName)) : buildSingleList();
            const cards = Array.from(taxCards.children);
            list.forEach((item, idx) => {
                const card = cards[idx]; if (!card) return;
                const countryCode = card.dataset.countryCode;
                const otherName = card.dataset.countryOtherName || null;
                const tipoEl = card.querySelector('input[name^="tipoPersona-"]:checked') || card.querySelector('input.tipoPersona');
                const tipoPersona = tipoEl ? tipoEl.value : 'all';
                const info = (TAX_MAP[countryCode][tipoPersona]) || TAX_MAP[countryCode].all;
                data.countries.push({
                    country: countryCode,
                    countryOtherName: otherName || null,
                    tipoPersona: tipoPersona,
                    taxIdLabel: info.label,
                    taxId: (card.querySelector('.taxId')?.value || ''),
                    taxInfo: { name: info.name, sigla: info.sigla, authority: info.authority },
                    address: {
                        street: (card.querySelector('.addr-street')?.value || ''),
                        number: (card.querySelector('.addr-number')?.value || ''),
                        district: (card.querySelector('.addr-district')?.value || ''),
                        city: (card.querySelector('.addr-city')?.value || ''),
                        state: (card.querySelector('.addr-state')?.value || ''),
                        postalCode: (card.querySelector('.addr-postal')?.value || '')
                    },
                    phone: (card.querySelector('.phone')?.value || '')
                });
            });
            return data;
        }

        window.getStep2Data = getStep2Data;

        let currentStep = 1;
        const totalSteps = 4;
        const form = document.getElementById('form-registro');
        const steps = Array.from(document.querySelectorAll('.step'));
        const progressBar = document.getElementById('progress-bar');

        function setProgress() {
            const percent = Math.round((currentStep - 1) / (totalSteps - 1) * 100);
            progressBar.style.width = percent + '%';
        }

        function showStep(step) {
            steps.forEach(s => s.classList.remove('active'));
            const target = steps.find(s => s.dataset.step == step);
            if (target) target.classList.add('active');
            currentStep = step;
            setProgress();

            if (currentStep == 4) {
                const fields = target.querySelectorAll('.field');
                fields.forEach((f, idx) => {
                    f.style.animation = `fadeUp .35s ease forwards`;
                    f.style.animationDelay = `${idx * 80}ms`;
                });
            }
        }

        function validateStep(step) {
            const container = steps.find(s => s.dataset.step == step);
            if (!container) return true;
            if (step == 2) {
                const ok = validateStep2();
                if (ok) sessionStorage.setItem('step2', JSON.stringify(getStep2Data()));
                return ok;
            }
            if (step == 1) {
                const p1 = document.getElementById('password').value;
                const p2 = document.getElementById('password_confirm').value;
                const phone = document.getElementById('phone');
                let ok = true;
                if (p1 && p2 && p1 !== p2) { alert('Las contraseñas no coinciden'); ok = false; }
                const val = phone.value.trim();
                if (val) {
                    const digits = val.replace(/[^0-9]/g,'');
                    if (digits.length < 8 || digits.length > 20) { phone.classList.add('is-invalid'); ok = false; } else { phone.classList.remove('is-invalid'); }
                } else { phone.classList.remove('is-invalid'); }
                if (ok) {
                    const step1 = {
                        name: document.getElementById('nombre_completo').value,
                        email: document.getElementById('email').value,
                        phone: phone.value.trim()
                    };
                    sessionStorage.setItem('step1', JSON.stringify(step1));
                }
                return ok;
            }
            if (step == 3) {
                const ok = validateStep3();
                if (ok) sessionStorage.setItem('step3', JSON.stringify(getStep3Data()));
                return ok;
            }
            const inputs = container.querySelectorAll('input, select, textarea');
            let valid = true;
            inputs.forEach(el => {
                if (el.hasAttribute('required') && !el.value) {
                    el.classList.add('is-invalid');
                    valid = false;
                } else {
                    el.classList.remove('is-invalid');
                }
            });
            return valid;
        }

        function nextStep() {
            if (!validateStep(currentStep)) return;
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        }

        function actualizarUnidades(){
            const num = parseInt(document.getElementById('num_unidades').value) || 0;
            const cont = document.getElementById('unidades-list');
            cont.innerHTML = '';
            for (let i=1;i<=num;i++){
                cont.appendChild(crearUnidadCard(i));
            }
        }
        function crearUnidadCard(idx){
            const wrap = document.createElement('div');
            wrap.className = 'unit-card';
            wrap.innerHTML = `
                <h4>Unidad de negocio ${idx}</h4>
                <label>Nombre de la unidad</label>
                <input type="text" class="unidad-nombre" placeholder="Ej. Cancún" required>
                <div class="field">
                    <label>Negocios</label>
                    <div class="biz-list" id="biz-list-${idx}"></div>
                    <div class="inline" style="margin-top:8px; gap:8px;">
                        <input type="text" class="biz-input" placeholder="Ej. Hotel Brisas">
                        <button type="button" class="btn-secondary add-biz">Agregar negocio</button>
                    </div>
                </div>
            `;
            const addBtn = wrap.querySelector('.add-biz');
            const input = wrap.querySelector('.biz-input');
            const list = wrap.querySelector(`#biz-list-${idx}`);
            function addBiz(){
                const v = (input.value||'').trim();
                if (!v) return;
                const chip = document.createElement('span');
                chip.className = 'chip';
                chip.textContent = v;
                const close = document.createElement('span'); close.className='close'; close.textContent='×';
                close.addEventListener('click', ()=> chip.remove());
                chip.appendChild(close);
                list.appendChild(chip);
                input.value='';
            }
            addBtn.addEventListener('click', addBiz);
            input.addEventListener('keydown', (e)=>{ if (e.key==='Enter' || e.key===','){ e.preventDefault(); addBiz(); }});
            return wrap;
        }
        function getStep3Data(){
            const unidades = [];
            document.querySelectorAll('#unidades-list .unit-card').forEach(card => {
                const nombre = card.querySelector('.unidad-nombre')?.value || '';
                const negocios = Array.from(card.querySelectorAll('.biz-list .chip')).map(c=> c.childNodes[0].nodeValue.trim());
                unidades.push({ nombreUnidad: nombre, negocios });
            });
            return { unidades, colaboradores: parseInt(document.getElementById('num_colaboradores').value)||0 };
        }
        function validateStep3(){
            const num = parseInt(document.getElementById('num_unidades').value) || 0;
            if (num < 1) { const el = document.getElementById('num_unidades'); scrollToError(el); return false; }
            let ok = true; let first = null;
            document.querySelectorAll('#unidades-list .unit-card').forEach(card => {
                const nombre = card.querySelector('.unidad-nombre');
                const tieneNegocio = card.querySelectorAll('.biz-list .chip').length > 0;
                if (!nombre.value.trim()) { ok=false; first = first || nombre; nombre.classList.add('is-invalid'); }
                else nombre.classList.remove('is-invalid');
                if (!tieneNegocio) { ok=false; first = first || card.querySelector('.biz-input'); }
            });
            if (!ok && first) scrollToError(first);
            return ok;
        }

        form.addEventListener('input', () => {});

        const ofrece = document.getElementById('ofrece_empresa');
        const condInv = document.getElementById('condicionalInventario');
        const gestiona = document.getElementById('gestiona_inventario');
        const numProd = document.getElementById('num_productos');
        function toggleInventario() {
            const visible = ['productos','ambos'].includes(ofrece.value);
            condInv.classList.toggle('show', visible);
            gestiona.toggleAttribute('required', visible);
            numProd.toggleAttribute('required', visible);
        }
        ofrece.addEventListener('change', () => { toggleInventario(); focusNext(ofrece); });
        toggleInventario();

        const chipsWrapper = document.getElementById('chipsWrapper');
        const chipsInput = document.getElementById('chipsInput');
        const chipsHidden = document.getElementById('chipsHidden');
        const chips = [];
        function renderChips() {
            chipsWrapper.querySelectorAll('.chip').forEach(c => c.remove());
            chips.forEach((txt, i) => {
                const chip = document.createElement('span');
                chip.className = 'chip';
                chip.textContent = txt;
                const close = document.createElement('span');
                close.className = 'close';
                close.textContent = '×';
                close.addEventListener('click', () => { chips.splice(i,1); syncChips(); });
                chip.appendChild(close);
                chipsWrapper.insertBefore(chip, chipsInput);
            });
            chipsHidden.value = chips.join(',');
        }
        function syncChips() { renderChips(); }
        function addChipFromInput() {
            const value = chipsInput.value.trim().replace(/,$/, '');
            if (value) {
                value.split(',').map(v=>v.trim()).filter(Boolean).forEach(v => { if (!chips.includes(v)) chips.push(v); });
                chipsInput.value = '';
                syncChips();
            }
        }
        chipsInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addChipFromInput();
            } else if (e.key === 'Backspace' && !chipsInput.value && chips.length) {
                chips.pop();
                syncChips();
            }
        });

        const ventaOtrosChk = document.getElementById('venta_otros_chk');
        const ventaOtrosWrap = document.getElementById('ventaOtrosWrap');
        if (ventaOtrosChk) ventaOtrosChk.addEventListener('change', ()=>{
            ventaOtrosWrap.style.display = ventaOtrosChk.checked ? 'block' : 'none';
        });
        const pagoOtrosChk = document.getElementById('pago_otros_chk');
        const pagoOtrosWrap = document.getElementById('pagoOtrosWrap');
        if (pagoOtrosChk) pagoOtrosChk.addEventListener('change', ()=>{
            pagoOtrosWrap.style.display = pagoOtrosChk.checked ? 'block' : 'none';
        });

        function focusNext(fromEl) {
            const stepEl = fromEl.closest('.step');
            const all = Array.from(stepEl.querySelectorAll('input, select, textarea'));
            const idx = all.indexOf(fromEl);
            for (let i = idx + 1; i < all.length; i++) {
                const el = all[i];
                if (el.offsetParent !== null) { el.focus(); break; }
            }
        }
        document.querySelectorAll('select').forEach(sel => sel.addEventListener('change', () => focusNext(sel)));

        function getStep4Data(){
            const ofrece = document.getElementById('ofrece_empresa').value || '';
            const catalogo = (document.getElementById('chipsHidden').value || '')
                .split(',').map(s=>s.trim()).filter(Boolean);
            const ventas = Array.from(document.querySelectorAll('#ventasGroup input[type="checkbox"]:checked')).map(c=>{
                if (c.value==='mostrador') return 'Mostrador';
                if (c.value==='ecommerce') return 'E-commerce';
                if (c.value==='distribuidores') return 'Distribuidores/Majoristas';
                if (c.value==='marketplaces') return 'Marketplaces';
                if (c.value==='redes') return 'Redes sociales';
                if (c.value==='callcenter') return 'Call center';
                if (c.value==='fuerza') return 'Fuerza de ventas';
                if (c.value==='suscripciones') return 'Suscripciones';
                if (c.value==='eventos') return 'Eventos';
                if (c.value==='otas') return 'OTAs';
                if (c.value==='otros') return (document.getElementById('venta_otros_text').value || 'Otros');
                return c.value;
            });
            const pagos = Array.from(document.querySelectorAll('#pagosGroup input[type="checkbox"]:checked')).map(c=>{
                const map = {
                    efectivo: 'Efectivo', debito: 'Tarjeta débito', credito: 'Tarjeta crédito', transferencia: 'Transferencia bancaria',
                    deposito: 'Depósito en banco', qr_spei_pix: 'Pago QR / SPEI / PIX', paypal: 'PayPal', mercadopago: 'Mercado Pago',
                    stripe: 'Stripe', cheque: 'Cheque', cripto: 'Criptomonedas', otros: (document.getElementById('pago_otros_text').value || 'Otros')
                };
                return map[c.value] || c.value;
            });
            return { ofrece, catalogo, ventas, pagos };
        }
        function validateStep4(){
            const ventasSel = document.querySelectorAll('#ventasGroup input:checked').length;
            const pagosSel = document.querySelectorAll('#pagosGroup input:checked').length;
            if (!ventasSel){ scrollToError(document.querySelector('#ventasGroup')); return false; }
            if (!pagosSel){ scrollToError(document.querySelector('#pagosGroup')); return false; }
            sessionStorage.setItem('step4', JSON.stringify(getStep4Data()));
            return true;
        }

        function setRadio(groupNameStartsWith, value, scope=document){
            const el = scope.querySelector(`input[name^="${groupNameStartsWith}"][value="${value}"]`);
            if (el) el.checked = true;
        }
        function checkByValue(container, values){
            Array.from(container.querySelectorAll('input[type="checkbox"]')).forEach(cb => {
                cb.checked = values.includes(cb.value);
            });
        }

        function restoreStep1(){
            const raw = sessionStorage.getItem('step1'); if (!raw) return;
            try {
                const s1 = JSON.parse(raw);
                if (s1.name) document.getElementById('nombre_completo').value = s1.name;
                if (s1.email) document.getElementById('email').value = s1.email;
                if (s1.phone) document.getElementById('phone').value = s1.phone;
            } catch {}
        }

        function restoreStep2(){
            const raw = sessionStorage.getItem('step2'); if (!raw) return;
            try {
                const s2 = JSON.parse(raw);
                if (s2.companyName) document.getElementById('nombre_empresa').value = s2.companyName;
                if (s2.industry) document.getElementById('industria').value = s2.industry;
                if (s2.description) document.getElementById('descripcion_empresa').value = s2.description;

                isMultiEl.checked = !!s2.isMulti; switchMode();
                if (!s2.countries || !s2.countries.length) { renderTaxCards(); return; }
                if (!s2.isMulti) {
                    const c = s2.countries[0];
                    countrySel.value = c.country;
                    countryOtherWrap.style.display = c.country === 'Otro' ? 'block' : 'none';
                    if (c.country === 'Otro' && c.countryOtherName) countryOther.value = c.countryOtherName;
                    renderTaxCards();
                    const card = taxCards.children[0]; if (card){
                        card.querySelector('.razon').value = c.razon || c.razonSocial || '';
                        const tipo = c.tipoPersona || 'all';
                        setRadio('tipoPersona-0', tipo, card);
                        updateTaxLabels(card, c.country);
                        card.querySelector('.taxId').value = c.taxId || '';
                        if (c.address){
                            card.querySelector('.addr-street').value = c.address.street || '';
                            card.querySelector('.addr-number').value = c.address.number || '';
                            card.querySelector('.addr-district').value = c.address.district || '';
                            card.querySelector('.addr-city').value = c.address.city || '';
                            card.querySelector('.addr-state').value = c.address.state || '';
                            card.querySelector('.addr-postal').value = c.address.postalCode || '';
                        }
                        if (c.phone) card.querySelector('.phone').value = c.phone;
                    }
                } else {
                    selectedCountries = s2.countries.map(c => ({ code: c.country, otherName: c.country === 'Otro' ? (c.countryOtherName||'') : null }));
                    renderCountriesChips();
                    renderTaxCards();
                    s2.countries.forEach((c, idx) => {
                        const card = taxCards.children[idx]; if (!card) return;
                        card.querySelector('.razon').value = c.razon || c.razonSocial || '';
                        const tipo = c.tipoPersona || 'all';
                        setRadio(`tipoPersona-${idx}`, tipo, card);
                        updateTaxLabels(card, c.country);
                        card.querySelector('.taxId').value = c.taxId || '';
                        if (c.address){
                            card.querySelector('.addr-street').value = c.address.street || '';
                            card.querySelector('.addr-number').value = c.address.number || '';
                            card.querySelector('.addr-district').value = c.address.district || '';
                            card.querySelector('.addr-city').value = c.address.city || '';
                            card.querySelector('.addr-state').value = c.address.state || '';
                            card.querySelector('.addr-postal').value = c.address.postalCode || '';
                        }
                        if (c.phone) card.querySelector('.phone').value = c.phone;
                    });
                    renderCountriesOtherChips();
                }
            } catch {}
        }

        function restoreStep3(){
            const raw = sessionStorage.getItem('step3'); if (!raw) return;
            try {
                const s3 = JSON.parse(raw);
                const unidades = Array.isArray(s3.unidades) ? s3.unidades : [];
                document.getElementById('num_unidades').value = unidades.length || 0;
                actualizarUnidades();
                unidades.forEach((u, idx) => {
                    const card = document.querySelectorAll('#unidades-list .unit-card')[idx]; if (!card) return;
                    card.querySelector('.unidad-nombre').value = u.nombreUnidad || '';
                    const list = card.querySelector('.biz-list');
                    (u.negocios||[]).forEach(n => {
                        const chip = document.createElement('span'); chip.className='chip'; chip.textContent=n;
                        const close = document.createElement('span'); close.className='close'; close.textContent='×'; close.addEventListener('click', ()=> chip.remove());
                        chip.appendChild(close); list.appendChild(chip);
                    });
                });
                document.getElementById('num_colaboradores').value = s3.colaboradores || '';
            } catch {}
        }

        function restoreStep4(){
            const raw = sessionStorage.getItem('step4'); if (!raw) return;
            try {
                const s4 = JSON.parse(raw);
                if (s4.ofrece) { document.getElementById('ofrece_empresa').value = s4.ofrece; toggleInventario(); }
                if (Array.isArray(s4.catalogo)) { chips.length = 0; s4.catalogo.forEach(v => chips.push(v)); syncChips(); }
                const ventaMap = {
                    'Mostrador': 'mostrador', 'E-commerce': 'ecommerce', 'Distribuidores/Majoristas': 'distribuidores',
                    'Marketplaces': 'marketplaces', 'Redes sociales': 'redes', 'Call center': 'callcenter',
                    'Fuerza de ventas': 'fuerza', 'Suscripciones': 'suscripciones', 'Eventos': 'eventos', 'OTAs': 'otas'
                };
                const ventasKnown = []; const ventasOtros = [];
                (s4.ventas||[]).forEach(v => { const code = ventaMap[v]; if (code) ventasKnown.push(code); else ventasOtros.push(v); });
                checkByValue(document.getElementById('ventasGroup'), ventasKnown);
                if (ventasOtros.length){ const c = document.getElementById('venta_otros_chk'); c.checked = true; document.getElementById('ventaOtrosWrap').style.display='block'; document.getElementById('venta_otros_text').value = ventasOtros.join(', '); }
                const pagoMap = {
                    'Efectivo':'efectivo','Tarjeta débito':'debito','Tarjeta crédito':'credito','Transferencia bancaria':'transferencia','Depósito en banco':'deposito',
                    'Pago QR / SPEI / PIX':'qr_spei_pix','PayPal':'paypal','Mercado Pago':'mercadopago','Stripe':'stripe','Cheque':'cheque','Criptomonedas':'cripto'
                };
                const pagosKnown = []; const pagosOtros = [];
                (s4.pagos||[]).forEach(p => { const code = pagoMap[p]; if (code) pagosKnown.push(code); else pagosOtros.push(p); });
                checkByValue(document.getElementById('pagosGroup'), pagosKnown);
                if (pagosOtros.length){ const c = document.getElementById('pago_otros_chk'); c.checked = true; document.getElementById('pagoOtrosWrap').style.display='block'; document.getElementById('pago_otros_text').value = pagosOtros.join(', '); }
            } catch {}
        }

        form.addEventListener('submit', () => {
            sessionStorage.setItem('step2', JSON.stringify(getStep2Data()));
            sessionStorage.setItem('step3', JSON.stringify(getStep3Data()));
            sessionStorage.setItem('step4', JSON.stringify(getStep4Data()));
            const all = {
                step1: JSON.parse(sessionStorage.getItem('step1')||'{}'),
                step2: JSON.parse(sessionStorage.getItem('step2')||'{}'),
                step3: JSON.parse(sessionStorage.getItem('step3')||'{}'),
                step4: JSON.parse(sessionStorage.getItem('step4')||'{}')
            };
            document.getElementById('payload').value = JSON.stringify(all);
        }, true);

        showStep(1);
        switchMode();
        restoreStep1();
        restoreStep2();
        restoreStep3();
        restoreStep4();
        renderTaxCards();
    </script>
</body>
</html>
