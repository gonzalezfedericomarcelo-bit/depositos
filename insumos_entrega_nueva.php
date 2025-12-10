<?php
// Archivo: insumos_entrega_nueva.php
// Propósito: Formulario de salida de stock con firma digital (Canvas) - CORREGIDO

require 'db.php';
session_start();

$mensaje = "";

// 1. PROCESAMIENTO DEL FORMULARIO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (empty($_POST['items']) || empty($_POST['firma_base64'])) {
            throw new Exception("Debe seleccionar ítems y firmar la entrega.");
        }

        $pdo->beginTransaction();

        // A. Insertar Cabecera de Entrega
        $stmt = $pdo->prepare("INSERT INTO entregas (tipo_origen, id_usuario_responsable, solicitante_nombre, solicitante_area, firma_solicitante_data) VALUES ('insumos', :user, :solic_nom, :solic_area, :firma)");
        $stmt->execute([
            ':user' => $_SESSION['user_id'],
            ':solic_nom' => $_POST['solicitante_nombre'],
            ':solic_area' => $_POST['solicitante_area'],
            ':firma' => $_POST['firma_base64'] // Guardamos la imagen en base64 directo en la DB
        ]);
        $id_entrega = $pdo->lastInsertId();

        // B. Procesar Ítems y Descontar Stock
        $stmtItem = $pdo->prepare("INSERT INTO entregas_items (id_entrega, id_insumo, cantidad) VALUES (:id_ent, :id_ins, :cant)");
        
        // CORRECCIÓN AQUÍ: Usamos nombres diferentes (:cant y :cant_check) para evitar error HY093
        $stmtStock = $pdo->prepare("UPDATE insumos_medicos SET stock_actual = stock_actual - :cant WHERE id = :id AND stock_actual >= :cant_check");
        
        // Verificación de stock antes de restar (para lanzar excepción bonita)
        $stmtCheck = $pdo->prepare("SELECT stock_actual, nombre FROM insumos_medicos WHERE id = :id");

        foreach ($_POST['items'] as $item) {
            $id_insumo = $item['id_insumo'];
            $cantidad = $item['cantidad'];

            if ($id_insumo && $cantidad > 0) {
                // Chequear stock disponible (Lectura)
                $stmtCheck->execute(['id' => $id_insumo]);
                $insumo_data = $stmtCheck->fetch();

                if ($insumo_data['stock_actual'] < $cantidad) {
                    throw new Exception("Stock insuficiente para: " . $insumo_data['nombre'] . " (Disponible: " . $insumo_data['stock_actual'] . ")");
                }

                // Restar Stock (Escritura)
                // Enviamos el valor de cantidad dos veces con llaves distintas
                $stmtStock->execute([
                    ':cant' => $cantidad, 
                    ':id' => $id_insumo,
                    ':cant_check' => $cantidad
                ]);
                
                if ($stmtStock->rowCount() == 0) {
                    throw new Exception("Error al actualizar stock de: " . $insumo_data['nombre']);
                }

                // Guardar ítem de entrega en el historial
                $stmtItem->execute([
                    ':id_ent' => $id_entrega,
                    ':id_ins' => $id_insumo,
                    ':cant' => $cantidad
                ]);
            }
        }

        $pdo->commit();
        header("Location: insumos_entregas.php?msg=exito");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Incluir Layout
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/navbar.php';

// Obtener lista de insumos con stock positivo
$stmtInsumos = $pdo->query("SELECT id, nombre, codigo, stock_actual FROM insumos_medicos WHERE stock_actual > 0 ORDER BY nombre ASC");
$lista_insumos = $stmtInsumos->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<div class="container-fluid px-4">
    <h1 class="mt-4">Nueva Entrega de Insumos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="insumos_entregas.php">Historial</a></li>
        <li class="breadcrumb-item active">Registrar Salida</li>
    </ol>

    <?php echo $mensaje; ?>

    <form method="POST" action="" id="formEntrega">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-user me-1"></i> Datos del Solicitante
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre y Apellido (Quien retira)</label>
                            <input type="text" name="solicitante_nombre" class="form-control" required placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Área / Servicio</label>
                            <select name="solicitante_area" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="Guardia">Guardia</option>
                                <option value="Internación">Internación</option>
                                <option value="Quirófano">Quirófano</option>
                                <option value="Laboratorio">Laboratorio</option>
                                <option value="Administración">Administración</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Firma Digital</label>
                            <div class="border rounded bg-light text-center">
                                <canvas id="signature-pad" width="300" height="200" style="touch-action: none;"></canvas>
                            </div>
                            <div class="mt-2 text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-signature">Borrar Firma</button>
                            </div>
                            <input type="hidden" name="firma_base64" id="firma_base64">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-boxes me-1"></i> Insumos a Retirar</span>
                        <button type="button" class="btn btn-light btn-sm text-dark" onclick="agregarFila()">
                            <i class="fas fa-plus"></i> Agregar Insumo
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th width="70%">Insumo (Stock Disponible)</th>
                                        <th width="20%">Cantidad</th>
                                        <th width="10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="contenedor-items">
                                    <tr class="fila-item">
                                        <td>
                                            <select name="items[0][id_insumo]" class="form-select select-insumo" onchange="actualizarMaximo(this)" required>
                                                <option value="" data-stock="0">-- Seleccione --</option>
                                                <?php foreach ($lista_insumos as $ins): ?>
                                                    <option value="<?php echo $ins['id']; ?>" data-stock="<?php echo $ins['stock_actual']; ?>">
                                                        <?php echo htmlspecialchars($ins['nombre']) . " (Disp: " . $ins['stock_actual'] . ")"; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][cantidad]" class="form-control" required min="1" max="1" placeholder="Cant.">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila(this)" disabled><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check-circle me-2"></i> Confirmar Entrega
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // 1. CONFIGURACIÓN DEL PAD DE FIRMA
    var canvas = document.getElementById('signature-pad');
    var signaturePad = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)', // Fondo transparente
        penColor: 'rgb(0, 0, 0)'
    });

    document.getElementById('clear-signature').addEventListener('click', function () {
        signaturePad.clear();
    });

    // Antes de enviar el formulario, guardar la firma en el input hidden
    document.getElementById('formEntrega').addEventListener('submit', function (e) {
        if (signaturePad.isEmpty()) {
            e.preventDefault();
            alert("Por favor, solicite la firma del usuario.");
            return;
        }
        var data = signaturePad.toDataURL('image/png');
        document.getElementById('firma_base64').value = data;
    });

    // 2. LÓGICA DE ÍTEMS DINÁMICOS
    let contador = 1;
    
    // Guardamos las opciones del select en una variable para clonarlas fácil
    // OJO: Usamos json_encode para evitar problemas con comillas en nombres de productos
    const opcionesInsumos = `
        <option value="" data-stock="0">-- Seleccione --</option>
        <?php foreach ($lista_insumos as $ins): ?>
            <option value="<?php echo $ins['id']; ?>" data-stock="<?php echo $ins['stock_actual']; ?>">
                <?php echo str_replace('"', '', htmlspecialchars($ins['nombre'])) . " (Disp: " . $ins['stock_actual'] . ")"; ?>
            </option>
        <?php endforeach; ?>
    `;

    function agregarFila() {
        const tbody = document.getElementById('contenedor-items');
        const tr = document.createElement('tr');
        tr.classList.add('fila-item');
        tr.innerHTML = `
            <td>
                <select name="items[${contador}][id_insumo]" class="form-select select-insumo" onchange="actualizarMaximo(this)" required>
                    ${opcionesInsumos}
                </select>
            </td>
            <td>
                <input type="number" name="items[${contador}][cantidad]" class="form-control" required min="1" max="1" placeholder="Cant.">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarFila(this)"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        contador++;
    }

    function eliminarFila(btn) {
        btn.closest('tr').remove();
    }

    // Actualiza el atributo 'max' del input number según el stock seleccionado
    function actualizarMaximo(select) {
        const opcion = select.options[select.selectedIndex];
        const stock = opcion.getAttribute('data-stock');
        // Buscamos el input de cantidad en la misma fila (tr)
        const inputCant = select.closest('tr').querySelector('input[type="number"]');
        
        if (stock) {
            inputCant.max = stock;
            inputCant.placeholder = "Máx " + stock;
            if (parseInt(inputCant.value) > parseInt(stock)) {
                inputCant.value = stock; // Ajustar si se pasa
            }
        }
    }
</script>

<?php include 'includes/footer.php'; ?>