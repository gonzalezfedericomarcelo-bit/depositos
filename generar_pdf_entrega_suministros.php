<?php
// Archivo: generar_pdf_entrega_suministros.php
// Propósito: Generar PDF de constancia para Suministros Generales

require 'db.php';
require 'fpdf/fpdf.php'; // Asegúrate que esta carpeta existe

session_start();

// 1. Validar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Falta el ID de la entrega.");
}
$id_entrega = $_GET['id'];

// 2. Obtener Datos Entrega
$stmt = $pdo->prepare("
    SELECT e.*, u.nombre_completo as responsable 
    FROM entregas e 
    JOIN usuarios u ON e.id_usuario_responsable = u.id 
    WHERE e.id = :id AND e.tipo_origen = 'suministros'
");
$stmt->execute(['id' => $id_entrega]);
$entrega = $stmt->fetch();

if (!$entrega) {
    die("Entrega no encontrada o no corresponde a Suministros.");
}

// 3. Obtener Ítems (JOIN con suministros_generales)
$stmtItems = $pdo->prepare("
    SELECT ei.*, sg.nombre as nombre_suministro, sg.codigo 
    FROM entregas_items ei 
    JOIN suministros_generales sg ON ei.id_suministro = sg.id 
    WHERE ei.id_entrega = :id
");
$stmtItems->execute(['id' => $id_entrega]);
$items = $stmtItems->fetchAll();

// 4. Configuración PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,utf8_decode('CONSTANCIA DE ENTREGA - SUMINISTROS'),0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,utf8_decode('Policlínica ACTIS - Dpto. Logística'),0,1,'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb} - Impreso el '.date('d/m/Y H:i'),0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- DATOS ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,utf8_decode('Entrega #' . $entrega['id']),0,1);
$pdf->Line(10, 35, 200, 35);

$pdf->SetFont('Arial','',10);
$pdf->Ln(2);

$pdf->Cell(40,7,utf8_decode('Fecha:'),0,0);
$pdf->Cell(60,7,date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])),0,1);

$pdf->Cell(40,7,utf8_decode('Responsable:'),0,0);
$pdf->Cell(60,7,utf8_decode($entrega['responsable']),0,1);

$pdf->Cell(40,7,utf8_decode('Solicitante:'),0,0);
$pdf->Cell(60,7,utf8_decode($entrega['solicitante_nombre']),0,0);
$pdf->Cell(30,7,utf8_decode('Área:'),0,0);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(60,7,utf8_decode($entrega['solicitante_area']),0,1);

$pdf->Ln(10);

// --- TABLA ---
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(220,255,220); // Verde claro para diferenciar suministros
$pdf->Cell(30,8,utf8_decode('Código'),1,0,'C',true);
$pdf->Cell(130,8,utf8_decode('Artículo / Suministro'),1,0,'L',true);
$pdf->Cell(30,8,utf8_decode('Cantidad'),1,1,'C',true);

$pdf->SetFont('Arial','',10);
foreach ($items as $item) {
    $pdf->Cell(30,8,utf8_decode($item['codigo']),1,0,'C');
    $pdf->Cell(130,8,utf8_decode($item['nombre_suministro']),1,0,'L');
    $pdf->Cell(30,8,$item['cantidad'],1,1,'C');
}

$pdf->Ln(20);

// --- FIRMA ---
if (!empty($entrega['firma_solicitante_data'])) {
    $img_data = $entrega['firma_solicitante_data'];
    $img_data = str_replace('data:image/png;base64,', '', $img_data);
    $img_data = str_replace(' ', '+', $img_data);
    $data = base64_decode($img_data);

    $temp_file = 'temp_firma_sum_' . $id_entrega . '.png';
    file_put_contents($temp_file, $data);

    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,10,utf8_decode('Conformidad del Solicitante:'),0,1);
    
    $pdf->Image($temp_file, $pdf->GetX(), $pdf->GetY(), 50, 30);
    $pdf->Ln(35);
    
    $pdf->Cell(50,5,utf8_decode('_______________________'),0,1,'C');
    $pdf->Cell(50,5,utf8_decode('Firma Registrada'),0,1,'C');

    unlink($temp_file);
} else {
    $pdf->Cell(0,10,utf8_decode('(Sin firma digital)'),0,1);
}

$pdf->Output('I', 'Entrega_Suministros_'.$entrega['id'].'.pdf');
?>