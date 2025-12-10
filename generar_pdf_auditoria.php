<?php
// Archivo: generar_pdf_auditoria.php
// Propósito: Generar PDF con el historial de modificaciones manuales

require 'db.php';
require 'fpdf/fpdf.php';
session_start();

// Solo Admin
$roles_usuario = $_SESSION['user_roles'] ?? [];
if (!in_array('Administrador', $roles_usuario)) {
    die("Acceso Denegado");
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,utf8_decode('REPORTE DE AUDITORÍA DE STOCK'),0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,utf8_decode('Policlínica ACTIS - Control de Seguridad'),0,1,'C');
        $this->Ln(10);
        
        // Encabezados de tabla
        $this->SetFont('Arial','B',9);
        $this->SetFillColor(200,200,200);
        $this->Cell(35,7,utf8_decode('Fecha'),1,0,'C',true);
        $this->Cell(45,7,utf8_decode('Usuario'),1,0,'C',true);
        $this->Cell(60,7,utf8_decode('Producto'),1,0,'C',true);
        $this->Cell(15,7,utf8_decode('Antes'),1,0,'C',true);
        $this->Cell(15,7,utf8_decode('Nuevo'),1,0,'C',true);
        $this->Cell(20,7,utf8_decode('Dif.'),1,1,'C',true);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Este documento es confidencial - Página ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Obtener datos
$sql = "
    SELECT h.*, u.nombre_completo as usuario,
           im.nombre as nombre_insumo,
           sg.nombre as nombre_suministro
    FROM historial_ajustes h
    JOIN usuarios u ON h.id_usuario = u.id
    LEFT JOIN insumos_medicos im ON h.tipo_origen = 'insumo' AND h.id_item = im.id
    LEFT JOIN suministros_generales sg ON h.tipo_origen = 'suministro' AND h.id_item = sg.id
    ORDER BY h.fecha_cambio DESC
";
$stmt = $pdo->query($sql);
$historial = $stmt->fetchAll();

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

foreach ($historial as $row) {
    $producto = ($row['tipo_origen'] == 'insumo') ? $row['nombre_insumo'] : $row['nombre_suministro'];
    $diferencia = $row['stock_nuevo'] - $row['stock_anterior'];
    $signo = ($diferencia > 0) ? '+' : '';
    
    $pdf->Cell(35,7,date('d/m/Y H:i', strtotime($row['fecha_cambio'])),1);
    $pdf->Cell(45,7,utf8_decode(substr($row['usuario'], 0, 25)),1); // Cortar nombre si es muy largo
    $pdf->Cell(60,7,utf8_decode(substr($producto, 0, 35)),1);
    $pdf->Cell(15,7,$row['stock_anterior'],1,0,'C');
    $pdf->Cell(15,7,$row['stock_nuevo'],1,0,'C');
    
    // Negrita para la diferencia
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(20,7,$signo.$diferencia,1,1,'C');
    $pdf->SetFont('Arial','',9);
}

$pdf->Output('I', 'Auditoria_Stock.pdf');
?>