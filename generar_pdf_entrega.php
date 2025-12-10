<?php
// Archivo: generar_pdf_entrega.php
// Propósito: Generar un archivo PDF con el detalle de la entrega y la firma digital

require 'db.php';
// Incluimos la librería FPDF (Asegúrate de tener la carpeta 'fpdf' con el archivo fpdf.php)
require 'fpdf/fpdf.php';

session_start();

// 1. Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Falta el ID de la entrega.");
}
$id_entrega = $_GET['id'];

// 2. Obtener Datos de la Entrega
$stmt = $pdo->prepare("
    SELECT e.*, u.nombre_completo as responsable 
    FROM entregas e 
    JOIN usuarios u ON e.id_usuario_responsable = u.id 
    WHERE e.id = :id
");
$stmt->execute(['id' => $id_entrega]);
$entrega = $stmt->fetch();

if (!$entrega) {
    die("Entrega no encontrada.");
}

// 3. Obtener Ítems
$stmtItems = $pdo->prepare("
    SELECT ei.*, im.nombre as nombre_insumo, im.codigo 
    FROM entregas_items ei 
    JOIN insumos_medicos im ON ei.id_insumo = im.id 
    WHERE ei.id_entrega = :id
");
$stmtItems->execute(['id' => $id_entrega]);
$items = $stmtItems->fetchAll();

// 4. Configuración del PDF (Clase extendida para Header y Footer personalizados)
class PDF extends FPDF {
    // Cabecera de página
    function Header() {
        // Logo (Si tuvieras uno, descomenta la siguiente línea y pon la ruta)
        // $this->Image('logo.png',10,6,30);
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,utf8_decode('CONSTANCIA DE ENTREGA - INSUMOS MÉDICOS'),0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,utf8_decode('Policlínica ACTIS'),0,1,'C');
        $this->Ln(10);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb} - Generado el '.date('d/m/Y H:i'),0,0,'C');
    }
}

// Crear instancia del PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- SECCIÓN: DATOS GENERALES ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,utf8_decode('Detalles de la Operación #' . $entrega['id']),0,1);
$pdf->Line(10, 35, 200, 35); // Línea separadora

$pdf->SetFont('Arial','',10);
$pdf->Ln(2);

// Fila 1
$pdf->Cell(40,7,utf8_decode('Fecha y Hora:'),0,0);
$pdf->Cell(60,7,date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])),0,1);

// Fila 2
$pdf->Cell(40,7,utf8_decode('Entregado por:'),0,0);
$pdf->Cell(60,7,utf8_decode($entrega['responsable']),0,1);

// Fila 3
$pdf->Cell(40,7,utf8_decode('Solicitante:'),0,0);
$pdf->Cell(60,7,utf8_decode($entrega['solicitante_nombre']),0,0);
$pdf->Cell(30,7,utf8_decode('Área:'),0,0);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(60,7,utf8_decode($entrega['solicitante_area']),0,1);

$pdf->Ln(10);

// --- SECCIÓN: TABLA DE ÍTEMS ---
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(230,230,230); // Gris claro
$pdf->Cell(30,8,utf8_decode('Código'),1,0,'C',true);
$pdf->Cell(130,8,utf8_decode('Descripción del Insumo'),1,0,'L',true);
$pdf->Cell(30,8,utf8_decode('Cantidad'),1,1,'C',true);

$pdf->SetFont('Arial','',10);
foreach ($items as $item) {
    $pdf->Cell(30,8,utf8_decode($item['codigo']),1,0,'C');
    $pdf->Cell(130,8,utf8_decode($item['nombre_insumo']),1,0,'L');
    $pdf->Cell(30,8,$item['cantidad'],1,1,'C');
}

$pdf->Ln(20);

// --- SECCIÓN: FIRMA ---
// Procesar la imagen base64
if (!empty($entrega['firma_solicitante_data'])) {
    $img_data = $entrega['firma_solicitante_data'];
    // Quitar el encabezado "data:image/png;base64," si existe
    $img_data = str_replace('data:image/png;base64,', '', $img_data);
    $img_data = str_replace(' ', '+', $img_data);
    $data = base64_decode($img_data);

    // Guardar temporalmente la imagen
    $temp_file = 'temp_firma_' . $id_entrega . '.png';
    file_put_contents($temp_file, $data);

    // Mostrar en el PDF
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,10,utf8_decode('Conformidad del Solicitante:'),0,1);
    
    // Insertar imagen (Ruta, X, Y, Ancho, Alto)
    $pdf->Image($temp_file, $pdf->GetX(), $pdf->GetY(), 50, 30);
    $pdf->Ln(35); // Bajar cursor después de la imagen
    
    $pdf->Cell(50,5,utf8_decode('_______________________'),0,1,'C');
    $pdf->Cell(50,5,utf8_decode('Firma Registrada'),0,1,'C');

    // Borrar archivo temporal
    unlink($temp_file);
} else {
    $pdf->Cell(0,10,utf8_decode('(Sin firma digital registrada)'),0,1);
}

// Salida del PDF (I = Inline/Navegador, D = Descargar)
$pdf->Output('I', 'Entrega_'.$entrega['id'].'.pdf');
?>