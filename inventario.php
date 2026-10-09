<?php
// === CONFIGURAR ZONA HORARIA BOLIVIA ===
date_default_timezone_set('America/La_Paz');
include_once 'conexion.php';
verificarSesion();
$es_admin = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'administrador') 
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'administrador')
         || (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin')
         || (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin');
if (!$es_admin) {
    die("❌ No tienes permiso para acceder a esta página.");
}
$nombre_admin = $_SESSION['usuario_nombre'] ?? 'Administrador';
$inicial = mb_substr($nombre_admin, 0, 1);
$rol_usuario = $es_admin ? 'Administrador' : 'Operador';

// === PRECIOS FIJOS SEGÚN LO SOLICITADO ===
$PRECIO_COMPRA_GASOLINA = 6.59;   // Bs por litro
$PRECIO_COMPRA_DIESEL   = 17.52;  // Bs por litro
$PRECIO_VENTA_GASOLINA  = 6.96;   // Bs por litro
$PRECIO_VENTA_DIESEL    = 17.95;  // Bs por litro
$GANANCIA_POR_LITRO_GASOLINA = 0.37; // Bs/L
$GANANCIA_POR_LITRO_DIESEL   = 0.43; // Bs/L

// === PROCESAR FORMULARIO DE ENTRADA ===
$mensaje = "";
$tipo_mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fecha            = $_POST['fecha'] ?? date('Y-m-d');
    $producto         = $_POST['producto'] ?? '';
    $litros_recibidos = floatval($_POST['litros_recibidos'] ?? 0);
    $precio_compra    = floatval($_POST['precio_compra'] ?? 0);
    $n_factura        = trim($_POST['n_factura'] ?? '');
    
    if (!empty($producto) && $litros_recibidos > 0) {
        $precio_venta = ($producto === 'gasolina_especial') ? $PRECIO_VENTA_GASOLINA : $PRECIO_VENTA_DIESEL;
        
        $sql = "INSERT INTO movimientos (fecha, tipo, producto, litros_recibidos, precio_nacional, precio_compra, n_factura) 
                VALUES ('$fecha', 'entrada', '$producto', $litros_recibidos, $precio_venta, '$precio_compra', '$n_factura')";
        
        if (mysqli_query($conn, $sql)) {
            $mensaje = "✅ Entrada registrada correctamente";
            $tipo_mensaje = "exito";
        } else {
            $mensaje = "❌ Error: " . mysqli_error($conn);
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "⚠️ Completa todos los campos correctamente.";
        $tipo_mensaje = "error";
    }
}

// === GASOLINA ===
$gas_entradas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(litros_recibidos), 0) AS total 
    FROM movimientos 
    WHERE tipo='entrada' AND producto='gasolina_especial'
"))['total'] ?? 0;
$gas_salidas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(litros_despachados), 0) AS total 
    FROM movimientos 
    WHERE tipo='salida' AND producto='gasolina_especial'
"))['total'] ?? 0;
$gas_costo_total = $gas_entradas * $PRECIO_COMPRA_GASOLINA;
$gas_valor_venta = $gas_salidas * $PRECIO_VENTA_GASOLINA;
$gas_ganancia    = $gas_salidas * $GANANCIA_POR_LITRO_GASOLINA;
$gas_stock       = $gas_entradas - $gas_salidas;

// === DIÉSEL ===
$die_entradas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(litros_recibidos), 0) AS total 
    FROM movimientos 
    WHERE tipo='entrada' AND producto='diesel_oil'
"))['total'] ?? 0;
$die_salidas = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(litros_despachados), 0) AS total 
    FROM movimientos 
    WHERE tipo='salida' AND producto='diesel_oil'
"))['total'] ?? 0;
$die_costo_total = $die_entradas * $PRECIO_COMPRA_DIESEL;
$die_valor_venta = $die_salidas * $PRECIO_VENTA_DIESEL;
$die_ganancia    = $die_salidas * $GANANCIA_POR_LITRO_DIESEL;
$die_stock       = $die_entradas - $die_salidas;

// === TOTALES GENERALES ===
$total_entradas = $gas_entradas + $die_entradas;
$total_salidas  = $gas_salidas + $die_salidas;
$total_costo    = $gas_costo_total + $die_costo_total;
$total_valor    = $gas_valor_venta + $die_valor_venta;
$total_ganancia = $gas_ganancia + $die_ganancia;
$total_stock    = $gas_stock + $die_stock;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario — IMANOL</title>
    <!-- === FAVICON MÁS GRANDE Y NÍTIDO === -->
    <link rel="icon" type="image/png" sizes="192x192" href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="128x128" href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="96x96"   href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="64x64"   href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="48x48"   href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="32x32"   href="imagenes/favicon1.png?v=6">
    <link rel="icon" type="image/png" sizes="16x16"   href="imagenes/favicon1.png?v=6">
    
    <!-- Para dispositivos Apple -->
    <link rel="apple-touch-icon" sizes="180x180" href="imagenes/favicon1.png?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        body { 
            background: #F8F9FA; 
            color: #1F2937; 
            display: flex; 
            min-height: 100vh; 
        }
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #B91C1C 0%, #EA580C 30%, #F59E0B 55%, #FB923C 80%, #DC2626 100%);
            border-right: 2px solid rgba(255, 255, 255, 0.25);
            height: 100vh;
            position: fixed;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 3px 0 15px rgba(185, 28, 28, 0.2);
        }
        .logo-box {
            padding: 14px 12px;
            display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }
        .logo-box img { max-width: 55px; }
        .logo-box h2 { font-size: 20px; color: #fff; margin: 0; }
        .logo-box p { font-size: 10px; color: rgba(255,255,255,0.8); margin: 1px 0 0 0; }
        .sidebar nav {
            padding: 10px; display: flex; flex-direction: column; gap: 2px; flex: 1; overflow-y: auto;
        }
        .sidebar nav a {
            display: flex; align-items: center; gap: 10px; padding: 9px 12px;
            color: rgba(255,255,255,0.9); text-decoration: none; border-radius: 8px;
            font-size: 13px; font-weight: 500; transition: all 0.25s ease;
        }
        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.25); color: #fff; transform: translateX(3px);
        }
        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.3); color: #fff; border-left: 3px solid #fff; font-weight: 600;
        }
        .sidebar .user-info {
            padding: 12px; border-top: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.1);
        }
        .sidebar .user-perfil {
            display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
        }
        .sidebar .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: #fff; color: #B91C1C;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .sidebar .user-perfil h4 { font-size: 13px; color: #fff; margin: 0; }
        .sidebar .user-perfil p { font-size: 11px; color: rgba(255,255,255,0.75); margin: 2px 0 0 0; }
        .sidebar .estado {
            display: flex; align-items: center; gap: 5px; font-size: 11px; color: #90EE90; margin-bottom: 8px;
        }
        .sidebar .estado span {
            width: 7px; height: 7px; border-radius: 50%;
            background: #90EE90; box-shadow: 0 0 4px #90EE90;
        }
        .cerrar-btn {
            background: rgba(0, 0, 0, 0.2); color: #fff; border: none; padding: 8px; border-radius: 7px;
            width: 100%; font-size: 12px; display: flex; align-items: center; justify-content: center;
            gap: 7px; text-decoration: none; transition: all 0.2s;
        }
        .cerrar-btn:hover { background: rgba(0, 0, 0, 0.35); }
        
        .main {
            margin-left: 250px; flex: 1; padding: 24px 30px;
            background: #FFFFFF; min-height: 100vh;
        }
        .contenedor { max-width: 1200px; margin: 0 auto; }
        
        .btn-regresar {
            display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px;
            background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; border-radius: 10px;
            text-decoration: none; font-size: 14px; margin-bottom: 24px; transition: all 0.25s ease;
        }
        .btn-regresar:hover {
            background: #FEF2F2; color: #991B1B; border-color: #DC2626; transform: translateX(-3px);
        }
        .cabecera {
            background: #FFFFFF; border: 1px solid #FECACA; border-radius: 16px; padding: 24px 28px;
            margin-bottom: 24px; display: flex; align-items: center; gap: 20px;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.08);
        }
        .circulo-logo {
            width: 56px; height: 56px; border-radius: 12px;
            background: linear-gradient(135deg, #B91C1C 0%, #EF4444 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #fff;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        .cabecera-texto h1 { font-size: 22px; color: #B91C1C; margin-bottom: 4px; }
        .cabecera-texto p { font-size: 13px; color: #6B7280; }
        
        .mensaje {
            padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;
            text-align: center; font-weight: 600;
        }
        .exito { background: #D1FAE5; color: #065F46; }
        .error { background: #FEE2E2; color: #991B1B; }
        
        .tarjetas { 
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; 
        }
        .tarjeta {
            background: #FFFFFF; padding: 28px 24px; border-radius: 16px;
            border-top: 4px solid; text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .tarjeta.gas { border-top-color: #F59E0B; }
        .tarjeta.die { border-top-color: #DC2626; }
        .tarjeta.total { border-top-color: #22C55E; }
        .tarjeta h3 { font-size: 14px; color: #6B7280; margin-bottom: 16px; font-weight: 600; }
        .tarjeta .grande { font-size: 36px; font-weight: 800; margin-bottom: 8px; }
        .tarjeta.gas .grande { color: #B45309; }
        .tarjeta.die .grande { color: #B91C1C; }
        .tarjeta.total .grande { color: #16A34A; }
        .tarjeta .etiqueta { font-size: 12px; color: #9CA3AF; margin-bottom: 16px; display: block; }
        .tarjeta .dato { font-size: 13px; margin: 6px 0; color: #4B5563; }
        
        /* === TABLA ESTILO PROFESIONAL === */
        .caja-tabla {
            background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 16px;
            padding: 28px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tabla {
            width: 100%; border-collapse: separate; border-spacing: 0; font-size: 14px;
        }
        .tabla th {
            background: linear-gradient(to bottom, #F9FAFB, #F3F4F6);
            padding: 16px 12px;
            text-align: center; color: #374151; font-weight: 700;
            border-bottom: 2px solid #E5E7EB;
            font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .tabla th:first-child { text-align: left; border-top-left-radius: 10px; }
        .tabla th:last-child { border-top-right-radius: 10px; }
        
        .tabla td {
            padding: 18px 12px; border-bottom: 1px solid #F3F4F6;
            color: #4B5563; text-align: center; vertical-align: middle;
            transition: background 0.2s ease;
        }
        .tabla td:first-child { text-align: left; font-weight: 600; }
        
        .tabla tbody tr:hover td {
            background: #FEF2F2;
        }
        .tabla tr:last-child td { border-bottom: none; }
        .tabla tr.fila-total { background: #F0FDF4; font-weight: bold; }
        .tabla tr.fila-total td { border-top: 2px solid #BBF7D0; }
        
        .prec-unit { font-size: 11px; color: #9CA3AF; display: block; margin-top: 3px; font-weight: normal; }
        
        .stock-bajo { 
            color: #D97706; font-weight: 700; 
            background: #FEF3C7; padding: 6px 14px; 
            border-radius: 8px; display: inline-block; font-size: 13px;
            box-shadow: 0 1px 3px rgba(217, 119, 6, 0.1);
        }
        .stock-ok { 
            color: #16A34A; font-weight: 700; 
            background: #D1FAE3; padding: 6px 14px; 
            border-radius: 8px; display: inline-block; font-size: 13px;
            box-shadow: 0 1px 3px rgba(22, 163, 74, 0.1);
        }
        .ganancia-positiva { color: #059669; font-weight: bold; }
        
        @media (max-width: 900px) {
            .tarjetas { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 220px; }
            .main { margin-left: 220px; padding: 16px; }
            .tabla { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo-box">
        <img src="imagenes/favicon1.png" alt="IMANOL">
        <div>
            <h2>MANOL</h2>
            <p>Dios Con Nosotros</p>
        </div>
    </div>
    <nav>
        <a href="index_admin.php"><i class="fas fa-chart-pie"></i> Inicio</a>
        <a href="gasolina.php"><i class="fas fa-gas-pump"></i> Gasolina</a>
        <a href="diesel.php"><i class="fas fa-gas-pump"></i> Diésel</a>
        <a href="reportes_compras.php"><i class="fas fa-file-invoice-dollar"></i> Compras Combustible</a>
        <a href="registro.php"><i class="fas fa-th-list"></i> Registros</a>
        <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
        <a href="reporte_vales.php"><i class="fas fa-ticket-alt"></i> Reporte Vales</a>
        <a href="inventario.php" class="activo"><i class="fas fa-boxes"></i> Inventario</a>
        <a href="historial_calibracion.php"><i class="fas fa-sliders-h"></i> Historial Calibración</a>
        <a href="proveedores.php"><i class="fas fa-truck"></i> Proveedores</a>
        <a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
        <a href="crear_cuenta.php"><i class="fas fa-user-plus"></i> Crear Cuentas</a>
    </nav>
    <div class="user-info">
        <div class="user-perfil">
            <div class="avatar"><?= $inicial ?></div>
            <div>
                <h4><?= $nombre_admin ?></h4>
                <p><?= $rol_usuario ?></p>
            </div>
        </div>
        <div class="estado"><span></span> En línea</div>
        <a href="logout.php" class="cerrar-btn"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
    </div>
</aside>
<main class="main">
    <div class="contenedor">
        <a href="index_admin.php" class="btn-regresar"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        
        <div class="cabecera">
            <div class="circulo-logo"><i class="fas fa-boxes"></i></div>
            <div class="cabecera-texto">
                <h1>Inventario de Combustible</h1>
                <p>Control de existencias — entradas, salidas y stock disponible</p>
            </div>
        </div>
        <?php if ($mensaje): ?>
            <div class="mensaje <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
        <?php endif; ?>
        
        <div class="tarjetas">
            <div class="tarjeta gas">
                <h3><i class="fas fa-gas-pump"></i> Gasolina Especial</h3>
                <div class="grande"><?= number_format($gas_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">En Stock</span>
                <div class="dato"><i class="fas fa-download"></i> Comprado: <?= number_format($gas_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Vendido: <?= number_format($gas_salidas, 2, ',', '.') ?> L</div>
            </div>
            <div class="tarjeta die">
                <h3><i class="fas fa-gas-pump"></i> Diésel Oil</h3>
                <div class="grande"><?= number_format($die_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">En Stock</span>
                <div class="dato"><i class="fas fa-download"></i> Comprado: <?= number_format($die_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Vendido: <?= number_format($die_salidas, 2, ',', '.') ?> L</div>
            </div>
            <div class="tarjeta total">
                <h3><i class="fas fa-chart-bar"></i> Total General</h3>
                <div class="grande"><?= number_format($total_stock, 2, ',', '.') ?> L</div>
                <span class="etiqueta">Combustible Disponible</span>
                <div class="dato"><i class="fas fa-download"></i> Total Comprado: <?= number_format($total_entradas, 2, ',', '.') ?> L</div>
                <div class="dato"><i class="fas fa-upload"></i> Total Vendido: <?= number_format($total_salidas, 2, ',', '.') ?> L</div>
            </div>
        </div>
        
        <div class="caja-tabla">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Litros Comprados</th>
                        <th>Costo Total</th>
                        <th>Litros Vendidos</th>
                        <th>Valor Venta</th>
                        <th>Stock Actual</th>
                        <th>Ganancia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-gas-pump" style="color:#F59E0B; margin-right: 8px;"></i> Gasolina Especial</td>
                        <td><strong><?= number_format($gas_entradas, 2, ',', '.') ?> L</strong></td>
                        <td>Bs <?= number_format($gas_costo_total, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($PRECIO_COMPRA_GASOLINA, 2, ',', '.') ?> Bs/L)</span></td>
                        <td><strong><?= number_format($gas_salidas, 2, ',', '.') ?> L</strong></td>
                        <td>Bs <?= number_format($gas_valor_venta, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($PRECIO_VENTA_GASOLINA, 2, ',', '.') ?> Bs/L)</span></td>
                        <td><strong><?= number_format($gas_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva">Bs <?= number_format($gas_ganancia, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($GANANCIA_POR_LITRO_GASOLINA, 2, ',', '.') ?> Bs/L)</span></td>
                        <td>
                            <?php if ($gas_stock < 500): ?>
                                <span class="stock-bajo">⚠️ Stock Bajo</span>
                            <?php else: ?>
                                <span class="stock-ok">✅ Disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-gas-pump" style="color:#DC2626; margin-right: 8px;"></i> Diésel Oil</td>
                        <td><strong><?= number_format($die_entradas, 2, ',', '.') ?> L</strong></td>
                        <td>Bs <?= number_format($die_costo_total, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($PRECIO_COMPRA_DIESEL, 2, ',', '.') ?> Bs/L)</span></td>
                        <td><strong><?= number_format($die_salidas, 2, ',', '.') ?> L</strong></td>
                        <td>Bs <?= number_format($die_valor_venta, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($PRECIO_VENTA_DIESEL, 2, ',', '.') ?> Bs/L)</span></td>
                        <td><strong><?= number_format($die_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva">Bs <?= number_format($die_ganancia, 2, ',', '.') ?><span class="prec-unit">(<?= number_format($GANANCIA_POR_LITRO_DIESEL, 2, ',', '.') ?> Bs/L)</span></td>
                        <td>
                            <?php if ($die_stock < 500): ?>
                                <span class="stock-bajo">⚠️ Stock Bajo</span>
                            <?php else: ?>
                                <span class="stock-ok">✅ Disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="fila-total">
                        <td><i class="fas fa-chart-bar" style="margin-right: 8px;"></i> Totales Generales</td>
                        <td><strong><?= number_format($total_entradas, 2, ',', '.') ?> L</strong></td>
                        <td><strong>Bs <?= number_format($total_costo, 2, ',', '.') ?></strong></td>
                        <td><strong><?= number_format($total_salidas, 2, ',', '.') ?> L</strong></td>
                        <td><strong>Bs <?= number_format($total_valor, 2, ',', '.') ?></strong></td>
                        <td><strong><?= number_format($total_stock, 2, ',', '.') ?> L</strong></td>
                        <td class="ganancia-positiva" style="font-size:16px;"><strong>Bs <?= number_format($total_ganancia, 2, ',', '.') ?></strong></td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>